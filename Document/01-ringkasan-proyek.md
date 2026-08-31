# Ringkasan proyek

## Apa ini

**ALZena Fashion** (sebelumnya FashionDialZena) adalah katalog toko fashion **tanpa keranjang**. Pengunjung pesan lewat **WhatsApp** dengan teks yang sudah berisi nama, harga, dan link foto produk.

Aplikasi ini Laravel: storefront publik + panel admin untuk stok, penjualan, retur, dan pembukuan.

Repo: `git@github.com:pribadisendiri84/fashiondialzena.git`  
Cabang kerja: `main`

## Jejak pekerjaan (urutan kasar)

1. **Katalog HTML statis** — grid produk, foto depan/belakang, pesan WA per item.
2. **Laravel** — produk, kategori, SKU (varian), admin login, storefront dari database.
3. **Pembukuan** — stok masuk, penjualan, retur, HPP rata-rata tertimbang, ledger pergerakan stok.
4. **PWA** — `manifest.webmanifest`, `sw.js`, ikon, halaman offline, banner instal.
5. **Branding ALZena** — nama, logo di header/login, tagline di hero, ikon PWA baru.
6. **Cari di storefront** — filter produk, sembunyikan section lain saat mencari.
7. **Admin UI** — sidebar, kartu KPI, preview foto produk sebelum simpan.
8. **Dashboard vs Pembukuan** — ringkasan di `/admin`, detail SKU di `/admin/pembukuan`.
9. **Filter tanggal Dari–Sampai** — Dashboard, Pembukuan, Penjualan, Stok masuk, Retur.
10. **Penjualan multi-SKU** — satu nomor order bisa beberapa item; stok atomik; laba per baris.

Situs lain (**Dedet 18**) di-host di VPS yang sama sebagai virtual host terpisah, bukan subfolder Laravel ini.

## Menjalankan di komputer lokal

PHP 8.4+ disarankan (lock Composer memakai paket yang minta PHP ≥ 8.4.1). PHP 8.3 di server akan gagal `composer install` jika lock tidak diubah.

```bash
cd fashiondialzena
cp .env.example .env
php artisan key:generate
# lokal: DB_CONNECTION=sqlite (default .env.example)
php artisan migrate
php artisan db:seed   # opsional: katalog contoh + user admin
php artisan serve
```

Buka `http://127.0.0.1:8000` (storefront) dan `http://127.0.0.1:8000/admin/login`.

CSS admin/storefront ada di `public/css/` (ikut Git). Tidak wajib `npm run build` untuk perubahan tampilan yang sudah di-commit.

## File yang tidak di-commit

- `.env` — kunci aplikasi, DB, Cloudinary
- `storage/logs`, cache view yang ter-generate

`.env.example` boleh di-commit sebagai template tanpa secret.
