@extends('Admin::layout')

@section('title', 'Riders')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Riders</h1>
            <p class="text-sm text-gray-500 mt-1">Manage delivery riders</p>
        </div>
        <a href="{{ route('admin.riders.create') }}" class="px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
            Add New Rider
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($riders as $rider)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.riders.show', $rider->id) }}" class="text-sm font-medium brand-accent-text hover:underline">{{ $rider->name }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $rider->email }}</div>
                        <div class="text-sm text-gray-500">{{ $rider->phone }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($rider->vehicle_type)
                            <div class="text-sm text-gray-900">{{ ucfirst($rider->vehicle_type) }}</div>
                            @if($rider->vehicle_number)
                                <div class="text-sm text-gray-500">{{ $rider->vehicle_number }}</div>
                            @endif
                        @else
                            <span class="text-sm text-gray-400">Not set</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($rider->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @elseif($rider->status === 'inactive')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Suspended</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $rider->orders_count ?? 0 }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ number_format($rider->rating, 2) }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.riders.show', $rider->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <a href="{{ route('admin.riders.edit', $rider->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.riders.delete', $rider->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this rider?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="mt-2">No riders found</p>
                        <a href="{{ route('admin.riders.create') }}" class="mt-4 inline-block brand-accent-text">Add your first rider</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($riders->hasPages())
    <div class="mt-6">
        {{ $riders->links() }}
    </div>
    @endif
</div>
@endsection
