# Backup database + foto ke Google Drive

Setiap hari jam **02:30** (Jakarta) Laravel menjalankan `backup:shop`:

- dump **seluruh database** (aman untuk restore)
- foto hanya yang **berubah sejak backup terakhir** (mtime)
- simpan di VPS: `storage/app/private/backups/` (bukan folder publik)
- unggah ke Google Drive jika `rclone` sudah dikonfigurasi

`git pull` tidak menghapus backup ini.

## Sekali di VPS: rclone + Google Drive

```bash
apt install -y rclone
rclone config
```

Pilih `n` (new remote), nama `gdrive`, type `drive`, lalu login Google di browser. Folder tujuan misalnya `fashiondialzena`.

Tes:

```bash
rclone lsd gdrive:
```

Di `.env` produksi:

```
BACKUP_RCLONE_REMOTE=gdrive:fashiondialzena
BACKUP_KEEP_DAYS=7
```

Lalu:

```bash
php artisan config:cache
php artisan backup:shop
```

Backup pertama menyalin **semua** foto. Berikutnya hanya file yang berubah (termasuk foto yang ditimpa).

Paksa semua foto:

```bash
php artisan backup:shop --full
```

## Cron Laravel

Satu baris di crontab root/www-data:

```bash
* * * * * cd /var/www/fashiondialzena && php artisan schedule:run >> /dev/null 2>&1
```

Tanpa baris ini, jadwal 02:30 tidak jalan.

## Restore singkat

1. Download folder tanggal dari Drive (atau ambil dari `storage/app/private/backups/…`).
2. Database: `mysql … < database.sql`
3. Foto: salin isi `photos/` ke `storage/app/public/products/`

Lokal di VPS hanya disimpan **7 hari** (`BACKUP_KEEP_DAYS`). Salinan di Drive tidak dihapus otomatis.
