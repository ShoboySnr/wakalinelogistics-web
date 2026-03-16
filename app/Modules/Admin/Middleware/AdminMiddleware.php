<?php

namespace App\Modules\Admin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/super-admin');
        }

        if (!Auth::user()->is_admin) {
            Auth::logout();
            return redirect('/super-admin')->withErrors(['error' => 'Unauthorized access.']);
        }

        return $next($request);
    }
}
