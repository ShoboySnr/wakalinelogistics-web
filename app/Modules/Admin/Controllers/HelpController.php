<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\FAQ;
use App\Modules\Admin\Models\SupportTicket;
use App\Modules\Admin\Models\TicketMessage;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    // ─── FAQs ────────────────────────────────────────────────────────────────

    public function faqs()
    {
        $faqs = FAQ::orderBy('category')->orderBy('sort_order')->orderBy('id')->get();
        $categories = $faqs->pluck('category')->unique()->values();
        return view('Admin::help.faqs.index', compact('faqs', 'categories'));
    }

    public function createFaq()
    {
        $categories = FAQ::pluck('category')->unique()->values()->toArray();
        return view('Admin::help.faqs.create', compact('categories'));
    }

    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        FAQ::create($validated);

        return redirect()->route('admin.help.faqs')->with('success', 'FAQ created successfully');
    }

    public function editFaq($id)
    {
        $faq = FAQ::findOrFail($id);
        $categories = FAQ::pluck('category')->unique()->values()->toArray();
        return view('Admin::help.faqs.edit', compact('faq', 'categories'));
    }

    public function updateFaq(Request $request, $id)
    {
        $faq = FAQ::findOrFail($id);

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $faq->update($validated);

        return redirect()->route('admin.help.faqs')->with('success', 'FAQ updated successfully');
    }

    public function deleteFaq($id)
    {
        FAQ::findOrFail($id)->delete();
        return back()->with('success', 'FAQ deleted successfully');
    }

    // ─── Support Tickets ─────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $query = SupportTicket::with(['client', 'latestMessage'])
            ->withCount(['messages', 'unreadByAdmin']);

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
                  ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
        ];

        return view('Admin::help.tickets.index', compact('tickets', 'stats'));
    }

    public function showTicket($id)
    {
        $ticket = SupportTicket::with(['client', 'messages', 'assignedAdmin'])->findOrFail($id);

        // Mark client messages as read
        $ticket->messages()->where('sender_type', 'client')->where('is_read', false)->update(['is_read' => true]);

        return view('Admin::help.tickets.show', compact('ticket'));
    }

    public function replyTicket(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        // Move to in_progress if still open
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply sent successfully');
    }

    public function updateTicketStatus(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif ($validated['status'] === 'closed') {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        return back()->with('success', 'Ticket status updated');
    }

    public function assignTicket(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update($validated);

        return back()->with('success', 'Ticket assigned successfully');
    }

    public function deleteTicket($id)
    {
        SupportTicket::findOrFail($id)->delete();
        return redirect()->route('admin.help.tickets')->with('success', 'Ticket deleted');
    }
}
