<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\ForgotPasswordController;

/*
|--------------------------------------------------------------------------
| ZONA PUBLIK (Tanpa Login)
| Semua orang bisa akses halaman ini
|--------------------------------------------------------------------------
*/

// 1. Login Standar
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2. Fitur Lupa Password (DI LUAR middleware auth!)
// Kita pindahkan ke sini agar bisa diklik oleh siapa saja
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode'])->name('password.send');

Route::get('/verify-code', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
Route::post('/verify-code', [ForgotPasswordController::class, 'verifyCode'])->name('password.check');

Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');


/*
|--------------------------------------------------------------------------
| ZONA PRIVAT (Wajib Login)
| Hanya user yang sudah login yang bisa akses halaman ini
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/', [BendaharaController::class, 'index'])->name('home');

    Route::post('/transaksi', [BendaharaController::class, 'store'])->name('transaksi.store');
    Route::delete('/transaksi/{id}', [BendaharaController::class, 'destroy'])->name('transaksi.destroy');

    Route::get('/laporan/sekolah', [BendaharaController::class, 'laporanSekolah'])->name('laporan.sekolah');
    Route::get('/laporan/wali', [BendaharaController::class, 'laporanWali'])->name('laporan.wali');
    Route::get('/laporan/yayasan', [BendaharaController::class, 'laporanYayasan'])->name('laporan.yayasan');
    Route::get('/cetak/nota/{id}', [BendaharaController::class, 'cetakNota'])->name('cetak.nota');
});
