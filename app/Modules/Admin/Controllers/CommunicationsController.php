<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\ContactMessage;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function waitlist()
    {
        $waitlistClients = Client::where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('Admin::waitlist', compact('waitlistClients'));
    }

    public function sendActivationEmail(Request $request, $clientId)
    {
        $client = Client::where('id', $clientId)
            ->where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->firstOrFail();

        if (empty($client->waitlist_token)) {
            $client->waitlist_token = Str::random(64);
            $client->save();
        }

        try {
            Mail::to($client->email)->send(new \App\Mail\WaitlistLaunch($client->name, $client->waitlist_token));
            return redirect()->route('admin.waitlist')->with('success', "Activation email sent to {$client->name} ({$client->email}).");
        } catch (\Throwable $e) {
            Log::error('Single activation email failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
            return redirect()->route('admin.waitlist')->with('error', "Failed to send email to {$client->email}: " . $e->getMessage());
        }
    }

    public function sendSampleEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            Mail::to($request->email)->send(
                new \App\Mail\WaitlistLaunch('Sample User', Str::random(64))
            );
            return redirect()->route('admin.waitlist')->with('success', "Sample launch email sent to {$request->email}.");
        } catch (\Throwable $e) {
            Log::error('Sample launch email failed', ['error' => $e->getMessage()]);
            return redirect()->route('admin.waitlist')->with('error', 'Failed to send sample email: ' . $e->getMessage());
        }
    }

    public function sendLaunchEmails(Request $request)
    {
        $waitlistClients = Client::where('is_active', false)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->get();

        if ($waitlistClients->isEmpty()) {
            return redirect()->route('admin.waitlist')->with('info', 'No waitlist users found to email.');
        }

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

        $msg = "Launch emails sent: {$sent} delivered" . ($failed > 0 ? ", {$failed} failed." : ".");
        return redirect()->route('admin.waitlist')->with('success', $msg);
    }
}
