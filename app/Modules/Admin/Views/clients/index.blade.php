@extends('Admin::layout')

@section('title', 'Clients')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your regular clients with saved pickup addresses</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
            Add New Client
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="mb-4 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('admin.clients') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, or email..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
                Search
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('admin.clients') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 whitespace-nowrap">
                Clear
            </a>
            @endif
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client / Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Terms</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $client->name }}</div>
                        @if($client->company_name)
                        <div class="text-xs text-gray-500">{{ $client->company_name }}</div>
                        @endif
                        @if($client->contact_person)
                        <div class="text-xs text-gray-400">Contact: {{ $client->contact_person }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $client->phone }}</div>
                        @if($client->alternate_phone)
                        <div class="text-xs text-gray-500">Alt: {{ $client->alternate_phone }}</div>
                        @endif
                        @if($client->email)
                        <div class="text-xs text-gray-500">{{ $client->email }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($client->city || $client->state)
                        <div class="text-sm text-gray-900">{{ $client->city }}{{ $client->city && $client->state ? ', ' : '' }}{{ $client->state }}</div>
                        @endif
                        <div class="text-xs text-gray-500 max-w-xs truncate" title="{{ $client->pickup_address }}">
                            {{ $client->pickup_address }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($client->business_type)
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($client->business_type) }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400">Not set</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($client->payment_terms)
                        <div class="text-sm text-gray-900">
                            @if($client->payment_terms === 'prepaid')
                                Prepaid
                            @elseif($client->payment_terms === 'postpaid')
                                Postpaid
                            @elseif($client->payment_terms === 'credit_30')
                                Net 30
                            @elseif($client->payment_terms === 'credit_60')
                                Net 60
                            @endif
                        </div>
                        @if($client->credit_limit)
                        <div class="text-xs text-gray-500">Limit: ₦{{ number_format($client->credit_limit, 2) }}</div>
                        @endif
                        @else
                        <span class="text-xs text-gray-400">Not set</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($client->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $client->orders()->count() }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.clients.edit', $client->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.clients.delete', $client->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this client?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="mt-2 text-sm">No clients found</p>
                        <a href="{{ route('admin.clients.create') }}" class="mt-4 inline-block px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                            Add Your First Client
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
    <div class="mt-4">
        {{ $clients->links() }}
    </div>
    @endif
</div>
@endsection
