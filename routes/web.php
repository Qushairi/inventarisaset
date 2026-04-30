<?php

use App\Http\Controllers\Admin\AdminPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin')->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/kategori', [AdminPageController::class, 'categories'])->name('categories.index');
    Route::get('/lokasi', [AdminPageController::class, 'locations'])->name('locations.index');
    Route::get('/aset', [AdminPageController::class, 'assets'])->name('assets.index');
    Route::get('/pegawai', [AdminPageController::class, 'employees'])->name('employees.index');
    Route::get('/peminjaman', [AdminPageController::class, 'loans'])->name('loans.index');
    Route::get('/pengembalian', [AdminPageController::class, 'returns'])->name('returns.index');
    Route::get('/laporan', [AdminPageController::class, 'reports'])->name('reports.index');
});
