<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class ShopBackup
{
    /**
     * @return array{stamp: string, path: string, photos: int, uploaded: bool, message: string}
     */
    public function run(bool $fullPhotos = false): array
    {
        $stamp = now(config('backup.timezone'))->format('Y-m-d_His').'_'.Str::lower(Str::random(4));
        $runDir = rtrim((string) config('backup.destination'), '/').'/'.$stamp;
        File::ensureDirectoryExists($runDir.'/photos');

        $state = $this->state();
        $since = $fullPhotos || empty($state['last_ran_at'])
            ? null
            : Carbon::parse($state['last_ran_at']);

        $this->dumpDatabase($runDir.'/database.sql');
        $photoCount = $this->copyPhotos($runDir.'/photos', $since);

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'full_photos' => $fullPhotos || $since === null,
            'since' => $since?->toIso8601String(),
            'photos' => $photoCount,
            'database' => 'database.sql',
        ];
        File::put($runDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $uploaded = $this->upload($runDir, $stamp);
        $this->writeState([
            'last_ran_at' => now()->toIso8601String(),
            'last_stamp' => $stamp,
        ]);
        $this->prune();

        $message = $uploaded
            ? 'Backup '.$stamp.' tersimpan lokal dan diunggah ke Google Drive.'
            : 'Backup '.$stamp.' tersimpan lokal. rclone belum siap, jadi belum ke Drive.';

        return [
            'stamp' => $stamp,
            'path' => $runDir,
            'photos' => $photoCount,
            'uploaded' => $uploaded,
            'message' => $message,
        ];
    }

    /**
     * @return array{last_ran_at?: string, last_stamp?: string}
     */
    public function state(): array
    {
        $file = $this->stateFile();
        if (! File::exists($file)) {
            return [];
        }

        $data = json_decode(File::get($file), true);

        return is_array($data) ? $data : [];
    }

    private function dumpDatabase(string $destination): void
    {
        $name = (string) config('database.default');
        $driver = (string) config('database.connections.'.$name.'.driver');

        if ($driver === 'sqlite') {
            $database = (string) config('database.connections.'.$name.'.database');
            if ($database === '' || $database === ':memory:') {
                File::put($destination, "-- sqlite in-memory: dump dilewati\n");

                return;
            }

            File::copy($database, $destination);

            return;
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException('Backup database hanya mendukung mysql dan sqlite.');
        }

        $this->dumpMysql($name, $destination);
    }

    private function dumpMysql(string $connection, string $destination): void
    {
        $host = (string) config('database.connections.'.$connection.'.host');
        $port = (string) config('database.connections.'.$connection.'.port', '3306');
        $database = (string) config('database.connections.'.$connection.'.database');
        $username = (string) config('database.connections.'.$connection.'.username');
        $password = (string) config('database.connections.'.$connection.'.password');

        $cnf = tempnam(sys_get_temp_dir(), 'alzena-mycnf-');
        File::put($cnf, "[client]\nuser=".addcslashes($username, "\\\n")."\npassword=\"".addcslashes($password, "\\\"\n")."\"\nhost=".$host."\nport=".$port."\n");

        try {
            $result = Process::timeout(180)->run([
                'mysqldump',
                '--defaults-extra-file='.$cnf,
                '--single-transaction',
                '--routines',
                '--triggers',
                $database,
            ]);

            if ($result->failed()) {
                throw new RuntimeException('mysqldump gagal: '.$result->errorOutput());
            }

            File::put($destination, $result->output());
        } finally {
            File::delete($cnf);
        }
    }

    private function copyPhotos(string $destination, ?Carbon $since): int
    {
        $source = (string) config('backup.photos_path');
        File::ensureDirectoryExists($destination);

        if (! File::isDirectory($source)) {
            return 0;
        }

        $copied = 0;
        foreach (File::files($source) as $file) {
            /** @var SplFileInfo $file */
            if ($since && $file->getMTime() < $since->timestamp) {
                continue;
            }

            File::copy($file->getPathname(), $destination.'/'.$file->getFilename());
            $copied++;
        }

        return $copied;
    }

    private function upload(string $runDir, string $stamp): bool
    {
        $remote = trim((string) config('backup.rclone_remote'));
        if ($remote === '') {
            return false;
        }

        $version = Process::timeout(15)->run(['rclone', 'version']);
        if ($version->failed()) {
            throw new RuntimeException('BACKUP_RCLONE_REMOTE terisi, tapi rclone tidak ketemu di VPS.');
        }

        $target = rtrim($remote, '/').'/'.$stamp;
        $result = Process::timeout(600)->run([
            'rclone',
            'copy',
            $runDir,
            $target,
            '--create-empty-src-dirs',
        ]);

        if ($result->failed()) {
            throw new RuntimeException('rclone gagal: '.$result->errorOutput());
        }

        return true;
    }

    /**
     * @param  array{last_ran_at: string, last_stamp: string}  $state
     */
    private function writeState(array $state): void
    {
        File::ensureDirectoryExists(dirname($this->stateFile()));
        File::put($this->stateFile(), json_encode($state, JSON_PRETTY_PRINT));
    }

    private function prune(): void
    {
        $keepDays = max(1, (int) config('backup.keep_days'));
        $root = rtrim((string) config('backup.destination'), '/');
        if (! File::isDirectory($root)) {
            return;
        }

        $cutoff = now()->subDays($keepDays)->getTimestamp();
        foreach (File::directories($root) as $dir) {
            if (basename($dir) === 'state.json') {
                continue;
            }

            if (File::lastModified($dir) < $cutoff) {
                File::deleteDirectory($dir);
            }
        }
    }

    private function stateFile(): string
    {
        return rtrim((string) config('backup.destination'), '/').'/state.json';
    }
}
