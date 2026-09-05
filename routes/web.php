<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AlkesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KalibrasiController;
use App\Http\Controllers\LogPemeliharaanController;
use App\Http\Controllers\MutasiAlkesController;
use App\Http\Controllers\PeminjamanAlkesController;
use App\Http\Controllers\RuanganController;
use App\Http\Middleware\EnsureSessionRole;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware([EnsureSessionRole::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('alkes', [AlkesController::class, 'index'])->name('alkes.index');
    Route::get('alkes/{alkes}', [AlkesController::class, 'show'])->name('alkes.show');

    Route::middleware(['role:elektromedis'])->group(function () {
        Route::get('alkes/create', [AlkesController::class, 'create'])->name('alkes.create');
        Route::post('alkes', [AlkesController::class, 'store'])->name('alkes.store');
        Route::get('alkes/{alkes}/edit', [AlkesController::class, 'edit'])->name('alkes.edit');
        Route::put('alkes/{alkes}', [AlkesController::class, 'update'])->name('alkes.update');
        Route::delete('alkes/{alkes}', [AlkesController::class, 'destroy'])->name('alkes.destroy');
    });

    Route::get('mutasi', [MutasiAlkesController::class, 'index'])->name('mutasi.index');
    Route::get('mutasi/buat', [MutasiAlkesController::class, 'create'])->name('mutasi.create');
    Route::post('mutasi', [MutasiAlkesController::class, 'store'])->name('mutasi.store');

    Route::get('peminjaman', [PeminjamanAlkesController::class, 'index'])->name('peminjaman.index');
    Route::post('peminjaman', [PeminjamanAlkesController::class, 'store'])->name('peminjaman.store');
    Route::post('peminjaman/{id}/kembalikan', [PeminjamanAlkesController::class, 'kembalikan'])->name('peminjaman.kembalikan');

    Route::get('pemeliharaan', [LogPemeliharaanController::class, 'index'])->name('pemeliharaan.index');
    Route::get('pemeliharaan/buat', [LogPemeliharaanController::class, 'create'])->name('pemeliharaan.create');
    Route::post('pemeliharaan', [LogPemeliharaanController::class, 'store'])->name('pemeliharaan.store');
    Route::post('pemeliharaan/{id}/selesai', [LogPemeliharaanController::class, 'resolve'])->name('pemeliharaan.resolve');

    Route::get('kalibrasi', [KalibrasiController::class, 'index'])->name('kalibrasi.index');
    Route::post('kalibrasi/{id}', [KalibrasiController::class, 'update'])->middleware('role:elektromedis')->name('kalibrasi.update');
    Route::get('database/sertifikat/{filename}', [KalibrasiController::class, 'serveCertificate'])->name('sertifikat.show');

    Route::get('ruangan', [RuanganController::class, 'index'])->name('ruangan.index');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::post('notifications/read-all', [LogPemeliharaanController::class, 'markNotificationsRead'])->name('notifications.read-all');
});
