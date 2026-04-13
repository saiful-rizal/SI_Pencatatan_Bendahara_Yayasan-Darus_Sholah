# Bendahara App (Laravel 10)

Aplikasi pembayaran sekolah berbasis Laravel 10 dengan modul utama:

- Dashboard ringkas tagihan
- Data Siswa (master + import Excel + popup detail + cetak)
- Item Pembayaran
- Tagihan + Potongan
- Transaksi Pembayaran
- Tanggungan + Bayar Semua
- Rekap Keuangan
- Backup Database JSON

## Kebutuhan

- PHP 8.1+
- Composer
- MySQL / MariaDB
- Node.js (opsional, untuk asset Vite)

## Instalasi Cepat

1. Clone project lalu masuk folder project.
2. Install dependency:
    - `composer install`
    - `npm install` (opsional)
3. Buat file env:
    - `copy .env.example .env`
4. Atur koneksi database pada `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
5. Generate key:
    - `php artisan key:generate`
6. Jalankan migration:
    - `php artisan migrate`
7. Jalankan server:
    - `php artisan serve`

## Akun Login

Gunakan akun yang sudah ada di database. Jika belum ada, buat user dari Tinker:

`php artisan tinker`

Lalu jalankan:

`\App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('123123')]);`

## Alur Penggunaan

1. Input master `Data Siswa`.
2. Input `Item Pembayaran`.
3. Buat `Tagihan` per siswa dan item.
4. Tambahkan `Potongan` bila diperlukan.
5. Input `Transaksi Pembayaran`.
6. Cek `Tanggungan` dan `Rekap`.
7. Unduh `Backup Database` secara berkala.

## Catatan

- Kategori siswa: `mondok` / `non_mondok`.
- Item pembayaran memiliki `berlaku_untuk`: `mondok` / `non_mondok` / `semua`.
- Status tagihan: `belum_lunas` / `sebagian` / `lunas`.

## Deploy ke Heroku

Project ini sudah disiapkan untuk Heroku dengan:

- `Procfile`:
  - `web: heroku-php-apache2 public/`
  - `release: php artisan migrate --force`
- `app.json` (addon PostgreSQL + buildpack Node.js dan PHP)

### Langkah Deploy (CLI)

1. Login dan buat app Heroku:

    ```bash
    heroku login
    heroku create nama-app-anda
    ```

2. Pastikan buildpack urutannya Node.js lalu PHP:

    ```bash
    heroku buildpacks:clear -a nama-app-anda
    heroku buildpacks:add heroku/nodejs -a nama-app-anda
    heroku buildpacks:add heroku/php -a nama-app-anda
    ```

3. Tambahkan database PostgreSQL:

    ```bash
    heroku addons:create heroku-postgresql:essential-0 -a nama-app-anda
    ```

4. Set environment variable penting:

    ```bash
    heroku config:set APP_ENV=production APP_DEBUG=false DB_CONNECTION=pgsql -a nama-app-anda
    heroku config:set APP_KEY="$(php artisan key:generate --show)" -a nama-app-anda
    heroku config:set APP_URL=https://nama-app-anda.herokuapp.com -a nama-app-anda
    ```

5. Deploy ke Heroku:

    ```bash
    git push heroku main
    ```

6. Jalankan seeder bila diperlukan:

    ```bash
    heroku run php artisan db:seed -a nama-app-anda
    ```

7. Buka aplikasi:

    ```bash
    heroku open -a nama-app-anda
    ```

### Catatan Heroku

- File system Heroku bersifat ephemeral (tidak persisten).
- Simpan file upload/backup di object storage (misalnya S3) jika ingin permanen.
- Jika domain berubah, update `APP_URL` via `heroku config:set`.
