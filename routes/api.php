<?php

use App\Http\Controllers\AlkesController;
use App\Models\Alkes;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Route;

Route::get('/ruangan', function () {
    return response()->json(Ruangan::all());
});

Route::match(['get', 'post'], '/alkes', [AlkesController::class, 'apiHandler'])->name('api.alkes.handler');
Route::match(['get', 'post'], '/alkes/sync', [AlkesController::class, 'apiSync'])->name('api.alkes.sync');
Route::match(['get', 'post'], '/sheets-data', [AlkesController::class, 'apiSheetsData'])->name('api.sheets.data');
Route::match(['get', 'post'], '/pemeliharaan-data', [AlkesController::class, 'apiPemeliharaan'])->name('api.pemeliharaan.data');
Route::match(['get', 'post'], '/kalibrasi-data', [AlkesController::class, 'apiKalibrasi'])->name('api.kalibrasi.data');

Route::get('/alkes/{id}', function ($id) {
    return response()->json(Alkes::with(['nomenklatur', 'ruangan', 'lokasiRuangan'])->find($id));
});
