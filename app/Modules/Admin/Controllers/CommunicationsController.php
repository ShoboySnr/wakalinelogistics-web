<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class CommunicationsController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::orderBy('created_at', 'desc')->paginate(25);
        $messages = ContactMessage::orderBy('created_at', 'desc')->paginate(25);

        return view('Admin::communications', compact('subscriptions', 'messages'));
    }

    public function updateSubscription(Request $request, $id)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:subscriptions,email,' . $id,
        ]);

        $sub = Subscription::findOrFail($id);
        $sub->email = $data['email'];
        $sub->save();

        return response()->json(['success' => true, 'subscription' => $sub, 'message' => 'Subscription updated successfully']);
    }

    public function deleteSubscription($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->delete();

        return redirect()->route('admin.communications')->with('success', 'Subscription deleted');
    }

    public function updateMessage(Request $request, $id)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'message' => 'required|string',
        ]);

        $m = ContactMessage::findOrFail($id);
        $m->first_name = $data['first_name'];
        $m->last_name = $data['last_name'];
        $m->email = $data['email'];
        $m->phone = $data['phone'];
        $m->message = $data['message'];
        $m->save();

        return response()->json(['success' => true, 'data' => $m, 'message' => 'Message updated successfully']);
    }

    public function deleteMessage($id)
    {
        $m = ContactMessage::findOrFail($id);
        $m->delete();
        $req = request();
        
        return response()->json(['success' => true, 'message' => 'Message deleted successfully']);
    }
}
