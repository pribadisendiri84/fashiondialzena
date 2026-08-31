# Update VPS setelah `git pull`

Git **hanya** menyalin file dari GitHub. Proses PHP yang sedang jalan (PHP-FPM) masih memakai:

- kode PHP lama di OPcache sampai FPM di-reload atau cache kadaluarsa
- config Laravel yang sudah di-`config:cache`
- view Blade yang sudah di-compile di `storage/framework/views`
- route yang sudah di-`route:cache`

Tanpa langkah di bawah, browser bisa tetap menampilkan versi lama.

## Alur dari laptop ke VPS

1. Lokal: commit + `git push origin main`
2. SSH ke VPS
3. `git pull` di folder aplikasi
4. Perintah Laravel + reload FPM (checklist di bawah)
5. Hard refresh browser (PWA/service worker bisa menahan CSS/JS lama)

`.env` **tidak** ikut pull. Jangan menimpa `.env` produksi.

## Checklist (salin di VPS)

```bash
cd /var/www/fashiondialzena

# 1. Ambil kode terbaru
git fetch origin
git status
git pull origin main

# 2. Dependensi PHP (wajib jika composer.lock berubah)
composer install --no-dev --optimize-autoloader

# 3. Skema database (aman jika tidak ada migrasi baru; jangan seed)
php artisan migrate --force

# 4. Buang cache lama lalu bangun ulang untuk production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Izin folder tulis
chown -R www-data:www-data storage bootstrap/cache

# 6. Paksa PHP-FPM memakai file PHP baru (penting)
systemctl reload php8.4-fpm
# jika sock/unit bernama lain: systemctl reload php8.4-fpm  atau php8.3-fpm
```

Cek versi FPM:

```bash
systemctl list-units 'php*-fpm*' --no-pager
```

Tidak perlu `systemctl reload nginx` kecuali Anda mengubah file config Nginx.

Tidak perlu `npm` jika perubahan CSS/JS sudah ada di `public/` dan ikut di-commit.

## Kapan langkah mana wajib

| Perubahan di Git | Wajib di VPS |
|------------------|--------------|
| Blade, controller, CSS di `public/` | `optimize:clear` + cache ulang + **reload php-fpm** |
| `composer.lock` / paket baru | `composer install --no-dev --optimize-autoloader` |
| File di `database/migrations/` | `php artisan migrate --force` |
| Hanya `.env.example` | Abaikan, atau salin nilai baru ke `.env` manual |
| `public/sw.js` (PWA) | Reload FPM + di HP/desktop: tutup PWA, hard refresh, atau hapus data situs |

## Yang tidak dijalankan di toko hidup

```bash
php artisan migrate:fresh   # menghapus semua data
php artisan db:seed         # data contoh; bisa bentrok dengan data nyata
```

## Cek cepat setelah update

```bash
cd /var/www/fashiondialzena
git log -1 --oneline
php artisan about
curl -I https://fashiondialzena.com
```

Buka storefront dan `/admin`. Jika CSS/logo lama: hard refresh (`Cmd+Shift+R`) atau mode penyamaran. Kalau PWA terpasang, increment cache name di `public/sw.js` (sudah pernah diubah ke `alzena-v2`) memaksa worker ambil aset baru setelah kunjungan berikutnya.

## Ringkas satu blok (setelah pull sukses)

```bash
cd /var/www/fashiondialzena && \
composer install --no-dev --optimize-autoloader && \
php artisan migrate --force && \
php artisan optimize:clear && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chown -R www-data:www-data storage bootstrap/cache && \
systemctl reload php8.4-fpm
```
