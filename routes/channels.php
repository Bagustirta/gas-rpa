<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel private untuk progress audit per user
Broadcast::channel('audit-progress.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Channel private untuk progress sinkronisasi RPA per user
Broadcast::channel('rpa-progress.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('data-gas.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});