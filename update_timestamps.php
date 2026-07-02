<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transaksis = App\Models\TransaksiGas::whereTime('tanggal_transaksi', '00:00:00')->get();
$count = 0;
foreach($transaksis as $trx) {
    $date = \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('Y-m-d');
    $time = \Carbon\Carbon::parse($trx->created_at)->format('H:i:s');
    $trx->tanggal_transaksi = $date . ' ' . $time;
    $trx->save();
    $count++;
}
echo "Berhasil mengupdate {$count} transaksi sebelumnya!\n";
