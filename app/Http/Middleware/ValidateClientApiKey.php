<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Admin\Models\Client;

class ValidateClientApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from Authorization header or api_key parameter
        $apiKey = $this->getApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => 'unauthorized'
            ], 401);
        }

        // Find client by API key
        $client = Client::where('api_key', $apiKey)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key. Please check your API key and try again.',
                'error' => 'invalid_api_key'
            ], 401);
        }

        // Check if API access is enabled
        if (!$client->api_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'API access is disabled for this account. Please contact support.',
                'error' => 'api_access_disabled'
            ], 403);
        }

        // Check if client account is active
        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'error' => 'account_inactive'
            ], 403);
        }

        // Record API usage
        $client->recordApiUsage();

        // Attach client to request for use in controllers
        $request->merge(['authenticated_client' => $client]);
        $request->attributes->set('client', $client);

        return $next($request);
    }

    /**
     * Extract API key from request
     */
    private function getApiKey(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Check X-API-Key header
        $apiKeyHeader = $request->header('X-API-Key');
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }

        // Check request body or query parameter
        return $request->input('api_key');
    }
}
