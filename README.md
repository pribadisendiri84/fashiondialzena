# FashionDialZena (Laravel)

Katalog fashion + admin stok. Data produk, kategori, New Arrival, Best Seller, dan Best Product disimpan di MySQL.

Repo: https://github.com/pribadisendiri84/fashiondialzena

## Stack

- Laravel 13 (PHP 8.2+)
- MySQL (Hostinger) / SQLite (lokal)

Ini stack yang didukung Hostinger. Document root harus mengarah ke folder `public/`.

## Jalankan lokal

```bash
cd ~/bukalapak/Noted/fashiondialzena
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

- Website: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/admin/login
- Password semua akun contoh: `admin123` (ganti setelah login pertama)

| Email | Role |
|-------|------|
| `admin@fashiondialzena.com` | Owner |
| `staf@fashiondialzena.com` | Staf |
| `sales@fashiondialzena.com` | Penjualan |

`php artisan db:seed` mengisi katalog + contoh **semua jenis input admin** (role, SKU warna/ukuran, semua channel jual, harga custom, stok masuk beda HPP, retur baik/cacat/rusak) **hanya jika belum ada order**. Jangan dijalankan di toko hidup yang sudah berisi transaksi.

## Deploy Hostinger

1. Buat database MySQL di hPanel.
2. Upload project (atau git clone) ke hosting.
3. SSH / Terminal Hostinger:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

4. Isi `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fashiondialzena.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nama_database_hostinger
DB_USERNAME=user_database
DB_PASSWORD=password_database

# Foto produk: storage/app/public/products (wajib storage:link)
```

5. Migrate + seed:

```bash
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

6. Di hPanel → Website → document root: `public`

7. Pastikan folder `storage` dan `bootstrap/cache` writable (775).

## Foto produk (Cloudinary)

Admin → Upload produk → pilih file depan & belakang.

1. File naik ke Cloudinary
2. Database hanya menyimpan URL (`img_front` / `img_back`)
3. Boleh juga paste URL manual jika tidak upload file

Paket gratis Cloudinary biasanya cukup untuk toko kecil. Secret hanya di `.env`, jangan di-commit.

## Admin

- `/admin/login` — masuk
- `/admin` — pembukuan
- `/admin/products/create` — upload produk (+ foto ke Cloudinary)
- `/admin/stock-ins` — tambah stok
- `/admin/sales` — catat penjualan
- `/admin/categories` — kategori
- `/admin/settings` — nomor WhatsApp

## Git (identitas personal, sama pola dedet18)

```bash
cd ~/bukalapak/Noted/fashiondialzena
git config user.name "pribadisendiri84"
git config user.email "pribadisendiri@gmail.com"
git config core.sshCommand "ssh -i ~/.ssh/id_ed25519_dedet18 -o IdentitiesOnly=yes"
```
