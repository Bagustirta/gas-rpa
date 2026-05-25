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

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = $model->user_id ?? auth()->id();
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
