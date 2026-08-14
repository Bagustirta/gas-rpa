<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $fillable = [
        'nik',
        'nik_bindex',
        'status',
        'user_id',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'id_unik',
        'is_synced',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = $model->user_id ?? auth()->id();
            }
        });

        static::saving(function ($model) {
            if ($model->isDirty('nik')) {
                // Buat sidik jari statis (Blind Index) untuk fitur pencarian NIK yang persis sama
                $model->nik_bindex = hash_hmac('sha256', $model->nik, config('app.key'));
            }
        });
    }

    protected $casts = [
        'status' => 'boolean',
        'nik'    => 'encrypted', // Enkripsi Otomatis Laravel Level 2
    ];

    /**
     * Relasi ke transaksi gas.
     */
    public function transaksiGas(): HasMany
    {
        return $this->hasMany(TransaksiGas::class, 'pelanggan_id');
    }

    /**
     * Hitung total tabung yang dibeli pada bulan & tahun tertentu.
     */
    public function totalTabungBulanIni(?int $bulan = null, ?int $tahun = null): int
    {
        $bulan = $bulan ?? Carbon::now()->month;
        $tahun = $tahun ?? Carbon::now()->year;

        return (int) $this->transaksiGas()
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->sum('jumlah_tabung');
    }

    /**
     * Hitung sisa kuota bulan ini (maks 4 tabung).
     */
    public function sisaKuotaBulanIni(): int
    {
        return max(0, 4 - $this->totalTabungBulanIni());
    }

    /**
     * Relasi ke user.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
