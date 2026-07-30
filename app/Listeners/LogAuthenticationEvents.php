<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;
use App\Models\ActivityLog;

class LogAuthenticationEvents
{
    public function handleLogin(Login $event)
    {
        $this->logActivity($event->user->id ?? null, 'login', 'User berhasil login ke sistem.');
    }

    public function handleLogout(Logout $event)
    {
        $this->logActivity($event->user->id ?? null, 'logout', 'User keluar dari sistem.');
    }

    public function handleFailed(Failed $event)
    {
        $this->logActivity($event->user->id ?? null, 'failed_login', 'Percobaan login gagal untuk email: ' . ($event->credentials['email'] ?? 'unknown'));
    }

    protected function logActivity($userId, $action, $description)
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'latitude' => Request::input('latitude'),
            'longitude' => Request::input('longitude'),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
