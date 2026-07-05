<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiLpgController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php
| which assigns the "api" middleware group.
|
*/

// Endpoint RPA dengan Keamanan API Key
Route::middleware('api.key')->group(function () {
    // Menyajikan daftar pelanggan yang transaksinya siap/perlu dilaporkan ke Pertamina
    Route::get('/nik-siap-lapor', [ApiLpgController::class, 'nikSiapLapor']);
    
    // Detail transaksi siap lapor per baris transaksi
    Route::get('/nik-siap-lapor/detail', [ApiLpgController::class, 'nikSiapLaporDetail']);
    
    // Konfirmasi bahwa transaksi sukses diinput ke MAP Pertamina
    Route::post('/konfirmasi-lapor/{id}', [ApiLpgController::class, 'konfirmasiLapor']);
    
    // Konfirmasi bahwa transaksi gagal diinput ke MAP Pertamina (misal limit habis)
    Route::post('/gagal-lapor/{id}', [ApiLpgController::class, 'gagalLapor']);
    
    // Konfirmasi massal sukses input
    Route::post('/konfirmasi-lapor/bulk', [ApiLpgController::class, 'konfirmasiLaporBulk']);
    
    // Rekap kuota semua pelanggan aktif bulan berjalan
    Route::get('/rekap-kuota', [ApiLpgController::class, 'rekapKuota']);

    // Manajemen Sinkronisasi Manual RPA Bot
    Route::get('/rpa-sync/pending', [ApiLpgController::class, 'getPendingSync']);
    Route::get('/rpa-sync/{id}', [ApiLpgController::class, 'getSyncStatus']);
    Route::patch('/rpa-sync/{id}', [ApiLpgController::class, 'updateSyncStatus']);
    Route::post('/rpa-sync/stok-kosong', [ApiLpgController::class, 'handleStokKosong']);

    Route::get('/akun-pangkalan/aktif', [ApiLpgController::class, 'allAkunPangkalan']);

    // Pencocokan kuota audit (Fitur Cek Quota Pertamina)
    Route::get('/all-pelanggan-aktif', [ApiLpgController::class, 'allPelangganAktif']);
    Route::post('/pelanggan/sync-quota', [ApiLpgController::class, 'syncQuota']);

    // Notifikasi progress audit real-time dari bot
    Route::post('/audit/progress', [ApiLpgController::class, 'auditProgress']);
    Route::post('/audit/status', [ApiLpgController::class, 'auditStatus']);
    Route::post('/audit/heartbeat', [ApiLpgController::class, 'auditHeartbeat']);
});
