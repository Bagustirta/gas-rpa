<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use App\Models\AuditProgress;
use App\Events\AuditProgressUpdated;

class ApiLpgController extends Controller
{
    /**
     * GET /api/nik-siap-lapor
     * 
     * Mengembalikan daftar NIK yang sudah ada transaksi bulan ini
     * namun BELUM dilaporkan ke MAP Pertamina.
     * Digunakan oleh skrip bot/RPA (Chrome Extension / Puppeteer).
     */
    public function nikSiapLapor(Request $request): JsonResponse
    {
        $bulan = $request->integer('bulan', Carbon::now()->month);
        $tahun = $request->integer('tahun', Carbon::now()->year);

        // Hapus otomatis transaksi bulan-bulan sebelumnya
        TransaksiGas::where('tanggal_transaksi', '<', Carbon::now()->startOfMonth())->delete();

        // Hapus otomatis transaksi yang hari transaksinya sudah lewat (sebelum hari ini) dan belum/gagal terlaporkan
        TransaksiGas::whereDate('tanggal_transaksi', '<', Carbon::today())
            ->where('status_lapor', '!=', 'sukses')
            ->delete();

        $transaksi = TransaksiGas::with('pelanggan:id,nik')
            ->whereHas('pelanggan', function ($q) use ($bulan, $tahun) {
                $q->where('status', true)
                  ->where(function ($sub) use ($bulan, $tahun) {
                      $subquery = TransaksiGas::selectRaw('COALESCE(SUM(jumlah_tabung), 0)')
                          ->whereColumn('pelanggan_id', 'pelanggan.id')
                          ->where('status_lapor', 'sukses')
                          ->whereMonth('tanggal_transaksi', $bulan)
                          ->whereYear('tanggal_transaksi', $tahun);
                      
                      $sub->where($subquery, '<', 4);
                  });
            })
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('status_lapor', 'belum_lapor')
            ->get()
            ->groupBy('pelanggan_id')
            ->map(function ($items) use ($bulan, $tahun) {
                $first     = $items->first();
                $pelanggan = $first->pelanggan;
                // Jaga-jaga jika relasi tidak termuat
                if (!$pelanggan) return null;
                $totalTabung = $items->sum('jumlah_tabung');

                // Hanya sertakan jika belum mencapai kuota penuh (ada sisa)
                // atau sudah ada transaksi yang belum dilaporkan
                return [
                    'transaksi_id'      => $first->id,
                    'pelanggan_id'      => $pelanggan->id,
                    'nik'               => $pelanggan->nik,
                    'jumlah_tabung'     => $totalTabung,
                    'sisa_kuota'        => max(0, 4 - $totalTabung),
                    'tanggal_transaksi' => $first->tanggal_transaksi->format('Y-m-d H:i:s'),
                    'dilaporkan'        => false,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status'    => 'success',
            'bulan'     => Carbon::create($tahun, $bulan)->translatedFormat('F Y'),
            'total'     => $transaksi->count(),
            'data'      => $transaksi,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/nik-siap-lapor/detail
     *
     * Daftar NIK dengan seluruh transaksi yang belum dilaporkan secara per-baris.
     * Berguna untuk bot yang input satu per satu ke sistem Pertamina.
     */
    public function nikSiapLaporDetail(Request $request): JsonResponse
    {
        $bulan = $request->integer('bulan', Carbon::now()->month);
        $tahun = $request->integer('tahun', Carbon::now()->year);

        // Hapus otomatis transaksi bulan-bulan sebelumnya
        TransaksiGas::where('tanggal_transaksi', '<', Carbon::now()->startOfMonth())->delete();

        // Hapus otomatis transaksi yang hari transaksinya sudah lewat (sebelum hari ini) dan belum/gagal terlaporkan
        TransaksiGas::whereDate('tanggal_transaksi', '<', Carbon::today())
            ->where('status_lapor', '!=', 'sukses')
            ->delete();

        $queryBuilder = TransaksiGas::with('pelanggan:id,nik')
            ->whereHas('pelanggan', function ($q) use ($bulan, $tahun) {
                $q->where('status', true)
                  ->where(function ($sub) use ($bulan, $tahun) {
                      $subquery = TransaksiGas::selectRaw('COALESCE(SUM(jumlah_tabung), 0)')
                          ->whereColumn('pelanggan_id', 'pelanggan.id')
                          ->where('status_lapor', 'sukses')
                          ->whereMonth('tanggal_transaksi', $bulan)
                          ->whereYear('tanggal_transaksi', $tahun);
                      
                      $sub->where($subquery, '<', 4);
                  });
            })
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('status_lapor', 'belum_lapor');

        if ($request->filled('akun_pangkalan_id')) {
            $queryBuilder->where('akun_pangkalan_id', $request->input('akun_pangkalan_id'));
        }

        if ($request->filled('user_id')) {
            $queryBuilder->whereHas('akunPangkalan', function ($q) use ($request) {
                $q->where('user_id', $request->input('user_id'));
            });
        }

        $transaksi = $queryBuilder->orderBy('tanggal_transaksi')
            ->get()
            ->map(function ($t) {
                if (!$t->pelanggan) return null;
                return [
                    'id'                => $t->id,
                    'nik'               => $t->pelanggan->nik,
                    'jumlah_tabung'     => $t->jumlah_tabung,
                    'tanggal_transaksi' => $t->tanggal_transaksi->format('Y-m-d'),
                    'dilaporkan'        => false,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status'    => 'success',
            'bulan'     => Carbon::create($tahun, $bulan)->translatedFormat('F Y'),
            'total'     => $transaksi->count(),
            'data'      => $transaksi,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * POST /api/konfirmasi-lapor/{id}
     *
     * Menandai transaksi dengan ID tertentu sudah berhasil dilaporkan
     * ke MAP Pertamina oleh skrip bot/RPA.
     */
    public function konfirmasiLapor(Request $request, int $id): JsonResponse
    {
        $transaksi = TransaksiGas::with('pelanggan:id,nik')->find($id);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => "Transaksi dengan ID {$id} tidak ditemukan.",
            ], 404);
        }

        if ($transaksi->dilaporkan_pertamina) {
            return response()->json([
                'status'  => 'warning',
                'message' => "Transaksi ID {$id} sudah pernah dilaporkan sebelumnya pada {$transaksi->waktu_dilaporkan?->format('d M Y H:i')}.",
                'data'    => [
                    'id'               => $transaksi->id,
                    'nik'              => $transaksi->pelanggan->nik,
                    'waktu_dilaporkan' => $transaksi->waktu_dilaporkan?->toIso8601String(),
                ],
            ]);
        }

        $catatan = 'Dilaporkan oleh RPA Bot';
        if ($request->filled('pangkalan')) {
            $catatan = 'Dilaporkan di: ' . $request->input('pangkalan');
        }

        $transaksi->update([
            'dilaporkan_pertamina' => true,
            'waktu_dilaporkan'     => Carbon::now(),
            'status_lapor'         => 'sukses',
            'catatan_lapor'        => $catatan,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "NIK {$transaksi->pelanggan->nik} berhasil dikonfirmasi dilaporkan ke Pertamina.",
            'data'    => [
                'id'               => $transaksi->id,
                'nik'              => $transaksi->pelanggan->nik,
                'jumlah_tabung'    => $transaksi->jumlah_tabung,
                'waktu_dilaporkan' => $transaksi->waktu_dilaporkan->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/konfirmasi-lapor/bulk
     *
     * Konfirmasi massal beberapa transaksi sekaligus.
     * Body: { "ids": [1, 2, 3, ...] }
     */
    public function konfirmasiLaporBulk(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids       = $request->input('ids');
        $now       = Carbon::now();
        $updated   = 0;
        $notFound  = [];
        $sudahLapor = [];

        foreach ($ids as $id) {
            $transaksi = TransaksiGas::find($id);
            if (!$transaksi) {
                $notFound[] = $id;
                continue;
            }
            if ($transaksi->dilaporkan_pertamina) {
                $sudahLapor[] = $id;
                continue;
            }
            $transaksi->update([
                'dilaporkan_pertamina' => true,
                'waktu_dilaporkan'     => $now,
            ]);
            $updated++;
        }

        return response()->json([
            'status'       => 'success',
            'dikonfirmasi' => $updated,
            'sudah_lapor'  => count($sudahLapor),
            'tidak_ditemukan' => count($notFound),
            'detail'       => [
                'ids_sudah_lapor'     => $sudahLapor,
                'ids_tidak_ditemukan' => $notFound,
            ],
            'timestamp'    => $now->toIso8601String(),
        ]);
    }

    /**
     * GET /api/rekap-kuota
     * 
     * Rekap status kuota seluruh pelanggan bulan ini.
     * Berguna untuk dashboard monitoring eksternal.
     */
    public function rekapKuota(Request $request): JsonResponse
    {
        $bulan = $request->integer('bulan', Carbon::now()->month);
        $tahun = $request->integer('tahun', Carbon::now()->year);

        $pelanggan = Pelanggan::where('status', true)
            ->withSum([
                'transaksiGas as total_tabung' => fn ($q) => $q
                    ->whereMonth('tanggal_transaksi', $bulan)
                    ->whereYear('tanggal_transaksi', $tahun)
            ], 'jumlah_tabung')
            ->orderBy('nik')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'nik'         => $p->nik,
                'terpakai'    => (int) ($p->total_tabung ?? 0),
                'sisa_kuota'  => max(0, 4 - (int) ($p->total_tabung ?? 0)),
                'kuota_habis' => ((int) ($p->total_tabung ?? 0)) >= 4,
            ]);

        $summary = [
            'total_pelanggan' => $pelanggan->count(),
            'kuota_habis'     => $pelanggan->where('kuota_habis', true)->count(),
            'kuota_tersedia'  => $pelanggan->where('kuota_habis', false)->count(),
            'total_tabung'    => $pelanggan->sum('terpakai'),
        ];

        return response()->json([
            'status'    => 'success',
            'bulan'     => Carbon::create($tahun, $bulan)->translatedFormat('F Y'),
            'summary'   => $summary,
            'data'      => $pelanggan,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/rpa-sync/pending
     *
     * Mendapatkan antrean sinkronisasi manual tertua yang berstatus 'pending'.
     */
    public function getPendingSync(): JsonResponse
    {
        $sync = \App\Models\RpaSync::where('status', 'pending')->orderBy('created_at', 'asc')->first();
        return response()->json([
            'status' => 'success',
            'sync'   => $sync ? [
                'id'         => $sync->id,
                'status'     => $sync->status,
                'created_at' => $sync->created_at->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * PATCH /api/rpa-sync/{id}
     *
     * Memperbarui status sinkronisasi (processing, completed, failed) dari bot RPA.
     */
    public function updateSyncStatus(Request $request, int $id): JsonResponse
    {
        $sync = \App\Models\RpaSync::find($id);
        if (!$sync) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sync job tidak ditemukan.',
            ], 404);
        }

        $data = $request->validate([
            'status'        => ['required', 'string', 'in:processing,completed,failed'],
            'total_records' => ['integer', 'min:0'],
            'message'       => ['string', 'nullable'],
        ]);

        if ($data['status'] === 'processing' && !$sync->started_at) {
            $sync->started_at = now();
        }
        if (in_array($data['status'], ['completed', 'failed']) && !$sync->completed_at) {
            $sync->completed_at = now();
        }

        $sync->update($data);

        return response()->json([
            'status' => 'success',
            'sync'   => $sync,
        ]);
    }

    /**
     * POST /api/gagal-lapor/{id}
     *
     * Menandai transaksi gagal dilaporkan ke MAP Pertamina dengan pesan/alasan tertentu.
     */
    public function gagalLapor(Request $request, int $id): JsonResponse
    {
        $transaksi = TransaksiGas::with('pelanggan:id,nik')->find($id);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => "Transaksi dengan ID {$id} tidak ditemukan.",
            ], 404);
        }

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $transaksi->update([
            'status_lapor'         => 'gagal',
            'catatan_lapor'        => $data['message'],
            'dilaporkan_pertamina' => false,
        ]);

        // Nonaktifkan pelanggan secara otomatis jika error berkaitan dengan NIK tidak valid atau meninggal
        $msgLower = strtolower($data['message']);
        if (
            str_contains($msgLower, 'belum 17 tahun') || 
            str_contains($msgLower, 'tidak terdaftar') || 
            str_contains($msgLower, 'nik salah') || 
            str_contains($msgLower, 'nik tidak ditemukan') ||
            str_contains($msgLower, 'meninggal') ||
            str_contains($msgLower, 'nib')
        ) {
            if ($transaksi->pelanggan) {
                $transaksi->pelanggan->update(['status' => false]);
            }
        }

        // Jika error karena melebihi batas kewajaran (limit habis), set total tabung bulan ini ke 4
        // Dan tandai sebagai SUKSES karena ini adalah fakta dari MyPertamina bahwa kuota sudah 4/4.
        if (str_contains($msgLower, 'melebihi batas kewajaran') || str_contains($msgLower, 'limit')) {
            if ($transaksi->pelanggan) {
                $currentSum = $transaksi->pelanggan->totalTabungBulanIni(
                    $transaksi->tanggal_transaksi->month,
                    $transaksi->tanggal_transaksi->year
                );
                if ($currentSum < 4) {
                    $selisih = 4 - $currentSum;
                    $transaksi->update([
                        'jumlah_tabung' => $transaksi->jumlah_tabung + $selisih,
                        'status_lapor' => 'sukses',
                        'dilaporkan_pertamina' => true,
                        'catatan_lapor' => 'Selesai: Kuota 4/4 Habis (Beli di pangkalan lain)',
                        'waktu_dilaporkan' => Carbon::now(),
                    ]);
                } else {
                    $transaksi->update([
                        'status_lapor' => 'sukses',
                        'dilaporkan_pertamina' => true,
                        'catatan_lapor' => 'Selesai: Kuota 4/4 Habis (Beli di pangkalan lain)',
                        'waktu_dilaporkan' => Carbon::now(),
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "NIK {$transaksi->pelanggan->nik} ditandai gagal dilaporkan dengan alasan: {$data['message']}.",
        ]);
    }

    /**
     * POST /api/rpa-sync/stok-kosong
     *
     * Dipanggil ketika bot mendeteksi stok kosong di MyPertamina.
     * Menghentikan pekerjaan sinkronisasi, menandai job sebagai failed,
     * dan me-reset semua transaksi hari ini yang tidak sukses kembali ke 'belum_lapor'.
     */
    public function handleStokKosong(Request $request): JsonResponse
    {
        $query = TransaksiGas::where('status_lapor', '!=', 'sukses');
        
        if ($request->filled('akun_pangkalan_id')) {
            $query->where('akun_pangkalan_id', $request->input('akun_pangkalan_id'));
        }

        $deletedCount = $query->delete();

        $sisaTransaksi = TransaksiGas::where('status_lapor', 'belum_lapor')->count();
        if ($sisaTransaksi === 0) {
            \App\Models\RpaSync::whereIn('status', ['processing', 'pending'])
                ->update([
                    'status' => 'failed',
                    'message' => 'Dihentikan karena stok tabung di portal MyPertamina kosong. Sisa transaksi dihapus.',
                    'completed_at' => now(),
                ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Stok MyPertamina untuk pangkalan ini kosong. Sebanyak {$deletedCount} transaksi yang belum sukses telah dihapus.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * GET /api/all-pelanggan-aktif
     */
    public function allPelangganAktif(Request $request): JsonResponse
    {
        $query = \App\Models\Pelanggan::where('status', true);

        if ($request->filled('email_pangkalan')) {
            $email = base64_decode($request->input('email_pangkalan'));
            $pangkalan = \App\Models\AkunPangkalan::where('email', $email)->first();
            if ($pangkalan) {
                $query->where('user_id', $pangkalan->user_id);
            }
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $pelanggans = $query->select('id', 'nik')->get();

        return response()->json([
            'status' => 'success',
            'total'  => $pelanggans->count(),
            'data'   => $pelanggans,
        ]);
    }

    /**
     * POST /api/pelanggan/sync-quota
     */
    public function syncQuota(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'string'],
            'count' => ['required', 'integer'],
            'pangkalan' => ['required', 'string'],
            'email_pangkalan' => ['nullable', 'string'],
        ]);

        $pelanggan = \App\Models\Pelanggan::where('nik', $data['nik'])->first();
        if (!$pelanggan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan.'
            ], 404);
        }

        // Hitung total akumulasi SEBELUM update
        $totalSebelum = $pelanggan->totalTabungBulanIni();
        $wasAlreadyFull = ($totalSebelum >= 4);

        $pangkalanName = trim($data['pangkalan']);
        $targetCount = $data['count'];

        // Cari pangkalan
        $pangkalanQuery = \App\Models\AkunPangkalan::query();
        if (!empty($data['email_pangkalan'])) {
            $email = base64_decode($data['email_pangkalan']);
            $pangkalanQuery->where('email', $email);
        } else {
            $pangkalanQuery->where('nama_pangkalan', $pangkalanName);
        }
        $pangkalan = $pangkalanQuery->first();
        $pangkalanId = $pangkalan?->id;

        // Hapus transaksi yang SUDAH DILAPORKAN untuk pangkalan ini bulan ini
        // karena MyPertamina adalah sumber kebenaran saat audit.
        $pelanggan->transaksiGas()
            ->whereMonth('tanggal_transaksi', Carbon::now()->month)
            ->whereYear('tanggal_transaksi', Carbon::now()->year)
            ->where('akun_pangkalan_id', $pangkalanId)
            ->where(function ($query) {
                $query->where('status_lapor', 'sukses')
                      ->orWhere('dilaporkan_pertamina', true);
            })
            ->delete();

        // Ambil transaksi pending/gagal untuk pangkalan ini bulan ini
        $pendingTrans = $pelanggan->transaksiGas()
            ->whereMonth('tanggal_transaksi', Carbon::now()->month)
            ->whereYear('tanggal_transaksi', Carbon::now()->year)
            ->where('akun_pangkalan_id', $pangkalanId)
            ->where(function ($query) {
                $query->where('status_lapor', '!=', 'sukses')
                      ->where('dilaporkan_pertamina', false);
            })
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

        foreach ($pendingTrans as $trans) {
            if ($targetCount <= 0) {
                break;
            }
            $jumlah = $trans->jumlah_tabung;
            if ($targetCount >= $jumlah) {
                // Transaksi ini sudah terlaporkan sepenuhnya di MyPertamina
                $trans->update([
                    'status_lapor' => 'sukses',
                    'dilaporkan_pertamina' => true,
                    'catatan_lapor' => "Disesuaikan saat Audit: {$pangkalanName}",
                ]);
                $targetCount -= $jumlah;
            } else {
                // Sebagian dari transaksi ini sudah terlaporkan
                // Pecah transaksi: yang sukses disesuaikan, sisanya tetap pending
                $sisa = $jumlah - $targetCount;
                $trans->update([
                    'jumlah_tabung' => $targetCount,
                    'status_lapor' => 'sukses',
                    'dilaporkan_pertamina' => true,
                    'catatan_lapor' => "Disesuaikan saat Audit (Partial): {$pangkalanName}",
                ]);
                
                $pelanggan->transaksiGas()->create([
                    'akun_pangkalan_id' => $pangkalanId,
                    'jumlah_tabung' => $sisa,
                    'tanggal_transaksi' => $trans->tanggal_transaksi,
                    'status_lapor' => 'belum_lapor',
                    'dilaporkan_pertamina' => false,
                    'catatan_lapor' => 'Sisa dari pecahan transaksi audit',
                ]);
                $targetCount = 0;
            }
        }

        // Jika masih ada targetCount tersisa (tidak ada transaksi pending yang cocok atau targetCount lebih besar)
        if ($targetCount > 0) {
            $pelanggan->transaksiGas()->create([
                'akun_pangkalan_id' => $pangkalanId,
                'jumlah_tabung' => $targetCount,
                'tanggal_transaksi' => now(),
                'status_lapor' => 'sukses',
                'dilaporkan_pertamina' => true,
                'catatan_lapor' => "Audit Pangkalan: {$pangkalanName}",
            ]);
        }

        // Hitung total akumulasi SETELAH update
        $totalSesudah = $pelanggan->totalTabungBulanIni();
        $isNowFull = ($totalSesudah >= 4);

        // Bersihkan transaksi pending/gagal lainnya yang melampaui sisa kuota (4 tabung)
        $sisaKuota = max(0, 4 - $totalSesudah);
        $remainingPending = $pelanggan->transaksiGas()
            ->whereMonth('tanggal_transaksi', Carbon::now()->month)
            ->whereYear('tanggal_transaksi', Carbon::now()->year)
            ->where('status_lapor', '!=', 'sukses')
            ->where('dilaporkan_pertamina', false)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

        foreach ($remainingPending as $pTrx) {
            if ($sisaKuota <= 0) {
                // Hapus karena sisa kuota pelanggan sudah 0 (habis di MyPertamina)
                $pTrx->delete();
            } else {
                $jumlah = $pTrx->jumlah_tabung;
                if ($sisaKuota >= $jumlah) {
                    $sisaKuota -= $jumlah;
                } else {
                    // Hanya muat sebagian, pecah transaksi dan hapus sisanya
                    $pTrx->update(['jumlah_tabung' => $sisaKuota]);
                    $sisaKuota = 0;
                }
            }
        }

        // Hitung ulang total akumulasi final setelah pembersihan
        $totalSesudah = $pelanggan->totalTabungBulanIni();
        $isNowFull = ($totalSesudah >= 4);

        return response()->json([
            'status' => 'success',
            'message' => "Kuota NIK {$data['nik']} di pangkalan '{$pangkalanName}' diupdate ke {$data['count']}.",
            'was_already_full' => $wasAlreadyFull,
            'is_now_full' => $isNowFull,
            'total_sebelum' => $totalSebelum,
            'total_sesudah' => $totalSesudah,
        ]);
    }

    /**
     * GET /api/akun-pangkalan/aktif
     * 
     * Mengembalikan daftar seluruh Akun Pangkalan yang terdaftar.
     * Digunakan oleh bot RPA untuk melakukan login berantai.
     */
    public function allAkunPangkalan(Request $request): JsonResponse
    {
        $query = \App\Models\AkunPangkalan::query();
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $akuns = $query->get()->map(function ($akun) {
            return [
                'id' => $akun->id,
                'nama_pangkalan' => $akun->nama_pangkalan,
                'email' => $akun->email,
                'password' => $akun->password,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $akuns,
        ]);
    }

    /**
     * POST /api/audit/progress
     *
     * Dipanggil bot untuk mengirim status progress audit secara real-time.
     * Bot mengirim: user_id, type, message, icon (opsional), meta (opsional)
     */
    public function auditProgress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'type'    => ['required', 'string'],  // login, navigate, filter, cek_nik, selesai, error
            'message' => ['required', 'string'],
            'meta'    => ['nullable', 'array'],
        ]);

        $user = \App\Models\User::find($data['user_id']);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }

        $iconMap = [
            'login'    => '🔐',
            'navigate' => '🧭',
            'filter'   => '📅',
            'cek_nik'  => '🔍',
            'selesai'  => '✅',
            'error'    => '❌',
            'pangkalan'=> '🏪',
            'info'     => 'ℹ️',
        ];
        $icon = $iconMap[$data['type']] ?? 'ℹ️';

        // Simpan ke tabel notifications bawaan Laravel
        DatabaseNotification::create([
            'id'              => Str::uuid(),
            'type'            => 'App\\Notifications\\AuditProgressNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $user->id,
            'data'            => [
                'title'   => $icon . ' ' . ucfirst(str_replace('_', ' ', $data['type'])),
                'body'    => $data['message'],
                'type'    => $data['type'],
                'meta'    => $data['meta'] ?? [],
                'at'      => now()->format('H:i:s'),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * POST /api/audit/status
     *
     * Dipanggil bot untuk memperbarui status progress audit secara live (satu record per user).
     */
    public function auditStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'          => ['required', 'integer'],
            'step'             => ['required', 'string'],
            'step_label'       => ['required', 'string'],
            'pangkalan_name'   => ['nullable', 'string'],
            'pangkalan_index'  => ['nullable', 'integer'],
            'pangkalan_total'  => ['nullable', 'integer'],
            'nik_current'      => ['nullable', 'integer'],
            'nik_total'        => ['nullable', 'integer'],
            'is_running'       => ['nullable', 'boolean'],
        ]);

        $progress = AuditProgress::firstOrNew(['user_id' => $data['user_id']]);
        $progress->step           = $data['step'];
        $progress->step_label     = $data['step_label'];
        $progress->pangkalan_name = $data['pangkalan_name'] ?? $progress->pangkalan_name;
        $progress->pangkalan_index= $data['pangkalan_index'] ?? $progress->pangkalan_index;
        $progress->pangkalan_total= $data['pangkalan_total'] ?? $progress->pangkalan_total;
        $progress->nik_current    = $data['nik_current'] ?? $progress->nik_current;
        $progress->nik_total      = $data['nik_total'] ?? $progress->nik_total;
        $isRunning = $data['is_running'] ?? true;

        if ($isRunning) {
            // Jika sebelumnya tidak berjalan, atau ini langkah awal, set started_at ke sekarang
            if (!$progress->is_running || ($data['step'] === 'pangkalan' && ($data['pangkalan_index'] ?? 1) == 1 && ($data['nik_current'] ?? 0) == 0)) {
                $progress->started_at = now();
            }
            $progress->finished_at = null; // Bersihkan finished_at dari sesi audit sebelumnya
        } else {
            $progress->finished_at = now();
        }
        $progress->is_running = $isRunning;
        $progress->save();

        // Broadcast ke browser via Laravel Reverb (async via queue worker)
        try {
            broadcast(new AuditProgressUpdated($progress));
        } catch (\Throwable $e) {
            // Jika Reverb tidak aktif, tetap lanjutkan — API tidak boleh gagal
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * POST /api/audit/heartbeat
     *
     * Memperbarui updated_at dari progress audit untuk menandakan bot masih hidup.
     */
    public function auditHeartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $progress = AuditProgress::where('user_id', $data['user_id'])->first();
        if ($progress) {
            $progress->touch();
            
            // Broadcast ke browser agar Reverb juga tahu bot masih aktif/update timestamp
            try {
                broadcast(new AuditProgressUpdated($progress));
            } catch (\Throwable $e) {}
        }

        return response()->json(['status' => 'success']);
    }
}
