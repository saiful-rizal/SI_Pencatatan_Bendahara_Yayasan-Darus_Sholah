<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ItemPembayaranController;
use App\Http\Controllers\HakAksesController;
use App\Http\Controllers\PembayaranTagihanController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TagihanController;


/*
|--------------------------------------------------------------------------
| ROUTE PUBLIK (Tanpa Login)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['security.request', 'throttle:5,1']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode'])->middleware(['security.request', 'throttle:5,1'])->name('password.send');

Route::get('/verify-code', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
Route::post('/verify-code', [ForgotPasswordController::class, 'verifyCode'])->middleware(['security.request', 'throttle:5,1'])->name('password.check');

Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->middleware(['security.request', 'throttle:5,1'])->name('password.update');

Route::middleware(['auth', 'no.cache', 'security.request', 'admin.security'])->group(function () {

    Route::get('/', [BendaharaController::class, 'index'])->name('home');

    Route::middleware('role:super_admin,admin_anggota')->group(function () {
        Route::middleware('role:super_admin')->group(function () {
        Route::get('/hak-akses', [HakAksesController::class, 'index'])->name('hak-akses.index');
        Route::post('/hak-akses', [HakAksesController::class, 'store'])->name('hak-akses.store');
        Route::put('/hak-akses/{user}', [HakAksesController::class, 'update'])->name('hak-akses.update');
        Route::delete('/hak-akses/{user}', [HakAksesController::class, 'destroy'])->name('hak-akses.destroy');

        Route::get('/item-pembayaran', [ItemPembayaranController::class, 'index'])->name('item.index');
        Route::get('/item-pembayaran/tambah', [ItemPembayaranController::class, 'create'])->name('item.create');
        Route::post('/item-pembayaran', [ItemPembayaranController::class, 'store'])->name('item.store');
        Route::put('/item-pembayaran/{item}', [ItemPembayaranController::class, 'update'])->name('item.update');
        Route::put('/item-pembayaran/{item}/toggle-aktif', [ItemPembayaranController::class, 'toggleAktif'])->name('item.toggle-aktif');
        Route::delete('/item-pembayaran/{item}', [ItemPembayaranController::class, 'destroy'])->name('item.destroy');
        });

        Route::get('/data-siswa', [SiswaController::class, 'index'])->name('siswa.index');
        Route::get('/data-siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
        Route::post('/data-siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::put('/data-siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::delete('/data-siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
        Route::post('/data-siswa/naik-kelas', [SiswaController::class, 'naikKelasMassal'])->name('siswa.naik-kelas');
        Route::post('/data-siswa/import', [SiswaController::class, 'import'])->middleware('throttle:5,1')->name('siswa.import');
        Route::get('/data-siswa/{siswa}/cetak', [SiswaController::class, 'cetak'])->name('siswa.cetak');

        Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
        Route::get('/tagihan/tambah', [TagihanController::class, 'create'])->name('tagihan.create');
        Route::post('/tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
        Route::put('/tagihan/{tagihan}', [TagihanController::class, 'update'])->name('tagihan.update');
        Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'destroy'])->name('tagihan.destroy');
        Route::post('/tagihan/{tagihan}/potongan', [TagihanController::class, 'tambahPotongan'])->name('tagihan.potongan.store');
        Route::put('/tagihan/{tagihan}/potongan/{potongan}', [TagihanController::class, 'updatePotongan'])->name('tagihan.potongan.update');
        Route::delete('/tagihan/{tagihan}/potongan/{potongan}', [TagihanController::class, 'hapusPotongan'])->name('tagihan.potongan.destroy');

        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/transaksi', [BendaharaController::class, 'store'])->middleware('throttle:30,1')->name('transaksi.store');
            Route::delete('/transaksi/{id}', [BendaharaController::class, 'destroy'])->middleware('throttle:20,1')->name('transaksi.destroy');
            Route::get('/riwayat-hapus', [BendaharaController::class, 'riwayat'])->name('transaksi.riwayat');
            Route::post('/riwayat-hapus/{id}/restore', [BendaharaController::class, 'restore'])->middleware('throttle:20,1')->name('transaksi.restore');
            Route::post('/riwayat-hapus/purge', [BendaharaController::class, 'purgeRiwayat'])->middleware('throttle:10,1')->name('transaksi.riwayat.purge');
            Route::get('/cetak/nota/{id}', [BendaharaController::class, 'cetakNota'])->name('cetak.nota');

            Route::get('/pengeluaran', [BendaharaController::class, 'pengeluaran'])->name('pengeluaran.index');
            Route::post('/pengeluaran', [BendaharaController::class, 'storePengeluaran'])->name('pengeluaran.store');

            Route::get('/laporan/wali-murid', [BendaharaController::class, 'laporanWali'])->name('laporan.wali');
            Route::get('/laporan/wali-murid/export', [BendaharaController::class, 'exportWali'])->name('laporan.wali.export');
            Route::get('/laporan/pemasukan-dana', [BendaharaController::class, 'laporanPemasukanDana'])->name('laporan.pemasukan');
            Route::get('/laporan/pemasukan-dana/export', [BendaharaController::class, 'exportPemasukanDana'])->name('laporan.pemasukan.export');
            Route::get('/laporan/pengeluaran-dana', [BendaharaController::class, 'laporanPengeluaranDana'])->name('laporan.pengeluaran');
            Route::get('/laporan/pengeluaran-dana/export', [BendaharaController::class, 'exportPengeluaranDana'])->name('laporan.pengeluaran.export');
            Route::get('/laporan/rekap-kas', [BendaharaController::class, 'laporanRekapKas'])->name('laporan.rekap-kas');
            Route::get('/laporan/rekap-kas/export', [BendaharaController::class, 'exportRekapKas'])->name('laporan.rekap-kas.export');
            Route::get('/laporan/yayasan', [BendaharaController::class, 'laporanYayasan'])->name('laporan.yayasan');
            Route::get('/laporan/yayasan/export', [BendaharaController::class, 'exportYayasan'])->name('laporan.yayasan.export');

            Route::get('/backup/database', [BackupController::class, 'index'])->name('backup.database');
            Route::post('/backup/database', [BackupController::class, 'download'])->middleware('throttle:5,1')->name('backup.database.download');
        });
    });

    Route::middleware('role:super_admin,admin_anggota')->group(function () {
        Route::get('/transaksi-pembayaran', [PembayaranTagihanController::class, 'index'])->name('pembayaran.index');
        Route::get('/transaksi-pembayaran/cetak-nota', [PembayaranTagihanController::class, 'cetakNota'])->name('pembayaran.cetak-nota');
        Route::post('/transaksi-pembayaran/bayar-semua', [PembayaranTagihanController::class, 'bayarSemuaSiswa'])->name('pembayaran.bayar-semua');
        Route::post('/transaksi-pembayaran/{tagihan}', [PembayaranTagihanController::class, 'store'])->name('pembayaran.store');
    });
});
