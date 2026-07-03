<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use App\Models\AuditProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;

        // 1. Stats Overview
        $totalPelanggan = Pelanggan::where('user_id', $userId)->where('status', true)->count();
        $transaksiHariIni = TransaksiGas::whereHas('akunPangkalan', fn($q) => $q->where('user_id', $userId))
            ->whereDate('tanggal_transaksi', today())
            ->count();
        $tabungBulanIni = TransaksiGas::whereHas('akunPangkalan', fn($q) => $q->where('user_id', $userId))
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('status_lapor', 'sukses')
            ->sum('jumlah_tabung');

        $kuotaHabis = TransaksiGas::whereHas('akunPangkalan', fn($q) => $q->where('user_id', $userId))
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->selectRaw('pelanggan_id')
            ->groupBy('pelanggan_id')
            ->havingRaw('SUM(jumlah_tabung) >= 4')
            ->get()
            ->count();

        $belumDilaporkan = TransaksiGas::whereHas('akunPangkalan', fn($q) => $q->where('user_id', $userId))
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('dilaporkan_pertamina', false)
            ->count();

        // 2. Customers for Quota Table
        $query = Pelanggan::where('user_id', $userId)
            ->where('status', true);

        if ($request->filled('search')) {
            $query->where('nik_bindex', hash_hmac('sha256', $request->search, config('app.key')));
        }

        $subquery = TransaksiGas::selectRaw('coalesce(sum(jumlah_tabung), 0)')
            ->whereColumn('pelanggan_id', 'pelanggan.id')
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun);

        if ($request->filled('sisa_kuota')) {
            $sisaKuota = (int)$request->sisa_kuota;
            $targetTotal = 4 - $sisaKuota;
            if ($targetTotal >= 4) {
                $query->where($subquery, '>=', 4);
            } else {
                $query->where($subquery, '=', $targetTotal);
            }
        }

        $customersQuota = $query->select('*')
            ->selectSub($subquery, 'total_tabung_bulan_ini')
            ->orderBy('total_tabung_bulan_ini', 'desc')
            ->paginate(10)
            ->onEachSide(1)
            ->withQueryString()
            ->through(fn($item) => [
                'id' => $item->id,
                'nik' => $item->nik,
                'total_tabung_bulan_ini' => (int) ($item->total_tabung_bulan_ini ?? 0),
                'sisa_kuota' => max(0, 4 - (int) ($item->total_tabung_bulan_ini ?? 0))
            ]);

        // 3. Current Live Audit Progress
        $auditProgress = AuditProgress::where('user_id', $userId)->first();
        $auditProgressData = null;

        if ($auditProgress) {
            $isRunning = (bool) $auditProgress->is_running;

            // Jika is_running tapi tidak ada update > 30 detik → berarti terminal mati paksa
            if ($isRunning && $auditProgress->updated_at && $auditProgress->updated_at->diffInSeconds(now()) > 30) {
                $auditProgress->update([
                    'is_running'  => false,
                    'finished_at' => now(),
                    'step'        => 'error',
                    'step_label'  => 'Audit terhenti (Terminal ditutup)',
                ]);
                $isRunning = false;
            }

            // Sembunyikan widget jika tidak berjalan dan sudah >10 menit sejak selesai
            $finishedAt = $auditProgress->finished_at ? \Carbon\Carbon::parse($auditProgress->finished_at) : null;
            $isOld = $finishedAt && $finishedAt->diffInMinutes(now()) >= 10;

            if ($isRunning || !$isOld) {
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalPelanggan'   => $totalPelanggan,
                'transaksiHariIni' => $transaksiHariIni,
                'tabungBulanIni'   => (int)$tabungBulanIni,
                'kuotaHabis'       => $kuotaHabis,
                'belumDilaporkan'  => $belumDilaporkan,
            ],
            'customersQuota'  => $customersQuota,
            'auditProgress'   => $auditProgressData,
            'filters'         => $request->only(['search', 'sisa_kuota']),
        ]);
    }
}
