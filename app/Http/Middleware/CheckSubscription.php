<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow if not logged in (will be caught by auth middleware anyway)
        if (!$user) {
            return $next($request);
        }

        // Super Admin bypasses everything
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Check if explicitly non-aktif
        if ($user->subscription_status === 'non-aktif') {
            return redirect()->route('dashboard')->with('error', 'Akses Ditolak: Masa langganan Anda telah habis. Silakan perpanjang untuk menggunakan fitur ini.');
        }

        // Check if expiration date has passed
        if ($user->subscription_expired_at && $user->subscription_expired_at->isPast()) {
            // Auto update to non-aktif if it's not already
            if ($user->subscription_status !== 'non-aktif') {
                $user->update(['subscription_status' => 'non-aktif']);
            }
            return redirect()->route('dashboard')->with('error', 'Akses Ditolak: Masa langganan Anda telah habis. Silakan perpanjang untuk menggunakan fitur ini.');
        }

        return $next($request);
    }
}
