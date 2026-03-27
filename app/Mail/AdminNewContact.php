<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class AdminNewContact extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ContactMessage $messageModel;

    public function __construct(ContactMessage $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    public function build()
    {
        return $this->subject('New contact form submission')->view('emails.admin_new_contact')
            ->with(['messageModel' => $this->messageModel]);
    }
}
