<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-API-Token') ?? $request->bearerToken();
        $validTokens = array_filter([
            config('services.metter_api.token'),
            config('services.frontend_api.token'),
        ]);

        if (!$token || !in_array($token, (array) $validTokens, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Valid API token required.',
                'error' => 'INVALID_API_TOKEN'
            ], 401);
        }
        
        return $next($request);
    }
}
