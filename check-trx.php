<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TransaksiGas;
use Carbon\Carbon;

$belumLapor = TransaksiGas::where('status_lapor', 'belum_lapor')->count();
$hariIni    = TransaksiGas::whereDate('tanggal_transaksi', Carbon::today())->count();
$bulanIni   = TransaksiGas::whereMonth('tanggal_transaksi', now()->month)->count();
$sample     = TransaksiGas::where('status_lapor', 'belum_lapor')
                ->limit(5)->get(['id','tanggal_transaksi','status_lapor','created_at']);

echo "=== CEK TRANSAKSI ===\n";
echo "Today                  : " . Carbon::today()->toDateString() . "\n";
echo "Belum lapor (total)    : $belumLapor\n";
echo "Tanggal hari ini       : $hariIni\n";
echo "Bulan ini (semua)      : $bulanIni\n\n";
echo "Sample transaksi belum_lapor (5 pertama):\n";
foreach ($sample as $t) {
    echo "  ID:{$t->id} | tanggal:{$t->tanggal_transaksi} | created:{$t->created_at}\n";
}

// Cek pending RPA sync
$sync = \App\Models\RpaSync::whereIn('status', ['pending','processing'])->latest()->first();
echo "\nRPA Sync aktif: " . ($sync ? "ID#{$sync->id} status={$sync->status}" : "TIDAK ADA") . "\n";
