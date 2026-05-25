<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$totalTxs = App\Models\TransaksiGas::count();
$invalidPelanggan = App\Models\TransaksiGas::whereNotExists(function ($query) {
    $query->select(DB::raw(1))
          ->from('pelanggan')
          ->whereRaw('transaksi_gas.pelanggan_id = pelanggan.id');
})->count();

$inactivePelanggan = App\Models\TransaksiGas::whereExists(function ($query) {
    $query->select(DB::raw(1))
          ->from('pelanggan')
          ->whereRaw('transaksi_gas.pelanggan_id = pelanggan.id')
          ->where('status', false);
})->count();

echo "Total Transactions: {$totalTxs}\n";
echo "Transactions pointing to non-existent pelanggan: {$invalidPelanggan}\n";
echo "Transactions pointing to inactive pelanggan: {$inactivePelanggan}\n";

$someTxs = App\Models\TransaksiGas::with('pelanggan')->take(5)->get();
foreach ($someTxs as $t) {
    echo "TX ID: {$t->id}, Pelanggan ID: {$t->pelanggan_id}, Pelanggan NIK: " . ($t->pelanggan ? $t->pelanggan->nik : 'NULL') . "\n";
}
