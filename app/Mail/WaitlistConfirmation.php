<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistConfirmation extends Mailable
{
    use SerializesModels;

    public string $clientName;

    public function __construct(string $clientName)
    {
        $this->clientName = $clientName;
    }

    public function build()
    {
        return $this->subject("Congrats! You're on the waitlist! 🎉")
            ->view('emails.waitlist_confirmation')
            ->with(['clientName' => $this->clientName]);
    }
}
