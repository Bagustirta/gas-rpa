<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunPangkalan extends Model
{
    use HasFactory;

    protected $table = 'akun_pangkalans';

    protected $fillable = [
        'nama_pangkalan',
        'email',
        'password',
        'user_id',
    ];

    protected $casts = [
        'password' => 'encrypted', // Enkripsi Otomatis Laravel Level 2
    ];

    protected $hidden = [
        'password',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $model->user_id = $model->user_id ?? \Illuminate\Support\Facades\Auth::id();
            }
        });
    }

    /**
     * Relasi ke user.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
