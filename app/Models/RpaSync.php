<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpaSync extends Model
{
    protected $fillable = [
        'status',
        'total_records',
        'success_records',
        'failed_records',
        'current_pangkalan',
        'message',
        'started_at',
        'completed_at',
        'user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calculateSummary()
    {
        $userId = $this->user_id;
        $startedAt = $this->created_at;

        // Ambil semua transaksi yang relevan untuk sesi sync ini
        $transaksis = \App\Models\TransaksiGas::with('akunPangkalan:id,nama_pangkalan')
            ->whereHas('akunPangkalan', function($q) use ($userId) { 
                $q->where('user_id', $userId); 
            })
            ->where(function($q) use ($startedAt) {
                $q->where('status_lapor', 'belum_lapor')
                  ->orWhere(function($sub) use ($startedAt) {
                      $sub->whereIn('status_lapor', ['sukses', 'kuota_habis', 'gagal', 'proses'])
                          ->where('updated_at', '>=', $startedAt);
                  });
            })
            ->get();

        $breakdowns = [];
        $globalSukses = 0;
        $globalKuotaHabis = 0;
        $globalGagal = 0;
        $globalBelumLapor = 0;
        $globalTotal = 0;

        foreach ($transaksis as $t) {
            $pangkalanId = $t->akun_pangkalan_id;
            $namaPangkalan = $t->akunPangkalan->nama_pangkalan ?? 'Pangkalan Tidak Diketahui';
            
            if (!isset($breakdowns[$pangkalanId])) {
                $breakdowns[$pangkalanId] = [
                    'nama_pangkalan' => $namaPangkalan,
                    'total' => 0,
                    'sukses' => 0,
                    'kuota_habis' => 0,
                    'gagal' => 0,
                    'belum_lapor' => 0,
                ];
            }
            
            $breakdowns[$pangkalanId]['total']++;
            $globalTotal++;
            
            if ($t->status_lapor === 'sukses') { $breakdowns[$pangkalanId]['sukses']++; $globalSukses++; }
            elseif ($t->status_lapor === 'kuota_habis') { $breakdowns[$pangkalanId]['kuota_habis']++; $globalKuotaHabis++; }
            elseif ($t->status_lapor === 'gagal') { $breakdowns[$pangkalanId]['gagal']++; $globalGagal++; }
            else { $breakdowns[$pangkalanId]['belum_lapor']++; $globalBelumLapor++; } // termasuk 'belum_lapor' atau 'proses'
        }

        return [
            // Gunakan globalTotal aktual dari query atau fallback ke total_records di DB
            'total' => max($globalTotal, $this->total_records), 
            'sukses' => $globalSukses,
            'kuota_habis' => $globalKuotaHabis,
            'belum_lapor' => $globalBelumLapor,
            'gagal' => $globalGagal,
            'current_pangkalan' => $this->current_pangkalan,
            'breakdowns' => array_values($breakdowns),
        ];
    }
}
