<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\ProfileController;
// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // Redirect Root Route
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Staff/Admin Routes
    Route::middleware(['role:admin,guru'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Kelas & Guru CRUD (Admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('kelas', KelasController::class)->except(['create', 'show', 'edit']);
            Route::resource('guru', GuruController::class)->except(['show']);
        });

        // Kelas Saya (Guru only)
        Route::middleware(['role:guru'])->group(function () {
            Route::get('/kelas-saya', [KelasController::class, 'saya'])->name('kelas.saya');
        });

        // Siswa CRUD
        Route::get('/siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
        Route::post('/siswa/import', [SiswaController::class, 'importStore'])->name('siswa.import.store');
        Route::get('/siswa/import/template', [SiswaController::class, 'importTemplate'])->name('siswa.import.template');
        Route::resource('siswa', SiswaController::class)->except(['show']);

        // Transaksi Resource
        Route::get('/transaksi/kolektif', [TransaksiController::class, 'kolektifForm'])->name('transaksi.kolektif.form');
        Route::post('/transaksi/kolektif', [TransaksiController::class, 'kolektifStore'])->name('transaksi.kolektif.store');
        Route::resource('transaksi', TransaksiController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('/transaksi/{transaksi}/void', [TransaksiController::class, 'void'])->name('transaksi.void');
    });

});
