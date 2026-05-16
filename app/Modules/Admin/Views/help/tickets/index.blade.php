@extends('Admin::layout')

@section('title', 'Support Tickets')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Support Tickets</h1>
            <p class="text-sm text-gray-500 mt-1">Manage client support requests</p>
        </div>
        <a href="{{ route('admin.help.faqs') }}" class="px-4 py-2 text-center text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 whitespace-nowrap">
            Manage FAQs
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Tickets</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['open'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Open</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] }}</p>
            <p class="text-xs text-gray-500 mt-1">In Progress</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['resolved'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Resolved</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by ticket #, subject, or client name..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
            <option value="">All Statuses</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
        <select name="priority" class="px-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
            <option value="">All Priorities</option>
            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
        </select>
        <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover text-sm">Filter</button>
        @if(request()->hasAny(['search', 'status', 'priority']))
        <a href="{{ route('admin.help.tickets') }}" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 text-sm">Clear</a>
        @endif
    </form>

    <!-- Tickets Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($ticket->unread_by_admin_count > 0)
                                <span class="w-2 h-2 bg-[#C1666B] rounded-full flex-shrink-0"></span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $ticket->subject }}</p>
                                    <p class="text-xs text-gray-500 font-mono">{{ $ticket->ticket_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-medium text-gray-900">{{ $ticket->client->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $ticket->client->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->priority_color }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $ticket->status_color }}">
                                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ticket->messages_count }}
                            @if($ticket->unread_by_admin_count > 0)
                            <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-[#C1666B] rounded-full">
                                {{ $ticket->unread_by_admin_count }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ticket->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.help.tickets.show', $ticket->id) }}" class="text-[#C1666B] hover:text-[#A85559]">View</a>
                                <form action="{{ route('admin.help.tickets.delete', $ticket->id) }}" method="POST" onsubmit="return confirm('Delete this ticket?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            No tickets found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $tickets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
