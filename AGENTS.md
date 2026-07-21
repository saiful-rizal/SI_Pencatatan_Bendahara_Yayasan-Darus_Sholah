# Bendahara App — Laravel 10

Aplikasi pembayaran sekolah berbasis Laravel 10.

## Stack
- Laravel 10, PHP 8.1+, MySQL (lokal) / PostgreSQL (Heroku)
- Bootstrap 5 + jQuery, Chart.js, Font Awesome
- Vite 5, Laravel Excel (maatwebsite), Sanctum

## Prasyarat
- PHP **zip** extension wajib aktif (`extension=zip` di php.ini) untuk import Excel (.xlsx)

## Perintah
- `php artisan migrate` — jalankan migration
- `php artisan migrate:fresh` — reset + migrate ulang (data hilang)
- `php artisan db:seed --class=AccessUserSeeder` — 2 user default (super_admin, admin_anggota)
- `php artisan tinker` — lalu `(new Database\Seeders\tinker_seed)->run()` — data demo lengkap
- `php artisan serve` — dev server
- `npm install && npm run dev` — Vite HMR
- `php artisan backup:database --format=json --keep=30` — backup (dijadwalkan tiap 02:00)

## Arsitektur
- **Role**: `super_admin`, `admin_anggota` (method: `$user->isSuperAdmin()`)
- **Middleware kustom**: `security.request`, `role`, `no.cache`, `admin.security`
- **Login rate limit**: 5 percobaan/menit
- **Siswa**: kategori `mondok` / `non_mondok`
- **ItemPembayaran**: `berlaku_untuk` = `mondok` / `non_mondok` / `semua`
- **Tagihan status**: `belum_lunas` / `sebagian` / `lunas`
- **Kelas dinormalisasi**: X→10, XI→11, XII→12, XIII→13
- **`norek`** di `pembayaran_tagihans` — only when `metode_bayar=transfer`
- **`PembayaranTagihan::saved()`** — auto-sync ke Transaksi + DetailTransaksi
- **Soft delete** transaksi + `deletion_histories` untuk audit
- **Pagination**: Bootstrap 5 via `AppServiceProvider`

## Laporan Yayasan
- `group_by=kategori` → group by `transaksis.kategori`
- `group_by=per_item` → group pemasukan by `detail_transaksis.nama_item` (menampilkan semua item)
- `group_by={id}` → filter spesifik satu item pembayaran
- Export Excel & Cetak PDF menggunakan data yang sama

## Testing
- PHPUnit 10 terkonfigurasi, belum ada test ditulis.
- Tidak ada CI pipeline.

## Deploy (Heroku)
- Procfile: `web: heroku-php-apache2 public/`
- Release: `php artisan migrate --force`
- DB: PostgreSQL via heroku-postgresql:essential-0
