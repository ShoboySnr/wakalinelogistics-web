<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\FAQ;
use App\Modules\Admin\Models\SupportTicket;
use App\Modules\Admin\Models\TicketMessage;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    // ─── FAQs ────────────────────────────────────────────────────────────────

    public function getFaqs(Request $request)
    {
        $query = FAQ::active()->orderBy('category')->orderBy('sort_order')->orderBy('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $faqs = $query->get();
        $categories = FAQ::active()->pluck('category')->unique()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'faqs' => $faqs,
                'categories' => $categories,
            ],
        ]);
    }

    // ─── Tickets ─────────────────────────────────────────────────────────────

    public function getTickets(Request $request)
    {
        $client = $request->user();

        $tickets = SupportTicket::where('client_id', $client->id)
            ->withCount('messages')
            ->with('latestMessage')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function createTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'category' => 'nullable|string|max:100',
        ]);

        $client = $request->user();

        $ticket = SupportTicket::create([
            'client_id' => $client->id,
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'category' => $validated['category'] ?? 'General',
            'status' => 'open',
        ]);

        // Create initial message from the description
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'client',
            'sender_id' => $client->id,
            'message' => $validated['description'],
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => $ticket,
        ], 201);
    }

    public function getTicket(Request $request, $id)
    {
        $client = $request->user();

        $ticket = SupportTicket::where('client_id', $client->id)
            ->with(['messages'])
            ->findOrFail($id);

        // Mark admin messages as read by client
        $ticket->messages()->where('sender_type', 'admin')->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function addMessage(Request $request, $id)
    {
        $client = $request->user();

        $ticket = SupportTicket::where('client_id', $client->id)
            ->whereNotIn('status', ['closed'])
            ->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'client',
            'sender_id' => $client->id,
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        // Re-open resolved tickets when client replies
        if (in_array($ticket->status, ['resolved'])) {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    public function closeTicket(Request $request, $id)
    {
        $client = $request->user();

        $ticket = SupportTicket::where('client_id', $client->id)->findOrFail($id);
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully',
        ]);
    }
}
