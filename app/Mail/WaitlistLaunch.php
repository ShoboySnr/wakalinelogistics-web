<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistLaunch extends Mailable
{
    use SerializesModels;

    public string $clientName;
    public string $activationUrl;

    public function __construct(string $clientName, string $token)
    {
        $this->clientName    = $clientName;
        $this->activationUrl = rtrim(env('FRONTEND_URL', 'https://app.wakalinelogistics.com'), '/') . '/activate/' . $token;
    }

    public function build()
    {
        return $this->subject("We're live! Activate Your Account Now 🎉")
            ->view('emails.waitlist_launch')
            ->with([
                'clientName'    => $this->clientName,
                'activationUrl' => $this->activationUrl,
            ]);
    }
}
