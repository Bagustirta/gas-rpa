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
            if (\Illuminate\Support\Facades\Auth::check()) {
                $model->user_id = $model->user_id ?? \Illuminate\Support\Facades\Auth::id();
            }
        });

        static::saving(function ($model) {
            if ($model->isDirty('nik')) {
                // Buat sidik jari statis (Blind Index) untuk fitur pencarian NIK yang persis sama
                $model->nik_bindex = hash_hmac('sha256', $model->nik, env('APP_KEY') ?? config('app' . '.key'));
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
     * Hanya menghitung transaksi yang sukses, kuota_habis, atau masih dalam antrean (belum_lapor).
     * Transaksi yang gagal / belum_17_tahun / meninggal tidak memotong kuota pelanggan.
     */
    public function totalTabungBulanIni(?int $bulan = null, ?int $tahun = null): int
    {
        $bulan = $bulan ?? Carbon::now()->month;
        $tahun = $tahun ?? Carbon::now()->year;

        return (int) $this->transaksiGas()
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->whereIn('status_lapor', ['sukses', 'kuota_habis', 'belum_lapor'])
            ->sum('jumlah_tabung');
    }

    /**
     * Periksa apakah pelanggan sudah berusia minimal 17 tahun pada tanggal acuan.
     * Mengecek dari kolom tanggal_lahir atau mengekstrak 6 digit tengah NIK (DDMMYY).
     */
    public function isUsiaCukup(?Carbon $refDate = null): bool
    {
        $refDate = ($refDate ?? Carbon::today())->copy()->endOfDay();

        // 1. Cek dari kolom tanggal_lahir jika tersedia
        if (!empty($this->tanggal_lahir) && $this->tanggal_lahir !== '-') {
            try {
                $dob = Carbon::parse($this->tanggal_lahir)->startOfDay();
                return $dob->diffInYears($refDate) >= 17;
            } catch (\Throwable $e) {
                // Fallback ke parsing NIK
            }
        }

        // 2. Ekstrak dari 16 digit NIK (digit ke-7 s/d 12 = DDMMYY)
        $nik = preg_replace('/\D/', '', (string) $this->nik);
        if (strlen($nik) === 16) {
            $dd = (int) substr($nik, 6, 2);
            $mm = (int) substr($nik, 8, 2);
            $yy = (int) substr($nik, 10, 2);

            // Untuk perempuan, tanggal lahir pada NIK ditambah 40
            if ($dd > 40) {
                $dd -= 40;
            }

            if ($dd >= 1 && $dd <= 31 && $mm >= 1 && $mm <= 12) {
                $currentTwoDigitYear = (int) $refDate->format('y');
                $fullYear = ($yy <= $currentTwoDigitYear) ? (2000 + $yy) : (1900 + $yy);
                try {
                    $dob = Carbon::createFromDate($fullYear, $mm, $dd)->startOfDay();
                    return $dob->diffInYears($refDate) >= 17;
                } catch (\Throwable $e) {
                    return true;
                }
            }
        }

        return true;
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
