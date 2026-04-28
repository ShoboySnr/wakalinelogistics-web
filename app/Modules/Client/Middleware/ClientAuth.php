<?php

namespace App\Modules\Client\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('client')->check()) {
            return redirect()->route('client.login');
        }

        $client = Auth::guard('client')->user();

        // Check if client is active
        if (!$client->is_active) {
            Auth::guard('client')->logout();
            return redirect()->route('client.login')
                           ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Check if dashboard access is enabled
        if (!$client->dashboard_enabled) {
            Auth::guard('client')->logout();
            return redirect()->route('client.login')
                           ->withErrors(['email' => 'Dashboard access has been disabled for your account.']);
        }

        return $next($request);
    }
}
