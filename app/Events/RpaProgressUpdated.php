<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RpaProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $message;
    public $transaksiId;
    public $status;
    public $summary;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $message, $transaksiId = null, $status = null, $summary = [])
    {
        $this->userId = $userId;
        $this->message = $message;
        $this->transaksiId = $transaksiId;
        $this->status = $status;
        $this->summary = $summary;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('rpa-progress.' . $this->userId),
        ];
    }
}
