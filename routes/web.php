<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Pegawai\AssetController as PegawaiAssetController;
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboardController;
use App\Http\Controllers\Pegawai\LoanController as PegawaiLoanController;
use App\Http\Controllers\Pegawai\ProfileController as PegawaiProfileController;
use App\Http\Controllers\Pegawai\ReturnController as PegawaiReturnController;
use App\Support\DashboardRedirector;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(DashboardRedirector::routeNameFor(auth()->user()))
        : redirect()->route('login');
})->name('dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/kategori/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/kategori/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/lokasi', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/lokasi/create', [LocationController::class, 'create'])->name('locations.create');
    Route::post('/lokasi', [LocationController::class, 'store'])->name('locations.store');
    Route::get('/lokasi/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
    Route::put('/lokasi/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/lokasi/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    Route::get('/aset', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/aset/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('/aset', [AssetController::class, 'store'])->name('assets.store');
    Route::get('/aset/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/aset/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/aset/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

    Route::get('/pegawai', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/pegawai/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/pegawai', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/pegawai/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/pegawai/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/pegawai/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/peminjaman', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/peminjaman/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/peminjaman', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/peminjaman/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    Route::put('/peminjaman/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::delete('/peminjaman/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

    Route::get('/pengembalian', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/pengembalian/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/pengembalian', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('/pengembalian/{return}/edit', [ReturnController::class, 'edit'])->name('returns.edit');
    Route::put('/pengembalian/{return}', [ReturnController::class, 'update'])->name('returns.update');
    Route::delete('/pengembalian/{return}', [ReturnController::class, 'destroy'])->name('returns.destroy');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/', [PegawaiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/aset', [PegawaiAssetController::class, 'index'])->name('assets.index');
    Route::get('/peminjaman', [PegawaiLoanController::class, 'index'])->name('loans.index');
    Route::get('/pengembalian', [PegawaiReturnController::class, 'index'])->name('returns.index');
    Route::get('/profile', [PegawaiProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [PegawaiProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [PegawaiProfileController::class, 'updatePassword'])->name('profile.password.update');
});

require __DIR__.'/auth.php';
