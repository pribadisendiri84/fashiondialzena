<?php

namespace Tests\Feature;

use App\Services\ShopBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ShopBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $photos;

    private string $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->photos = storage_path('framework/testing/backup-photos-'.uniqid());
        $this->destination = storage_path('framework/testing/backup-dest-'.uniqid());
        File::ensureDirectoryExists($this->photos);
        File::ensureDirectoryExists($this->destination);

        config([
            'backup.photos_path' => $this->photos,
            'backup.destination' => $this->destination,
            'backup.rclone_remote' => null,
            'backup.keep_days' => 7,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->photos);
        File::deleteDirectory($this->destination);
        parent::tearDown();
    }

    public function test_first_backup_copies_all_photos_and_skips_drive_without_rclone(): void
    {
        File::put($this->photos.'/kaos-depan-aaaaaa.jpg', 'front');
        File::put($this->photos.'/kaos-belakang-bbbbbb.jpg', 'back');

        $this->artisan('backup:shop')
            ->assertSuccessful()
            ->expectsOutputToContain('tersimpan lokal');

        $runs = collect(File::directories($this->destination))->sort()->values();
        $this->assertCount(1, $runs);
        $this->assertFileExists($runs[0].'/database.sql');
        $this->assertFileExists($runs[0].'/photos/kaos-depan-aaaaaa.jpg');
        $this->assertFileExists($runs[0].'/photos/kaos-belakang-bbbbbb.jpg');
        $this->assertFileExists($this->destination.'/state.json');
    }

    public function test_later_backup_only_copies_photos_changed_since_last_run(): void
    {
        File::put($this->photos.'/lama.jpg', 'old');
        touch($this->photos.'/lama.jpg', now()->subDay()->timestamp);

        app(ShopBackup::class)->run();

        File::put($this->photos.'/baru.jpg', 'new');

        $second = app(ShopBackup::class)->run();
        $this->assertSame(1, $second['photos']);
        $this->assertFileExists($second['path'].'/photos/baru.jpg');
        $this->assertFileDoesNotExist($second['path'].'/photos/lama.jpg');
    }

    public function test_full_flag_copies_every_photo_again(): void
    {
        File::put($this->photos.'/lama.jpg', 'old');
        app(ShopBackup::class)->run();

        $full = app(ShopBackup::class)->run(true);
        $this->assertSame(1, $full['photos']);
        $this->assertFileExists($full['path'].'/photos/lama.jpg');
    }
}
