<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Modules\Admin\Models\Client;

class ClientAuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('client.dashboard');
        }

        return view('Client::auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Check if client exists and has dashboard access
        $client = Client::where('email', $credentials['email'])->first();

        if (!$client) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (!$client->is_active) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->onlyInput('email');
        }

        if (!$client->dashboard_enabled) {
            return back()->withErrors([
                'email' => 'Dashboard access is not enabled for your account. Please contact support.',
            ])->onlyInput('email');
        }

        if (!$client->password) {
            return back()->withErrors([
                'email' => 'Your account is not set up for dashboard access. Please contact support.',
            ])->onlyInput('email');
        }

        // Attempt authentication
        if (Auth::guard('client')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Record login
            $client->recordLogin();

            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
