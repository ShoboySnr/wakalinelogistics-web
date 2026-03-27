<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\ContactMessage;

class CommunicationsController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::orderBy('created_at', 'desc')->paginate(25);
        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(25);

        return view('Admin::communications', compact('subscriptions', 'messages'));
    }
}
