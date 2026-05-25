<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiGas extends Model
{
    use HasFactory;

    protected $table = 'transaksi_gas';

    protected $fillable = [
        'pelanggan_id',
        'akun_pangkalan_id',
        'jumlah_tabung',
        'tanggal_transaksi',
        'dilaporkan_pertamina',
        'waktu_dilaporkan',
        'status_lapor',
        'catatan_lapor',
    ];

    protected $casts = [
        'tanggal_transaksi'   => 'datetime',
        'waktu_dilaporkan'    => 'datetime',
        'dilaporkan_pertamina'=> 'boolean',
    ];

    /**
     * Relasi ke pelanggan.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    /**
     * Scope: transaksi bulan ini.
     */
    public function scopeBulanIni($query)
    {
        return $query
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year);
    }

    /**
     * Scope: belum dilaporkan ke Pertamina.
     */
    public function scopeBelumDilaporkan($query)
    {
        return $query->where('status_lapor', 'belum_lapor');
    }

    /**
     * Relasi ke akun pangkalan.
     */
    public function akunPangkalan(): BelongsTo
    {
        return $this->belongsTo(AkunPangkalan::class, 'akun_pangkalan_id');
    }
}
