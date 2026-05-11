@extends('Admin::layout')

@section('title', 'Bikes Management')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bikes Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage bikes and their documents</p>
        </div>
        <a href="{{ route('admin.bikes.create') }}" class="px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Register New Bike
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm font-medium text-gray-600">Total Bikes</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm font-medium text-gray-600">Active</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm font-medium text-gray-600">Assigned to Riders</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['assigned'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm font-medium text-gray-600">Expiring Documents</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['expiring_docs'] }}</p>
        </div>
    </div>

    <!-- Alerts for Expired/Expiring Documents -->
    @if($stats['expired_docs'] > 0)
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">
                    <strong>{{ $stats['expired_docs'] }} bike(s)</strong> have expired documents. 
                    <a href="{{ route('admin.bikes') }}?expired=1" class="font-medium underline">View bikes with expired documents</a>
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($stats['expiring_docs'] > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>{{ $stats['expiring_docs'] }} bike(s)</strong> have documents expiring within 30 days. 
                    <a href="{{ route('admin.bikes') }}?expiring=1" class="font-medium underline">View bikes with expiring documents</a>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.bikes') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search bike number, plate, brand..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Filter
            </button>
            <a href="{{ route('admin.bikes') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Clear
            </a>
        </form>
    </div>

    <!-- Bikes Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bike Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Rider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($bikes as $bike)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $bike->bike_number }}</div>
                            <div class="text-sm text-gray-500">{{ $bike->brand }} {{ $bike->model }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $bike->plate_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($bike->status == 'active')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @elseif($bike->status == 'maintenance')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Maintenance</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($bike->assignedRider)
                                <div class="text-sm text-gray-900">{{ $bike->assignedRider->name }}</div>
                                <div class="text-xs text-gray-500">Since {{ $bike->assignment_date?->format('M d, Y') }}</div>
                            @else
                                <span class="text-gray-400">Not assigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($bike->hasExpiredDocuments())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    <svg class="inline h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Expired
                                </span>
                            @elseif($bike->hasExpiringDocuments(30))
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    <svg class="inline h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Expiring Soon
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <svg class="inline h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Up to Date
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.bikes.show', $bike->id) }}" class="brand-accent-text hover:underline mr-3">View</a>
                            <a href="{{ route('admin.bikes.edit', $bike->id) }}" class="brand-accent-text hover:underline mr-3">Edit</a>
                            <form method="POST" action="{{ route('admin.bikes.delete', $bike->id) }}" onsubmit="return confirm('Are you sure you want to delete this bike?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No bikes registered yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($bikes->hasPages())
        <div class="px-6 py-4 bg-gray-50">
            {{ $bikes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
