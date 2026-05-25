<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/api/internal/badge-transaksi', function () {
    if (!auth()->check()) return response()->json(['count' => 0]);
    $count = \App\Models\TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
        ->whereYear('tanggal_transaksi', now()->year)
        ->whereHas('akunPangkalan', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->count();
    return response()->json(['count' => $count]);
})->middleware('web');
