<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AkunPangkalanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\TransaksiGasController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');
});

// Authenticated but unverified can see this
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return inertia('Auth/VerifyEmail');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/dashboard')->with('success', 'Email berhasil diverifikasi! Masa Trial 7 Hari Anda sudah aktif.');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Web Push Subscriptions
    Route::post('/push-subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push-unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

// Authenticated and Verified Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Billing
    Route::get('/', function () {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/langganan', function () {
        return inertia('Billing/Index');
    })->name('billing');

    Route::get('/panduan', function () {
        return inertia('Panduan');
    })->name('panduan');

    // Akun Pangkalan (CRUD) - Butuh Langganan Aktif
    Route::middleware('subscribed')->prefix('akun-pangkalan')->name('akun-pangkalan.')->group(function () {
        Route::get('/', [AkunPangkalanController::class, 'index'])->name('index');
        Route::post('/', [AkunPangkalanController::class, 'store'])->name('store');
        Route::put('/{akunPangkalan}', [AkunPangkalanController::class, 'update'])->name('update');
        Route::delete('/{akunPangkalan}', [AkunPangkalanController::class, 'destroy'])->name('destroy');
    });

    // Pelanggan (CRUD & Import) - Butuh Langganan Aktif
    Route::middleware('subscribed')->prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('index');
        Route::post('/', [PelangganController::class, 'store'])->name('store');
        Route::put('/{pelanggan}', [PelangganController::class, 'update'])->name('update');
        Route::delete('/{pelanggan}', [PelangganController::class, 'destroy'])->name('destroy');
        Route::post('/{pelanggan}/toggle-status', [PelangganController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/import', [PelangganController::class, 'import'])->name('import');
    });

    // Transaksi Gas (CRUD & Actions) - Butuh Langganan Aktif
    Route::middleware('subscribed')->prefix('transaksi-gas')->name('transaksi-gas.')->group(function () {
        Route::get('/', [TransaksiGasController::class, 'index'])->name('index');
        Route::get('/search-nik', [TransaksiGasController::class, 'searchNik'])->name('search-nik');
        Route::post('/', [TransaksiGasController::class, 'store'])->name('store');
        Route::put('/{transaksiGas}', [TransaksiGasController::class, 'update'])->name('update');
        Route::delete('/{transaksiGas}', [TransaksiGasController::class, 'destroy'])->name('destroy');
        Route::post('/{transaksiGas}/lapor', [TransaksiGasController::class, 'tandaiLapor'])->name('tandai-lapor');
        Route::post('/lapor-massal', [TransaksiGasController::class, 'tandaiLaporMassal'])->name('tandai-lapor-massal');
        Route::post('/trigger-rpa', [TransaksiGasController::class, 'triggerRpa'])->middleware('throttle:5,1')->name('trigger-rpa');
        Route::post('/audit-quota', [TransaksiGasController::class, 'auditQuota'])->middleware('throttle:5,1')->name('audit-quota');
        Route::post('/reset-sync', [TransaksiGasController::class, 'resetSync'])->middleware('throttle:5,1')->name('reset-sync');
        Route::post('/distribusi-otomatis', [TransaksiGasController::class, 'distribusiOtomatis'])->middleware('throttle:10,1')->name('distribusi-otomatis');
    });
    // Super Admin: Manajemen Agen/User & Monitoring
    Route::middleware('super_admin')->prefix('superadmin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
        Route::get('/logs', [\App\Http\Controllers\SuperAdminController::class, 'logs'])->name('superadmin.logs');
        Route::get('/logs/location/{ip}', [\App\Http\Controllers\SuperAdminController::class, 'getLocation'])->name('superadmin.logs.location');
        
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('store');
            Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
            Route::post('/{user}/renew', [\App\Http\Controllers\UserController::class, 'quickRenew'])->name('renew');
            Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
        });
    });
});

// Internal API / badges compatibility
Route::get('/api/internal/badge-transaksi', function () {
    if (!Auth::check()) return response()->json(['count' => 0]);
    $count = \App\Models\TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
        ->whereYear('tanggal_transaksi', now()->year)
        ->whereHas('akunPangkalan', function ($q) {
            $q->where('user_id', Auth::id());
        })
        ->count();
    return response()->json(['count' => $count]);
})->middleware(['web', 'auth']);
