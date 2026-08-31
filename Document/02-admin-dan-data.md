# Admin dan struktur data

## Alur kerja halaman (disengaja berbeda)

| Halaman | Cara isi data | Alasan |
|---------|----------------|--------|
| **Produk** | Tombol di atas → form terpisah | Form panjang (foto, kategori, SKU, harga, label) |
| **Penjualan, Stok masuk, Retur** | Form di halaman yang sama dengan riwayat | Form pendek, diisi berulang setiap hari |

Tampilan (sidebar, kartu, panel, filter tanggal) diseragamkan. Alur isi form tidak dipaksa sama.

## Filter periode

Komponen: `resources/views/admin/partials/date-range.blade.php`  
Logika: `app/Http/Controllers/Concerns/ResolvesDateRange.php`

- Default: tanggal 1 bulan ini sampai hari ini
- Query: `?from=YYYY-MM-DD&to=YYYY-MM-DD`
- Tanggal masa depan dipotong ke hari ini; Dari > Sampai ditukar
- KPI keuangan/aktivitas mengikuti rentang; stok sisa di Pembukuan tetap **posisi terkini**

## Model penjualan

Tabel inti:

- `products` + `product_variants` (SKU unik, stok, HPP, harga jual)
- `orders` + `order_items` (header struk + baris)
- `order_returns` menempel ke baris, qty tidak boleh melebihi sisa
- `stock_ins` restock per SKU, HPP rata-rata tertimbang
- `stock_movements` jejak masuk / jual / retur / pembatalan

Uang disimpan integer Rupiah.

**Satu order bisa banyak SKU.** Semua item divalidasi dan stok dipotong dalam satu transaksi. Jika satu SKU kurang stok, seluruh order dibatalkan. SKU yang sama tidak boleh dua kali dalam satu form. Laba kotor di riwayat dihitung **per baris**, bukan mengulang laba seluruh order.

Yang sengaja belum ada: status pesanan (draft/lunas), tabel pembayaran, master pelanggan, FIFO gudang.

## URL admin

| Path | Nama |
|------|------|
| `/admin/login` | Login |
| `/admin` | Dashboard |
| `/admin/pembukuan` | Pembukuan per SKU |
| `/admin/sales` | Penjualan |
| `/admin/stock-ins` | Stok masuk |
| `/admin/returns` | Retur & refund |
| `/admin/products` | Produk |
| `/admin/categories` | Kategori |
| `/admin/settings` | Pengaturan (WA, dll.) |

Foto produk disimpan di VPS: `storage/app/public/products`, nama file `nama-produk-depan-xxxxxx.jpg`. Foto lama di Cloudinary tetap dipakai sampai diganti. Wajib `php artisan storage:link`.
