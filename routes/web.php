<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AkunPangkalanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\TransaksiGasController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', function () {
        if (auth()->check() && auth()->user()->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Akun Pangkalan (CRUD)
    Route::prefix('akun-pangkalan')->name('akun-pangkalan.')->group(function () {
        Route::get('/', [AkunPangkalanController::class, 'index'])->name('index');
        Route::post('/', [AkunPangkalanController::class, 'store'])->name('store');
        Route::put('/{akunPangkalan}', [AkunPangkalanController::class, 'update'])->name('update');
        Route::delete('/{akunPangkalan}', [AkunPangkalanController::class, 'destroy'])->name('destroy');
    });

    // Pelanggan (CRUD & Import)
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('index');
        Route::post('/', [PelangganController::class, 'store'])->name('store');
        Route::put('/{pelanggan}', [PelangganController::class, 'update'])->name('update');
        Route::delete('/{pelanggan}', [PelangganController::class, 'destroy'])->name('destroy');
        Route::post('/{pelanggan}/toggle-status', [PelangganController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/import', [PelangganController::class, 'import'])->name('import');
    });

    // Transaksi Gas (CRUD & Actions)
    Route::prefix('transaksi-gas')->name('transaksi-gas.')->group(function () {
        Route::get('/', [TransaksiGasController::class, 'index'])->name('index');
        Route::get('/search-nik', [TransaksiGasController::class, 'searchNik'])->name('search-nik');
        Route::post('/', [TransaksiGasController::class, 'store'])->name('store');
        Route::put('/{transaksiGas}', [TransaksiGasController::class, 'update'])->name('update');
        Route::delete('/{transaksiGas}', [TransaksiGasController::class, 'destroy'])->name('destroy');
        Route::post('/{transaksiGas}/lapor', [TransaksiGasController::class, 'tandaiLapor'])->name('tandai-lapor');
        Route::post('/lapor-massal', [TransaksiGasController::class, 'tandaiLaporMassal'])->name('tandai-lapor-massal');
        Route::post('/trigger-rpa', [TransaksiGasController::class, 'triggerRpa'])->name('trigger-rpa');
        Route::post('/audit-quota', [TransaksiGasController::class, 'auditQuota'])->name('audit-quota');
        Route::post('/reset-sync', [TransaksiGasController::class, 'resetSync'])->name('reset-sync');
        Route::post('/distribusi-otomatis', [TransaksiGasController::class, 'distribusiOtomatis'])->name('distribusi-otomatis');
    });
    // Super Admin: Manajemen Agen/User & Monitoring
    Route::middleware('super_admin')->prefix('superadmin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
        
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('store');
            Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
        });
    });
});

// Internal API / badges compatibility
Route::get('/api/internal/badge-transaksi', function () {
    if (!auth()->check()) return response()->json(['count' => 0]);
    $count = \App\Models\TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
        ->whereYear('tanggal_transaksi', now()->year)
        ->whereHas('akunPangkalan', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->count();
    return response()->json(['count' => $count]);
})->middleware(['web', 'auth']);
