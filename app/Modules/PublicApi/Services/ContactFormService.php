<?php

namespace App\Modules\PublicApi\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\ContactMessage;
use App\Mail\ContactReceived;
use App\Mail\AdminNewContact;

class ContactFormService
{
    public function send(array $data): bool
    {
        $message = ContactMessage::create([
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'],
            'message' => $data['message'] ?? '',
        ]);

        try {
            Mail::to($message->email)->send(new ContactReceived($message));
        } catch (\Exception $e) {
            Log::error('Contact received email failed', ['error' => $e->getMessage()]);
        }

        try {
            $adminEmail = config('app.admin_email', 'support@wakalinelogistics.com');
            Mail::to($adminEmail)->send(new AdminNewContact($message));
        } catch (\Exception $e) {
            Log::error('Admin contact notification email failed', ['error' => $e->getMessage()]);
        }

        return true;
    }
}
