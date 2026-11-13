<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use App\Models\AkunPangkalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class TransaksiGasController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $query = TransaksiGas::whereHas('akunPangkalan', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['pelanggan', 'akunPangkalan']);

        if ($request->filled('search')) {
            $query->whereHas('pelanggan', function ($q) use ($request) {
                // Pencarian sekarang membutuhkan NIK persis sama (karena terenkripsi)
                $q->where('nik_bindex', hash_hmac('sha256', $request->search, config('app.key')));
            });
        }

        if ($request->filled('status_lapor')) {
            $query->where('status_lapor', $request->status_lapor);
        }

        if ($request->filled('akun_pangkalan_id')) {
            $query->where('akun_pangkalan_id', $request->akun_pangkalan_id);
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_transaksi', [
                Carbon::parse($request->tanggal_mulai)->startOfDay(),
                Carbon::parse($request->tanggal_selesai)->endOfDay()
            ]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_transaksi', '>=', $request->tanggal_mulai);
        } elseif ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_transaksi', '<=', $request->tanggal_selesai);
        } elseif ($request->filled('tanggal')) {
            $query->whereDate('tanggal_transaksi', $request->tanggal);
        }

        $transaksis = $query->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString()
            ->through(fn($item) => [
                'id' => $item->id,
                'pelanggan_id' => $item->pelanggan_id,
                'pelanggan_nik' => $item->pelanggan?->nik,
                'pelanggan_status' => (bool)$item->pelanggan?->status,
                'akun_pangkalan_id' => $item->akun_pangkalan_id,
                'nama_pangkalan' => $item->akunPangkalan?->nama_pangkalan,
                'jumlah_tabung' => $item->jumlah_tabung,
                'tanggal_transaksi' => $item->tanggal_transaksi->format('Y-m-d H:i'),
                'status_lapor' => $item->status_lapor,
                'catatan_lapor' => $item->catatan_lapor,
                'waktu_dilaporkan' => $item->waktu_dilaporkan?->format('Y-m-d H:i'),
                'dilaporkan_pertamina' => (bool)$item->dilaporkan_pertamina,
            ]);

        $pangkalans = AkunPangkalan::where('user_id', $userId)->orderBy('nama_pangkalan')->get();

        $activeSyncModel = \App\Models\RpaSync::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->first();
            
        $activeSync = $activeSyncModel ? true : false;

        $rpaSummary = [
            'total' => 0,
            'sukses' => 0,
            'belum_lapor' => 0,
            'gagal' => 0,
            'current_pangkalan' => null,
        ];

        if ($activeSyncModel) {
            $rpaSummary = $activeSyncModel->calculateSummary();
        }

        $syncHistory = \App\Models\RpaSync::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($sync) {
                return [
                    'id' => $sync->id,
                    'status' => $sync->status,
                    'message' => $sync->message,
                    'total_records' => $sync->total_records,
                    'success_records' => $sync->success_records,
                    'failed_records' => $sync->failed_records,
                    'limit_records' => (int) $sync->limit_records,
                    'created_at' => $sync->created_at->format('d M Y, H:i'),
                    'completed_at' => $sync->completed_at ? $sync->completed_at->format('d M Y, H:i') : null,
                ];
            });

        $auditProgress = \App\Models\AuditProgress::where('user_id', $userId)->first();
        $auditProgressData = null;
        $activeAudit = false;

        if ($auditProgress) {
            $isRunning = (bool) $auditProgress->is_running;

            if ($isRunning && $auditProgress->updated_at && $auditProgress->updated_at->diffInSeconds(now()) > 30) {
                $auditProgress->update([
                    'is_running'  => false,
                    'finished_at' => now(),
                    'step'        => 'error',
                    'step_label'  => 'Audit terhenti (Terminal ditutup)',
                ]);
                $isRunning = false;
            }

            $activeAudit = $isRunning;

            if ($isRunning) {
                $auditProgressData = [
                    'step'            => $auditProgress->step,
                    'step_label'      => $auditProgress->step_label,
                    'pangkalan_name'  => $auditProgress->pangkalan_name,
                    'pangkalan_index' => (int) $auditProgress->pangkalan_index,
                    'pangkalan_total' => (int) $auditProgress->pangkalan_total,
                    'nik_current'     => $auditProgress->nik_current,
                    'nik_total'       => (int) $auditProgress->nik_total,
                    'is_running'      => $isRunning,
                    'finished_at'     => $auditProgress->finished_at,
                ];
            }
        }

        return Inertia::render('TransaksiGas/Index', [
            'transaksis' => $transaksis,
            'pangkalans' => $pangkalans,
            'filters' => $request->only(['search', 'status_lapor', 'akun_pangkalan_id', 'tanggal', 'tanggal_mulai', 'tanggal_selesai']),
            'activeSync' => $activeSync,
            'activeAudit' => $activeAudit,
            'auditProgress' => $auditProgressData,
            'rpaSummary' => $rpaSummary,
            'syncHistory' => $syncHistory,
        ]);
    }

    public function searchNik(Request $request)
    {
        $nik = $request->nik;
        $pelanggan = Pelanggan::where('user_id', Auth::id())
            ->where('nik_bindex', hash_hmac('sha256', $nik, config('app.key')))
            ->where('status', true)
            ->first();

        if ($pelanggan) {
            $terpakai = $pelanggan->totalTabungBulanIni();
            return response()->json([
                'found' => true,
                'pelanggan_id' => $pelanggan->id,
                'nik' => $pelanggan->nik,
                'total_tabung_bulan_ini' => $terpakai,
                'sisa_kuota' => max(0, 4 - $terpakai)
            ]);
        }

        return response()->json([
            'found' => false
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'pelanggan_id' => ['required', 'exists:pelanggan,id'],
            'akun_pangkalan_id' => ['required', 'exists:akun_pangkalans,id'],
            'jumlah_tabung' => ['required', 'integer', 'min:1', 'max:4'],
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:now'],
        ]);

        $pelanggan = Pelanggan::where('user_id', $userId)->find($validated['pelanggan_id']);
        if (!$pelanggan || !$pelanggan->status) {
            return back()->withErrors(['pelanggan_id' => 'Pelanggan tidak valid atau tidak aktif.']);
        }

        // Limit Checks
        $date = Carbon::parse($validated['tanggal_transaksi']);
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1. Weekly Limit (lintas semua pangkalan — 1 tabung/minggu)
        $weeklySum = TransaksiGas::where('pelanggan_id', $pelanggan->id)
            ->whereBetween('tanggal_transaksi', [$startOfWeek, $endOfWeek])
            ->whereIn('status_lapor', ['sukses', 'kuota_habis', 'belum_lapor'])
            ->sum('jumlah_tabung');

        if (($weeklySum + $validated['jumlah_tabung']) > 1) {
            return back()->withErrors([
                'jumlah_tabung' => "Pelanggan ini sudah membeli {$weeklySum} tabung pada minggu ini (di semua pangkalan). Batas mingguan adalah 1 tabung."
            ]);
        }

        // 2. Monthly Limit overall
        $monthlySum = TransaksiGas::where('pelanggan_id', $pelanggan->id)
            ->whereBetween('tanggal_transaksi', [$startOfMonth, $endOfMonth])
            ->whereIn('status_lapor', ['sukses', 'kuota_habis', 'belum_lapor'])
            ->sum('jumlah_tabung');

        if (($monthlySum + $validated['jumlah_tabung']) > 4) {
            return back()->withErrors([
                'jumlah_tabung' => "Pelanggan ini sudah membeli {$monthlySum} tabung pada bulan ini secara keseluruhan. Batas bulanan adalah 4 tabung."
            ]);
        }

        $validated['tanggal_transaksi'] = Carbon::parse($validated['tanggal_transaksi'])->setTimeFromTimeString(now()->toTimeString());

        TransaksiGas::create([
            ...$validated,
            'status_lapor' => 'belum_lapor',
            'dilaporkan_pertamina' => false,
        ]);

        try {
            event(new \App\Events\DataGasUpdated($userId));
            \App\Http\Controllers\SuperAdminController::broadcastDashboard();
        } catch (\Exception $e) {}

        return redirect()->route('transaksi-gas.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(Request $request, TransaksiGas $transaksiGas)
    {
        $userId = Auth::id();
        
        // Ensure authorization
        $pangkalan = AkunPangkalan::where('user_id', $userId)->find($transaksiGas->akun_pangkalan_id);
        if (!$pangkalan) {
            abort(403);
        }

        $validated = $request->validate([
            'pelanggan_id' => ['required', 'exists:pelanggan,id'],
            'akun_pangkalan_id' => ['required', 'exists:akun_pangkalans,id'],
            'jumlah_tabung' => ['required', 'integer', 'min:1', 'max:4'],
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:now'],
        ]);

        // Limit Checks
        $date = Carbon::parse($validated['tanggal_transaksi']);
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1. Weekly Limit (lintas semua pangkalan — 1 tabung/minggu)
        $weeklySum = TransaksiGas::where('pelanggan_id', $validated['pelanggan_id'])
            ->where('id', '!=', $transaksiGas->id)
            ->whereBetween('tanggal_transaksi', [$startOfWeek, $endOfWeek])
            ->whereIn('status_lapor', ['sukses', 'kuota_habis', 'belum_lapor'])
            ->sum('jumlah_tabung');

        if (($weeklySum + $validated['jumlah_tabung']) > 1) {
            return back()->withErrors([
                'jumlah_tabung' => "Pelanggan ini sudah membeli {$weeklySum} tabung pada minggu ini (di semua pangkalan). Batas mingguan adalah 1 tabung."
            ]);
        }

        // 2. Monthly Limit
        $monthlySum = TransaksiGas::where('pelanggan_id', $validated['pelanggan_id'])
            ->where('id', '!=', $transaksiGas->id)
            ->whereBetween('tanggal_transaksi', [$startOfMonth, $endOfMonth])
            ->whereIn('status_lapor', ['sukses', 'kuota_habis', 'belum_lapor'])
            ->sum('jumlah_tabung');

        if (($monthlySum + $validated['jumlah_tabung']) > 4) {
            return back()->withErrors([
                'jumlah_tabung' => "Pelanggan ini sudah membeli {$monthlySum} tabung pada bulan ini secara keseluruhan. Batas bulanan adalah 4 tabung."
            ]);
        }

        $validated['tanggal_transaksi'] = Carbon::parse($validated['tanggal_transaksi'])->setTimeFromTimeString(now()->toTimeString());

        $transaksiGas->update($validated);

        return redirect()->route('transaksi-gas.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(TransaksiGas $transaksiGas)
    {
        $userId = Auth::id();
        $pangkalan = AkunPangkalan::where('user_id', $userId)->find($transaksiGas->akun_pangkalan_id);
        if (!$pangkalan) {
            abort(403);
        }

        $transaksiGas->delete();

        try {
            event(new \App\Events\DataGasUpdated($userId));
            \App\Http\Controllers\SuperAdminController::broadcastDashboard();
        } catch (\Exception $e) {}

        return redirect()->route('transaksi-gas.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function tandaiLapor(TransaksiGas $transaksiGas)
    {
        $userId = Auth::id();
        $pangkalan = AkunPangkalan::where('user_id', $userId)->find($transaksiGas->akun_pangkalan_id);
        if (!$pangkalan) {
            abort(403);
        }

        $transaksiGas->update([
            'dilaporkan_pertamina' => true,
            'waktu_dilaporkan' => now(),
            'status_lapor' => 'sukses',
            'catatan_lapor' => 'Ditandai manual oleh Admin',
        ]);

        return redirect()->route('transaksi-gas.index')->with('success', 'Transaksi ditandai sukses dilaporkan.');
    }

    public function tandaiLaporMassal(Request $request)
    {
        $userId = Auth::id();
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return redirect()->route('transaksi-gas.index')->with('error', 'Tidak ada transaksi yang dipilih.');
        }

        // Filter IDs to ensure they belong to the current user's stations
        $records = TransaksiGas::whereIn('id', $ids)
            ->whereHas('akunPangkalan', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();

        foreach ($records as $r) {
            $r->update([
                'dilaporkan_pertamina' => true,
                'waktu_dilaporkan' => now(),
                'status_lapor' => 'sukses',
                'catatan_lapor' => 'Ditandai manual massal oleh Admin',
            ]);
        }

        return redirect()->route('transaksi-gas.index')->with('success', count($records) . ' transaksi ditandai sukses dilaporkan.');
    }

    public function triggerRpa()
    {
        $userId = Auth::id();
        $belumLaporCount = TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('status_lapor', 'belum_lapor')
            ->whereHas('akunPangkalan', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->count();

        if ($belumLaporCount === 0) {
            return redirect()->route('transaksi-gas.index')->with('error', 'Tidak ada transaksi baru bulan ini yang perlu dilaporkan ke Pertamina.');
        }

        $activeSync = \App\Models\RpaSync::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
        if ($activeSync) {
            return redirect()->route('transaksi-gas.index')->with('error', 'RPA Bot sedang bekerja atau dalam antrean. Mohon tunggu proses sebelumnya selesai.');
        }

        $sync = \App\Models\RpaSync::create([
            'status' => 'pending',
            'total_records' => $belumLaporCount,
            'user_id' => $userId,
        ]);

        // 1. WebSocket broadcast (untuk UI frontend)
        try {
            event(new \App\Events\RpaSyncCreated($sync->id, $userId, $belumLaporCount));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal broadcast RpaSyncCreated: ' . $e->getMessage());
        }

        // 2. HTTP trigger langsung ke scaler daemon — agar scaler langsung bangun
        //    tanpa harus menunggu fallback polling interval (default 5 menit).
        //    Fire-and-forget: jika scaler tidak aktif, bot tetap akan ambil job
        //    saat scaler restart dan melakukan cek startup.
        try {
            $scalerPort   = env('SCALER_PORT', 3099);
            $scalerSecret = env('SCALER_SECRET', env('LPG_API_KEY', 'lpg-secret-token-12345'));
            $ch = curl_init("http://127.0.0.1:{$scalerPort}/trigger");
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2, // non-blocking: max 2 detik, tidak boleh blokir response user
                CURLOPT_HTTPHEADER     => ["x-scaler-secret: {$scalerSecret}"],
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            // Abaikan — scaler mungkin sedang restart, fallback polling akan menangani
        }
        // [UPDATE: Membuka terminal otomatis untuk testing tanpa PM2]
        
        /*
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $base = base_path();
            $appUrl = url('/');
            pclose(popen("start \"Bot Pertamina\" cmd /c \"cd /d \"{$base}\" && set LARAVEL_APP_URL={$appUrl} && node rpa-bot/bot.js --run-once\"", 'r'));
        }
        */

        return redirect()->route('transaksi-gas.index')->with('success', "Antrean dibuat untuk {$belumLaporCount} transaksi. Bot RPA otomatis berjalan.");
    }

    public function auditQuota()
    {
        $userId = Auth::id();

        // Cegah eksekusi ganda jika audit sedang berjalan
        $activeAudit = \App\Models\AuditProgress::where('user_id', $userId)->where('is_running', true)->first();
        if ($activeAudit && $activeAudit->updated_at && $activeAudit->updated_at->diffInSeconds(now()) < 60) {
            return redirect()->route('transaksi-gas.index')->with('error', 'Bot Audit Kuota sedang berjalan. Mohon tunggu hingga proses selesai.');
        }

        // Set status langsung agar UI langsung merespon "Sedang berjalan"
        \App\Models\AuditProgress::updateOrCreate(
            ['user_id' => $userId],
            [
                'is_running' => true,
                'step' => 'init',
                'step_label' => 'Menginisiasi bot (Harap tunggu 10-15 detik)...',
                'started_at' => now(),
                'finished_at' => null
            ]
        );

        // Panggil Bot Audit secara otomatis di Windows
        // Gunakan VBScript + WScript.Shell agar CMD window terbuka meski PHP berjalan sebagai service
        if (str_contains(PHP_OS_FAMILY, 'Windows')) {
            $botPath = str_replace('/', '\\', base_path('rpa-bot'));
            
            // Persiapkan direktori khusus untuk log audit
            $logDir = storage_path('logs/rpa-audit');
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = str_replace('/', '\\', $logDir . '/audit_log_user_' . $userId . '.txt');
            
            // Perintah eksekusi Node.js beserta pengalihan log (menimpa log lama tiap kali run)
            // ESCAPING PENTING: Karena dijalankan di dalam VBScript oShell.Run "...", 
            // setiap tanda kutip di dalam string command harus ditulis dua kali ("")
            $cmd = "node check-quota.js --user-id {$userId} > \"\"{$logFile}\"\" 2>&1";
            
            $vbs = sys_get_temp_dir() . '\\audit_run_' . $userId . '_' . time() . '.vbs';
            // VBScript: gunakan ""  (dua kutip) untuk escape quote di dalam string
            $vbsContent = "Set oShell = CreateObject(\"WScript.Shell\")\r\n";
            $vbsContent .= "oShell.Run \"cmd.exe /c cd /d \"\"" . $botPath . "\"\" && " . $cmd . "\", 0, False\r\n";
            file_put_contents($vbs, $vbsContent);
            pclose(popen("wscript.exe \"{$vbs}\"", "r"));
        }

        return redirect()->route('transaksi-gas.index')->with('success', 'Bot Audit Kuota sedang dijalankan secara gaib di latar belakang. Silakan pantau widget progress!');
    }

    public function resetSync()
    {
        $userId = Auth::id();
        
        // Reset RpaSync Utama
        \App\Models\RpaSync::where('user_id', $userId)->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'failed',
                'message' => 'Proses pelaporan telah dihentikan secara manual.',
                'completed_at' => now(),
            ]);

        // Reset AuditProgress
        \App\Models\AuditProgress::where('user_id', $userId)->where('is_running', true)
            ->update([
                'is_running'  => false,
                'finished_at' => now(),
                'step'        => 'error',
                'step_label'  => 'Dibatalkan secara paksa (Reset)',
            ]);

        // Bunuh paksa proses Node.js Audit Kuota khusus user ini (Windows)
        if (str_contains(PHP_OS_FAMILY, 'Windows')) {
            pclose(popen("wmic process where \"name='node.exe' and commandline like '%check-quota.js --user-id {$userId}%'\" call terminate > NUL 2>&1", "r"));
        }

        return redirect()->route('transaksi-gas.index')->with('success', 'Seluruh bot latar belakang berhasil dihentikan paksa (Reset).');
    }

    public function distribusiOtomatis(Request $request)
    {
        $validated = $request->validate([
            'akun_pangkalan_id' => ['required', 'exists:akun_pangkalans,id'],
            'total_tabung' => ['required', 'integer', 'min:1'],
            'tanggal_transaksi' => ['required', 'date'],
        ]);

        $totalTarget    = (int) $validated['total_tabung'];
        $tanggal        = $validated['tanggal_transaksi'];
        $baseDate       = \Carbon\Carbon::parse($tanggal);
        $bulan          = $baseDate->month;
        $tahun          = $baseDate->year;
        $pangkalanId    = $validated['akun_pangkalan_id'];

        // Ambil pelanggan aktif milik user
        $allPelanggan = Pelanggan::where('status', true)
            ->where('user_id', Auth::id())
            ->get();

        if ($allPelanggan->isEmpty()) {
            return redirect()->route('transaksi-gas.index')->with('error', 'Tidak ada pelanggan aktif yang terdaftar di akun Anda.');
        }

        // Ambil daftar pelanggan yang pernah ditandai 'belum_17_tahun' pada tahun ini
        $underageIdsFromTrx = TransaksiGas::whereYear('tanggal_transaksi', $tahun)
            ->where('status_lapor', 'belum_17_tahun')
            ->pluck('pelanggan_id')
            ->unique()
            ->flip()
            ->toArray();

        // Filter hanya pelanggan yang sudah berusia >= 17 tahun pada tanggal transaksi
        $pelangganList = $allPelanggan->filter(function (Pelanggan $p) use ($baseDate, $underageIdsFromTrx) {
            if (isset($underageIdsFromTrx[$p->id])) {
                return false;
            }
            return $p->isUsiaCukup($baseDate);
        })->values();

        if ($pelangganList->isEmpty()) {
            return redirect()->route('transaksi-gas.index')->with('error', 'Tidak ada pelanggan aktif berusia 17 tahun ke atas yang memenuhi syarat.');
        }

        $pelangganIds = $pelangganList->pluck('id')->toArray();

        // Rentang minggu dari tanggal_transaksi yang dipilih user (Senin - Minggu)
        $selectedWeekStart = $baseDate->copy()->startOfWeek();
        $selectedWeekEnd   = $baseDate->copy()->endOfWeek();
        $targetDate        = $baseDate->copy();
        $dStr              = $targetDate->toDateString();

        // Ambil seluruh transaksi valid bulan ini dalam 1 query (hindari N+1 query)
        $validStatuses = ['sukses', 'kuota_habis', 'belum_lapor'];
        $monthlyTrxs = TransaksiGas::whereIn('pelanggan_id', $pelangganIds)
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereIn('status_lapor', $validStatuses)
            ->select('pelanggan_id', 'jumlah_tabung', 'tanggal_transaksi')
            ->get();

        // Ambil juga transaksi pada minggu berjalan jika lintas bulan (misal awal bulan)
        $weeklyTrxs = TransaksiGas::whereIn('pelanggan_id', $pelangganIds)
            ->whereBetween('tanggal_transaksi', [$selectedWeekStart, $selectedWeekEnd])
            ->whereIn('status_lapor', $validStatuses)
            ->select('pelanggan_id', 'jumlah_tabung', 'tanggal_transaksi')
            ->get();

        $customerMonthlyTotals = array_fill_keys($pelangganIds, 0);
        $existingTrxDates = [];

        foreach ($monthlyTrxs as $t) {
            $customerMonthlyTotals[$t->pelanggan_id] = ($customerMonthlyTotals[$t->pelanggan_id] ?? 0) + (int) $t->jumlah_tabung;
            $dateKey = \Carbon\Carbon::parse($t->tanggal_transaksi)->toDateString();
            $existingTrxDates[$t->pelanggan_id][$dateKey] = true;
        }

        $customerWeeklyTotals = array_fill_keys($pelangganIds, 0);
        foreach ($weeklyTrxs as $t) {
            $customerWeeklyTotals[$t->pelanggan_id] = ($customerWeeklyTotals[$t->pelanggan_id] ?? 0) + (int) $t->jumlah_tabung;
            $dateKey = \Carbon\Carbon::parse($t->tanggal_transaksi)->toDateString();
            $existingTrxDates[$t->pelanggan_id][$dateKey] = true;
        }

        // Helper untuk mengurutkan kandidat secara merata berdasarkan tier kuota:
        // Tier 0 (0/4) -> Tier 1 (1/4) -> Tier 2 (2/4) -> Tier 3 (3/4)
        // Di dalam masing-masing tier tetap di-shuffle agar urutan pelanggan acak & adil.
        $sortCandidatesByQuotaTier = function (array $candidates) use (&$customerMonthlyTotals): array {
            $tiers = [0 => [], 1 => [], 2 => [], 3 => []];
            foreach ($candidates as $p) {
                $used = min(3, max(0, (int) ($customerMonthlyTotals[$p->id] ?? 0)));
                $tiers[$used][] = $p;
            }

            $sorted = [];
            foreach ([0, 1, 2, 3] as $tier) {
                if (!empty($tiers[$tier])) {
                    shuffle($tiers[$tier]);
                    foreach ($tiers[$tier] as $p) {
                        $sorted[] = $p;
                    }
                }
            }
            return $sorted;
        };

        $allocated = 0;
        $createdCount = 0;
        $pendingTransactions = [];
        $allocatedTodayPelanggan = [];

        // TAHAP 1 (Utama): Pelanggan yang BELUM beli di minggu ini (< 1 tabung minggu ini)
        // dan belum punya transaksi di tanggal $dStr, diurutkan dari 0/4 -> 1/4 -> 2/4 -> 3/4.
        $stage1Candidates = [];
        foreach ($pelangganList as $p) {
            if (($customerMonthlyTotals[$p->id] ?? 0) >= 4) {
                continue;
            }
            if (($customerWeeklyTotals[$p->id] ?? 0) >= 1) {
                continue;
            }
            if (!empty($existingTrxDates[$p->id][$dStr])) {
                continue;
            }
            $stage1Candidates[] = $p;
        }

        $stage1Sorted = $sortCandidatesByQuotaTier($stage1Candidates);

        foreach ($stage1Sorted as $p) {
            if ($allocated >= $totalTarget) {
                break;
            }

            $waktuTransaksi = $targetDate->copy()->setTimeFromTimeString(now()->toTimeString());
            $pendingTransactions[] = [
                'pelanggan_id' => $p->id,
                'akun_pangkalan_id' => $pangkalanId,
                'jumlah_tabung' => 1,
                'tanggal_transaksi' => $waktuTransaksi,
                'dilaporkan_pertamina' => false,
                'status_lapor' => 'belum_lapor',
            ];

            $customerMonthlyTotals[$p->id]++;
            $customerWeeklyTotals[$p->id]++;
            $existingTrxDates[$p->id][$dStr] = true;
            $allocatedTodayPelanggan[$p->id] = true;
            $allocated++;
            $createdCount++;
        }

        // TAHAP 2 (Fallback): Jika target tabung masih sisa (jumlah pelanggan yang belum beli minggu ini kurang),
        // ambil pelanggan yang masih < 4 tabung bulan ini dan belum dapat transaksi di hari ini ($dStr),
        // tetap diprioritaskan dari kuota terkecil (0/4 -> 1/4 -> 2/4 -> 3/4).
        if ($allocated < $totalTarget) {
            $stage2Candidates = [];
            foreach ($pelangganList as $p) {
                if (!empty($allocatedTodayPelanggan[$p->id])) {
                    continue;
                }
                if (($customerMonthlyTotals[$p->id] ?? 0) >= 4) {
                    continue;
                }
                if (!empty($existingTrxDates[$p->id][$dStr])) {
                    continue;
                }
                $stage2Candidates[] = $p;
            }

            $stage2Sorted = $sortCandidatesByQuotaTier($stage2Candidates);

            foreach ($stage2Sorted as $p) {
                if ($allocated >= $totalTarget) {
                    break;
                }

                $waktuTransaksi = $targetDate->copy()->setTimeFromTimeString(now()->toTimeString());
                $pendingTransactions[] = [
                    'pelanggan_id' => $p->id,
                    'akun_pangkalan_id' => $pangkalanId,
                    'jumlah_tabung' => 1,
                    'tanggal_transaksi' => $waktuTransaksi,
                    'dilaporkan_pertamina' => false,
                    'status_lapor' => 'belum_lapor',
                ];

                $customerMonthlyTotals[$p->id]++;
                $existingTrxDates[$p->id][$dStr] = true;
                $allocatedTodayPelanggan[$p->id] = true;
                $allocated++;
                $createdCount++;
            }
        }

        // Acak urutan eksekusi di dalam batch yang sudah terpilih agar urutan input ke Pertamina terlihat natural
        shuffle($pendingTransactions);

        foreach ($pendingTransactions as $trxData) {
            TransaksiGas::create($trxData);
        }

        if ($allocated < $totalTarget) {
            $sisa = $totalTarget - $allocated;
            try { event(new \App\Events\DataGasUpdated(Auth::id())); \App\Http\Controllers\SuperAdminController::broadcastDashboard(); } catch (\Exception $e) {}
            return redirect()->route('transaksi-gas.index')->with('success', "Distribusi selesai sebagian. Berhasil membuat {$createdCount} transaksi ({$allocated} tabung). Sisa {$sisa} tabung tidak teralokasikan.");
        }

        try { event(new \App\Events\DataGasUpdated(Auth::id())); \App\Http\Controllers\SuperAdminController::broadcastDashboard(); } catch (\Exception $e) {}
        return redirect()->route('transaksi-gas.index')->with('success', "Distribusi otomatis berhasil. Berhasil membuat {$createdCount} transaksi.");
    }
}
