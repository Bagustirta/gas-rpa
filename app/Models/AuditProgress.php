<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditProgress extends Model
{
    protected $table = 'audit_progress';

    protected $fillable = [
        'user_id', 'step', 'step_label',
        'pangkalan_name', 'pangkalan_index', 'pangkalan_total',
        'nik_current', 'nik_total',
        'is_running', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'is_running'  => 'boolean',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Persentase progress NIK (0–100) */
    public function nikPercent(): int
    {
        if ($this->nik_total <= 0) return 0;
        return (int) round(($this->nik_current / $this->nik_total) * 100);
    }

    /** Persentase progress pangkalan (0–100) */
    public function pangkalanPercent(): int
    {
        if ($this->pangkalan_total <= 0) return 0;
        return (int) round(($this->pangkalan_index / $this->pangkalan_total) * 100);
    }
}
