<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\FAQ;
use App\Modules\Admin\Models\SupportTicket;
use App\Modules\Admin\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    // ─── FAQs ────────────────────────────────────────────────────────────────

    public function faqs(): JsonResponse
    {
        $faqs = FAQ::orderBy('category')->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $faqs->map(fn (FAQ $f) => $this->faqData($f)),
            'categories' => $faqs->pluck('category')->unique()->values(),
        ]);
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $faq = FAQ::create($this->faqRules($request));

        return response()->json(['success' => true, 'message' => 'FAQ created successfully', 'data' => $this->faqData($faq)], 201);
    }

    public function updateFaq(Request $request, int $id): JsonResponse
    {
        $faq = FAQ::findOrFail($id);
        $faq->update($this->faqRules($request));

        return response()->json(['success' => true, 'message' => 'FAQ updated successfully', 'data' => $this->faqData($faq)]);
    }

    public function deleteFaq(int $id): JsonResponse
    {
        FAQ::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'FAQ deleted successfully']);
    }

    // ─── Support Tickets ─────────────────────────────────────────────────────

    public function tickets(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['client', 'latestMessage'])->withCount(['messages', 'unreadByAdmin']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(min(100, max(5, (int) $request->get('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($tickets->items())->map(fn (SupportTicket $t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status,
                'priority' => $t->priority,
                'client' => $t->client ? ['id' => $t->client->id, 'name' => $t->client->name] : null,
                'messages_count' => $t->messages_count,
                'unread_count' => $t->unread_by_admin_count,
                'created_at' => $t->created_at,
            ]),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'stats' => [
                'total' => SupportTicket::count(),
                'open' => SupportTicket::where('status', 'open')->count(),
                'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
                'resolved' => SupportTicket::where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function showTicket(int $id): JsonResponse
    {
        $ticket = SupportTicket::with(['client', 'messages', 'assignedAdmin'])->findOrFail($id);

        $ticket->messages()->where('sender_type', 'client')->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'client' => $ticket->client ? ['id' => $ticket->client->id, 'name' => $ticket->client->name, 'email' => $ticket->client->email] : null,
                'assigned_to' => $ticket->assigned_to,
                'assigned_admin' => $ticket->assignedAdmin?->name,
                'resolved_at' => $ticket->resolved_at,
                'created_at' => $ticket->created_at,
                'messages' => $ticket->messages->map(fn (TicketMessage $m) => [
                    'id' => $m->id,
                    'sender_type' => $m->sender_type,
                    'message' => $m->message,
                    'is_read' => (bool) $m->is_read,
                    'created_at' => $m->created_at,
                ])->values(),
                'admins' => User::where('is_admin', true)->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function replyTicket(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $validated = $request->validate(['message' => 'required|string|max:5000']);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json(['success' => true, 'message' => 'Reply sent successfully']);
    }

    public function updateTicketStatus(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $status = $request->validate(['status' => 'required|in:open,in_progress,resolved,closed'])['status'];

        $updates = ['status' => $status];
        if ($status === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif ($status === 'closed') {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        return response()->json(['success' => true, 'message' => 'Ticket status updated']);
    }

    public function assignTicket(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);
        $validated = $request->validate(['assigned_to' => 'nullable|exists:users,id']);

        $ticket->update($validated);

        return response()->json(['success' => true, 'message' => 'Ticket assigned successfully']);
    }

    public function deleteTicket(int $id): JsonResponse
    {
        SupportTicket::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Ticket deleted']);
    }

    private function faqRules(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);
    }

    private function faqData(FAQ $f): array
    {
        return [
            'id' => $f->id,
            'question' => $f->question,
            'answer' => $f->answer,
            'category' => $f->category,
            'sort_order' => $f->sort_order,
            'is_active' => (bool) $f->is_active,
        ];
    }
}
