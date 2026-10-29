<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Subscription;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CommunicationsController extends Controller
{
    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = Subscription::latest()->paginate(min(100, max(5, (int) $request->get('per_page', 25))));

        return $this->paginated($subscriptions, fn (Subscription $s) => [
            'id' => $s->id,
            'email' => $s->email,
            'created_at' => $s->created_at,
        ]);
    }

    public function updateSubscription(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email|unique:subscriptions,email,' . $id]);
        $sub = Subscription::findOrFail($id);
        $sub->update($data);

        return response()->json(['success' => true, 'message' => 'Subscription updated successfully', 'data' => $sub]);
    }

    public function deleteSubscription(int $id): JsonResponse
    {
        Subscription::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Subscription deleted']);
    }

    public function messages(Request $request): JsonResponse
    {
        $messages = ContactMessage::latest()->paginate(min(100, max(5, (int) $request->get('per_page', 25))));

        return $this->paginated($messages, fn (ContactMessage $m) => [
            'id' => $m->id,
            'first_name' => $m->first_name,
            'last_name' => $m->last_name,
            'email' => $m->email,
            'phone' => $m->phone,
            'message' => $m->message,
            'created_at' => $m->created_at,
        ]);
    }

    public function updateMessage(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'message' => 'required|string',
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->update($data);

        return response()->json(['success' => true, 'message' => 'Message updated successfully', 'data' => $message]);
    }

    public function deleteMessage(int $id): JsonResponse
    {
        ContactMessage::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Message deleted successfully']);
    }

    public function waitlist(): JsonResponse
    {
        $clients = $this->waitlistQuery()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $clients->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'company_name' => $c->company_name,
                'activation_sent' => ! empty($c->waitlist_token),
                'created_at' => $c->created_at,
            ]),
            'stats' => ['total' => $clients->count()],
        ]);
    }

    public function sendActivationEmail(int $clientId): JsonResponse
    {
        $client = $this->waitlistQuery()->where('id', $clientId)->firstOrFail();

        if (empty($client->waitlist_token)) {
            $client->update(['waitlist_token' => Str::random(64)]);
        }

        try {
            Mail::to($client->email)->send(new \App\Mail\WaitlistLaunch($client->name, $client->waitlist_token));

            return response()->json(['success' => true, 'message' => "Activation email sent to {$client->name} ({$client->email})."]);
        } catch (\Throwable $e) {
            Log::error('Single activation email failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => "Failed to send email to {$client->email}."], 500);
        }
    }

    public function sendSampleEmail(Request $request): JsonResponse
    {
        $email = $request->validate(['email' => 'required|email'])['email'];

        try {
            Mail::to($email)->send(new \App\Mail\WaitlistLaunch('Sample User', Str::random(64)));

            return response()->json(['success' => true, 'message' => "Sample launch email sent to {$email}."]);
        } catch (\Throwable $e) {
            Log::error('Sample launch email failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to send sample email.'], 500);
        }
    }

    public function sendLaunchEmails(): JsonResponse
    {
        $clients = $this->waitlistQuery()->get();

        if ($clients->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'No waitlist users found to email.', 'data' => ['sent' => 0, 'failed' => 0]]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($clients as $client) {
            if (empty($client->waitlist_token)) {
                $client->update(['waitlist_token' => Str::random(64)]);
            }

            try {
                Mail::to($client->email)->send(new \App\Mail\WaitlistLaunch($client->name, $client->waitlist_token));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Launch email failed', ['email' => $client->email, 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Launch emails sent: {$sent} delivered" . ($failed > 0 ? ", {$failed} failed." : "."),
            'data' => ['sent' => $sent, 'failed' => $failed],
        ]);
    }

    private function waitlistQuery()
    {
        return Client::where('is_active', false)
            ->where(fn ($q) => $q->whereNull('password')->orWhere('password', ''));
    }

    private function paginated($paginator, callable $map): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map($map),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
