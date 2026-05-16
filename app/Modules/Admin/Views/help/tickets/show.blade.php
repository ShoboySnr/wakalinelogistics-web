@extends('Admin::layout')

@section('title', 'Ticket: ' . $ticket->ticket_number)

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.help.tickets') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $ticket->subject }}</h1>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->status_color }}">
                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
            </span>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->priority_color }}">
                {{ ucfirst($ticket->priority) }}
            </span>
        </div>
        <p class="text-sm text-gray-500 font-mono">{{ $ticket->ticket_number }}</p>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversation -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Messages -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Conversation</h3>
                </div>
                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto" id="messages-container">
                    @foreach($ticket->messages as $message)
                    <div class="flex {{ $message->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%]">
                            <div class="flex items-center gap-2 mb-1 {{ $message->sender_type === 'admin' ? 'justify-end' : '' }}">
                                @if($message->sender_type === 'admin')
                                <span class="text-xs text-gray-500">Support Team</span>
                                <span class="w-6 h-6 rounded-full bg-[#C1666B] flex items-center justify-center text-white text-xs">A</span>
                                @else
                                <span class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 text-xs">
                                    {{ strtoupper(substr($ticket->client->name ?? 'C', 0, 1)) }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $ticket->client->name ?? 'Client' }}</span>
                                @endif
                            </div>
                            <div class="px-4 py-3 rounded-lg {{ $message->sender_type === 'admin' ? 'bg-[#C1666B] text-white' : 'bg-gray-100 text-gray-900' }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $message->message }}</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-1 {{ $message->sender_type === 'admin' ? 'text-right' : '' }}">
                                {{ $message->created_at->format('d M Y, g:ia') }}
                            </p>
                        </div>
                    </div>
                    @endforeach

                    @if($ticket->messages->isEmpty())
                    <p class="text-center text-gray-500 text-sm py-8">No messages yet.</p>
                    @endif
                </div>

                <!-- Reply Form -->
                @if(!in_array($ticket->status, ['closed']))
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <form action="{{ route('admin.help.tickets.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="message" rows="4" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500"
                                placeholder="Type your reply...">{{ old('message') }}</textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-gray-500">Reply will be visible to the client</p>
                            <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover text-sm">
                                Send Reply
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-500 text-center">This ticket is closed.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ticket Details</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Client</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $ticket->client->name ?? 'Unknown' }}</dd>
                        <dd class="text-gray-500">{{ $ticket->client->email ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Category</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $ticket->category }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $ticket->created_at->format('d M Y, g:ia') }}</dd>
                    </div>
                    @if($ticket->resolved_at)
                    <div>
                        <dt class="text-gray-500">Resolved</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $ticket->resolved_at->format('d M Y, g:ia') }}</dd>
                    </div>
                    @endif
                    @if($ticket->assignedAdmin)
                    <div>
                        <dt class="text-gray-500">Assigned To</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $ticket->assignedAdmin->name }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Update Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
                <form action="{{ route('admin.help.tickets.status', $ticket->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <button type="submit" class="w-full px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover text-sm">
                        Update Status
                    </button>
                </form>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <form action="{{ route('admin.help.tickets.delete', $ticket->id) }}" method="POST"
                    onsubmit="return confirm('Delete this ticket permanently?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 text-red-700 bg-red-50 hover:bg-red-100 rounded-md border border-red-200 text-sm">
                        Delete Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Scroll to bottom of messages
    const container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;
</script>
@endsection
