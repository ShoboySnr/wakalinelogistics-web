<?php

namespace App\Modules\Admin\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have admin access.',
            ], 403);
        }

        return $next($request);
    }
}
