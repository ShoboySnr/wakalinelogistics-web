<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class ContactReceived extends Mailable
{
    use SerializesModels;

    public ContactMessage $messageModel;

    public function __construct(ContactMessage $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    public function build()
    {
        return $this->subject('We received your message — Waka Line Logistics')
            ->view('emails.contact_received')
            ->with(['messageModel' => $this->messageModel]);
    }
}
