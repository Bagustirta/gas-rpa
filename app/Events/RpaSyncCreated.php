<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang di-broadcast ke public channel saat job RPA baru dibuat.
 * Digunakan oleh bot.js (RPA Daemon) untuk mendeteksi job baru secara real-time
 * via WebSocket, menggantikan polling terus-menerus ke /api/rpa-sync/pending.
 */
class RpaSyncCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $syncId;
    public int $userId;
    public int $totalRecords;

    public function __construct(int $syncId, int $userId, int $totalRecords)
    {
        $this->syncId       = $syncId;
        $this->userId       = $userId;
        $this->totalRecords = $totalRecords;
    }

    /**
     * Broadcast ke public channel agar bot (yang tidak login) bisa menerimanya.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('rpa-jobs'), // Public channel, tidak perlu auth
        ];
    }

    /**
     * Nama event yang akan diterima bot via WebSocket.
     */
    public function broadcastAs(): string
    {
        return 'new-sync-job';
    }

    public function broadcastWith(): array
    {
        return [
            'sync_id'       => $this->syncId,
            'user_id'       => $this->userId,
            'total_records' => $this->totalRecords,
        ];
    }
}
