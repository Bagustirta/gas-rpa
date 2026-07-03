<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TransaksiGas;
use App\Models\AkunPangkalan;
use App\Models\Pelanggan;
use App\Models\RpaSync;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        // Kumpulkan data statistik global untuk super admin
        $totalUsers = User::where('role', 'agen')->count();
        $totalPangkalan = AkunPangkalan::count();
        $totalPelanggan = Pelanggan::count();
        $totalTransaksiHariIni = TransaksiGas::whereDate('tanggal_transaksi', today())->count();
        $totalTransaksiBulanIni = TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
                                            ->whereYear('tanggal_transaksi', now()->year)
                                            ->count();

        // Ambil 10 aktivitas transaksi terakhir secara global
        $recentTransactions = TransaksiGas::with(['pelanggan:id,nik', 'akunPangkalan:id,nama_pangkalan,user_id', 'akunPangkalan.user:id,name'])
                                          ->latest('updated_at')
                                          ->take(8)
                                          ->get()
                                          ->map(function($t) {
                                              return [
                                                  'id' => $t->id,
                                                  'nik' => $t->pelanggan ? $t->pelanggan->nik : '-',
                                                  'nama_pelanggan' => 'Warga',
                                                  'pangkalan' => $t->akunPangkalan ? $t->akunPangkalan->nama_pangkalan : '-',
                                                  'agen' => ($t->akunPangkalan && $t->akunPangkalan->user) ? $t->akunPangkalan->user->name : '-',
                                                  'status' => $t->status_lapor,
                                                  'waktu' => $t->updated_at->diffForHumans()
                                              ];
                                          });

        // Ambil status bot RPA
        $activeBots = RpaSync::where('status', 'processing')->count();
        $totalRpaJobs = RpaSync::whereDate('created_at', today())->count();

        // Bisa ditambahkan logika CPU / RAM monitoring jika menggunakan shell_exec() di Linux
        // Namun karena ini Windows/Laragon, kita fokus ke statistik aplikasi (Aman & Ringan)
        $serverStatus = [
            'status' => 'Online',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'active_bots' => $activeBots,
            'today_rpa_jobs' => $totalRpaJobs,
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalPangkalan' => $totalPangkalan,
                'totalPelanggan' => $totalPelanggan,
                'totalTransaksiHariIni' => $totalTransaksiHariIni,
                'totalTransaksiBulanIni' => $totalTransaksiBulanIni,
            ],
            'serverStatus' => $serverStatus,
            'recentTransactions' => $recentTransactions
        ]);
    }
}
