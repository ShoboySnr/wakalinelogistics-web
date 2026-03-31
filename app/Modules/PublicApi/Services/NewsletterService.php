<?php

namespace App\Modules\PublicApi\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Subscription;
use App\Mail\SubscriptionConfirmation;
use App\Mail\AdminNewSubscription;

class NewsletterService
{
    public function subscribe(string $email): bool
    {
        $subscription = Subscription::firstOrCreate([
            'email' => strtolower($email),
        ]);

        try {
            Mail::to($subscription->email)->queue(new SubscriptionConfirmation($subscription));

            $adminEmail = config('app.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new AdminNewSubscription($subscription));
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue subscription emails', ['error' => $e->getMessage()]);
        }

        return true;
    }

    public function unsubscribe(string $email): bool
    {
        $sub = Subscription::where('email', strtolower($email))->first();
        if (! $sub) return false;
        try {
            $sub->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe', ['error' => $e->getMessage(), 'email' => $email]);
            return false;
        }
    }
}
