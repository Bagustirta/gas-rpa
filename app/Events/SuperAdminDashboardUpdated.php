<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuperAdminDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $stats;
    public array $serverStatus;
    public array $recentTransactions;

    public function __construct(array $stats, array $serverStatus, array $recentTransactions)
    {
        $this->stats = $stats;
        $this->serverStatus = $serverStatus;
        $this->recentTransactions = $recentTransactions;
    }

    /**
     * Channel publik khusus super admin dashboard.
     * Tidak perlu auth karena halaman ini sendiri sudah diproteksi middleware.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('superadmin-dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }
}
