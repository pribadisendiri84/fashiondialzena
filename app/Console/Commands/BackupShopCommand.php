<?php

namespace App\Console\Commands;

use App\Services\ShopBackup;
use Illuminate\Console\Command;

class BackupShopCommand extends Command
{
    protected $signature = 'backup:shop {--full : Salin semua foto, bukan hanya yang berubah sejak backup terakhir}';

    protected $description = 'Backup database dan foto produk (berdasarkan last update) ke folder lokal, lalu ke Google Drive jika rclone siap';

    public function handle(ShopBackup $backup): int
    {
        $result = $backup->run((bool) $this->option('full'));

        $this->info($result['message']);
        $this->line('Foto dalam batch ini: '.$result['photos']);
        $this->line('Lokal: '.$result['path']);

        return self::SUCCESS;
    }
}
