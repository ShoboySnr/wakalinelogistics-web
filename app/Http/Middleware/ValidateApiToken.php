<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next, ?string $expected = null): Response
    {
        $token = $request->header('X-API-Token') ?? $request->bearerToken();

        $metterToken = config('services.metter_api.token');
        $frontendToken = config('services.frontend_api.token');

        if ($expected === 'frontend') {
            $accepted = [$frontendToken];
        } elseif ($expected === 'metter') {
            $accepted = [$metterToken];
        } else {
            $accepted = array_filter([$metterToken, $frontendToken]);
        }

        if (!$token || !in_array($token, $accepted, true)) {
            if ($expected === 'frontend') {
                $errorMessage = 'Unauthorized. FRONTEND API token required.';
                $errorCode = 'INVALID_FRONTEND_API_TOKEN';
            } elseif ($expected === 'metter') {
                $errorMessage = 'Unauthorized. Valid METTER API token required.';
                $errorCode = 'INVALID_METTER_API_TOKEN';
            } else {
                $errorMessage = 'Unauthorized. Valid API token required.';
                $errorCode = 'INVALID_API_TOKEN';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $errorCode
            ], 401);
        }
        
        return $next($request);
    }
}
