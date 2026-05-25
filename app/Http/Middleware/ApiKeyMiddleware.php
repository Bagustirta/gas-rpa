<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Middleware keamanan sederhana untuk API endpoint LPG.
     * Gunakan header: X-API-Key: <nilai dari LPG_API_KEY di .env>
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('app.lpg_api_key');

        // Jika API key belum dikonfigurasi, tolak semua request
        if (empty($apiKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API Key belum dikonfigurasi di server.',
            ], 500);
        }

        $requestKey = $request->header('X-API-Key')
            ?? $request->query('api_key');

        if ($requestKey !== $apiKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. API Key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
