<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DoaController;
use App\Http\Controllers\DoaTersimpanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BerandaController::class, 'index'])->name('beranda');

// /doa/acak harus didefinisikan sebelum /doa/{doa:slug}
Route::get('/doa/acak', [DoaController::class, 'acak'])->name('doa.acak');
Route::get('/doa/{doa:slug}', [DoaController::class, 'show'])->name('doa.show');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/daftar', [AuthController::class, 'formRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/tersimpan', [DoaTersimpanController::class, 'index'])->name('tersimpan');
    Route::post('/doa/{doa:slug}/simpan', [DoaTersimpanController::class, 'simpan'])->name('doa.simpan');
    Route::delete('/doa/{doa:slug}/simpan', [DoaTersimpanController::class, 'hapus'])->name('doa.hapus');
});
