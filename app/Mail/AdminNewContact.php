<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class AdminNewContact extends Mailable
{
    use SerializesModels;

    public ContactMessage $messageModel;

    public function __construct(ContactMessage $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    public function build()
    {
        return $this->subject('New Enquiry: ' . $this->messageModel->first_name . ' ' . $this->messageModel->last_name)
            ->view('emails.admin_new_contact')
            ->with(['messageModel' => $this->messageModel]);
    }
}
