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

        // Ambil penggunaan memori bot (Node.js & Chrome) jika di Windows
        $botMemory = 0;
        try {
                $outputNode = shell_exec('tasklist /FI "IMAGENAME eq node.exe" /FO CSV /NH 2>NUL');
                
                $lines = explode("\n", trim($outputNode));
                foreach ($lines as $line) {
                    if (strpos($line, 'node.exe') !== false) {
                        $parts = str_getcsv($line);
                        if (isset($parts[4])) {
                            $memKb = (int) str_replace([' ', ',', '.', 'K', 'k', '"'], '', $parts[4]);
                            $botMemory += $memKb;
                        }
                    }
                }

                // KHUSUS CHROME: Ambil HANYA yang 'headless' (Bot) via WMIC, abaikan Chrome pribadi user
                $outputChrome = shell_exec('wmic process where "name=\'chrome.exe\' and CommandLine like \'%--headless%\'" get WorkingSetSize 2>NUL');
                if ($outputChrome) {
                    preg_match_all('/\d+/', $outputChrome, $matches);
                    foreach ($matches[0] as $bytes) {
                        $botMemory += round((int)$bytes / 1024); // Convert Bytes to KB
                    }
                }
        } catch (\Exception $e) {
            // Abaikan jika command gagal
        }

        $serverStatus = [
            'status' => 'Online',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'bot_memory_usage' => round($botMemory / 1024, 2),
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

    public function logs(Request $request)
    {
        $query = \App\Models\ActivityLog::with('user:id,name,email')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString()->through(fn($log) => [
            'id' => $log->id,
            'user' => $log->user ? $log->user->name . ' (' . $log->user->email . ')' : 'System / Guest',
            'action' => $log->action,
            'description' => $log->description,
            'ip_address' => $log->ip_address,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            'time_ago' => $log->created_at->diffForHumans(),
        ]);

        return inertia(implode('/', ['SuperAdmin', 'Logs']), [
            'logs' => $logs,
            'filters' => $request->only('search'),
        ]);
    }

    public function getLocation(string $ip)
    {
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.')) {
            return response()->json(['error' => 'IP Lokal'], 400);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
            
            if ($response->successful() && $response->json('status') === 'success') {
                return response()->json([
                    'city' => $response->json('city'),
                    'regionName' => $response->json('regionName'),
                    'country' => $response->json('country'),
                ]);
            }
            
            return response()->json(['error' => 'Lokasi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghubungi server pelacakan'], 500);
        }
    }
}
