<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ItemPembayaranController;
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
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode'])->name('password.send');

Route::get('/verify-code', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
Route::post('/verify-code', [ForgotPasswordController::class, 'verifyCode'])->name('password.check');

Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

Route::middleware(['auth'])->group(function () {

    Route::get('/', [BendaharaController::class, 'index'])->name('home');

    Route::get('/data-siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::post('/data-siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/data-siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::post('/data-siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    Route::get('/data-siswa/{siswa}/cetak', [SiswaController::class, 'cetak'])->name('siswa.cetak');

    Route::get('/item-pembayaran', [ItemPembayaranController::class, 'index'])->name('item.index');
    Route::post('/item-pembayaran', [ItemPembayaranController::class, 'store'])->name('item.store');

    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
    Route::post('/tagihan/{tagihan}/potongan', [TagihanController::class, 'tambahPotongan'])->name('tagihan.potongan.store');

    Route::get('/transaksi-pembayaran', [PembayaranTagihanController::class, 'index'])->name('pembayaran.index');
    Route::post('/transaksi-pembayaran/{tagihan}', [PembayaranTagihanController::class, 'store'])->name('pembayaran.store');

    Route::post('/transaksi', [BendaharaController::class, 'store'])->name('transaksi.store');
    Route::delete('/transaksi/{id}', [BendaharaController::class, 'destroy'])->name('transaksi.destroy');
    Route::get('/riwayat-hapus', [BendaharaController::class, 'riwayat'])->name('transaksi.riwayat');
    Route::post('/riwayat-hapus/{id}/restore', [BendaharaController::class, 'restore'])->name('transaksi.restore');
    Route::get('/cetak/nota/{id}', [BendaharaController::class, 'cetakNota'])->name('cetak.nota');

    Route::get('/tanggungan', [SiswaController::class, 'tanggungan'])->name('tanggungan.index');
    Route::post('/tanggungan/bayar-semua', [SiswaController::class, 'bayarSemua'])->name('tanggungan.bayar-semua');

    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('/backup/database', [BackupController::class, 'downloadJson'])->name('backup.database');
});
