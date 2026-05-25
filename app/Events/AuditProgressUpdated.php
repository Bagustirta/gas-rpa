<?php

namespace App\Events;

use App\Models\AuditProgress;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AuditProgress $progress)
    {
    }

    /**
     * Broadcast ke private channel milik user yang sedang audit.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('audit-progress.' . $this->progress->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AuditProgressUpdated';
    }

    /**
     * Data yang dikirim ke browser via WebSocket.
     */
    public function broadcastWith(): array
    {
        return [
            'step'             => $this->progress->step,
            'step_label'       => $this->progress->step_label,
            'pangkalan_name'   => $this->progress->pangkalan_name,
            'pangkalan_index'  => $this->progress->pangkalan_index,
            'pangkalan_total'  => $this->progress->pangkalan_total,
            'nik_current'      => $this->progress->nik_current,
            'nik_total'        => $this->progress->nik_total,
            'nik_pct'          => $this->progress->nikPercent(),
            'pangkalan_pct'    => $this->progress->pangkalanPercent(),
            'is_running'       => $this->progress->is_running,
            'started_at'       => $this->progress->started_at?->format('H:i:s'),
            'finished_at'      => $this->progress->finished_at?->format('H:i:s'),
        ];
    }
}
