<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpaSync extends Model
{
    protected $fillable = [
        'status',
        'total_records',
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
}
