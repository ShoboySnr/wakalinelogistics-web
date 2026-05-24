<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Modules\PublicApi\Controllers\NewsletterController;
use App\Modules\PublicApi\Controllers\ContactFormController;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\FeatureSuggestion;

Route::prefix('v1/public')->name('api.public.')->middleware('api.token:frontend')->group(function () {
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::post('contact/enquiry', [ContactFormController::class, 'send'])->name('contact.send');
    Route::get('newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    Route::post('orders/submit-public', [\App\Http\Controllers\Api\OrderController::class, 'submitOrder']);

    Route::get('suggestions', function () {
        $suggestions = FeatureSuggestion::orderByDesc('upvotes')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'title'          => $s->title,
                'description'    => $s->description,
                'category'       => $s->category,
                'status'         => $s->status,
                'upvotes'        => $s->upvotes,
                'admin_response' => $s->admin_response,
                'created_at'     => $s->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $suggestions]);
    });

    Route::post('waitlist/join', function (Request $request) {
        $request->validate([
            'name'  => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:clients,email',
        ], [
            'email.unique' => 'This email is already on the waitlist.',
        ]);

        $client = Client::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'phone'             => '',
            'pickup_address'    => '',
            'is_active'         => false,
            'dashboard_enabled' => false,
            'waitlist_token'    => Str::random(64),
        ]);

        try {
            Mail::to($client->email)->send(new \App\Mail\WaitlistConfirmation($client->name));
        } catch (\Throwable $e) {
            Log::warning('Waitlist confirmation email failed', ['email' => $client->email, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => "You're on the list! You'll receive ₦2,000 in delivery credits when we launch.",
        ], 201);
    })->name('waitlist.join');

    // Verify activation token — used by the set-password page to prefill name/email
    Route::get('waitlist/activate/{token}', function (string $token) {
        $client = Client::where('waitlist_token', $token)
            ->where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'This activation link is invalid or has already been used.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['name' => $client->name, 'email' => $client->email],
        ]);
    })->name('waitlist.verify');

    // Activate account — set password, enable dashboard, credit ₦2,000, return auth token
    Route::post('waitlist/activate', function (Request $request) {
        $request->validate([
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $client = Client::where('waitlist_token', $request->token)
            ->where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'This activation link is invalid or has already been used.',
            ], 404);
        }

        $client->password          = Hash::make($request->password);
        $client->is_active         = true;
        $client->dashboard_enabled = true;
        $client->email_verified_at = now();
        $client->waitlist_token    = null;
        $client->save();

        if (!$client->signup_bonus_credited) {
            try {
                $credits = $client->getOrCreateCredits();
                $credits->addCredits(2000);
                $client->signup_bonus_credited = true;
                $client->save();
            } catch (\Throwable $e) {
                Log::error('Waitlist activation credit failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        $client->recordLogin();
        $authToken = $client->createToken('wakaline-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => "Welcome to Waka Line! Your ₦2,000 credit is now in your account.",
            'data'    => [
                'user' => [
                    'id'           => $client->id,
                    'name'         => $client->name,
                    'email'        => $client->email,
                    'phone'        => $client->phone,
                    'role'         => 'client',
                    'account_type' => 'client',
                ],
                'token' => $authToken,
            ],
        ]);
    })->name('waitlist.activate');
});

// Admin-only: trigger launch-day emails to all waitlist users
// Protected by X-Admin-Secret header matching ADMIN_SECRET in .env
Route::post('v1/admin/waitlist/send-launch-emails', function (Request $request) {
    $secret = $request->header('X-Admin-Secret');
    if (!$secret || $secret !== env('ADMIN_SECRET')) {
        return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
    }

    $waitlistClients = Client::where('is_active', false)
        ->where(function ($q) {
            $q->whereNull('password')->orWhere('password', '');
        })
        ->get();

    $sent   = 0;
    $failed = 0;

    foreach ($waitlistClients as $client) {
        if (empty($client->waitlist_token)) {
            $client->waitlist_token = Str::random(64);
            $client->save();
        }

        try {
            Mail::to($client->email)->send(new \App\Mail\WaitlistLaunch($client->name, $client->waitlist_token));
            $sent++;
        } catch (\Throwable $e) {
            Log::error('Launch email failed', ['email' => $client->email, 'error' => $e->getMessage()]);
            $failed++;
        }
    }

    return response()->json([
        'success' => true,
        'message' => "Done. {$sent} sent, {$failed} failed out of {$waitlistClients->count()} waitlist users.",
        'data'    => ['sent' => $sent, 'failed' => $failed, 'total' => $waitlistClients->count()],
    ]);
});
