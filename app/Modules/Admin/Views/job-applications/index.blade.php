@extends('Admin::layout')

@section('title', 'Job Applications')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Job Applications</h1>
            <p class="text-sm text-gray-500 mt-1">Review and manage job applications</p>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="mb-4 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('admin.job-applications') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, or email..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            
            <select name="job_type" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                <option value="">All Job Types</option>
                <option value="dispatch_rider" {{ request('job_type') === 'dispatch_rider' ? 'selected' : '' }}>Dispatch Rider</option>
                <option value="warehouse_staff" {{ request('job_type') === 'warehouse_staff' ? 'selected' : '' }}>Warehouse Staff</option>
                <option value="customer_service" {{ request('job_type') === 'customer_service' ? 'selected' : '' }}>Customer Service</option>
            </select>
            
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="reviewing" {{ request('status') === 'reviewing' ? 'selected' : '' }}>Reviewing</option>
                <option value="shortlisted" {{ request('status') === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="hired" {{ request('status') === 'hired' ? 'selected' : '' }}>Hired</option>
            </select>
            
            <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
                Search
            </button>
            @if(request('search') || request('job_type') || request('status'))
            <a href="{{ route('admin.job-applications') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 whitespace-nowrap">
                Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-xs font-medium text-yellow-800 uppercase">Pending</div>
            <div class="text-2xl font-bold text-yellow-900">{{ $applications->where('status', 'pending')->count() }}</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-xs font-medium text-blue-800 uppercase">Reviewing</div>
            <div class="text-2xl font-bold text-blue-900">{{ $applications->where('status', 'reviewing')->count() }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-xs font-medium text-green-800 uppercase">Shortlisted</div>
            <div class="text-2xl font-bold text-green-900">{{ $applications->where('status', 'shortlisted')->count() }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-xs font-medium text-red-800 uppercase">Rejected</div>
            <div class="text-2xl font-bold text-red-900">{{ $applications->where('status', 'rejected')->count() }}</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-xs font-medium text-purple-800 uppercase">Hired</div>
            <div class="text-2xl font-bold text-purple-900">{{ $applications->where('status', 'hired')->count() }}</div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Job Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Experience</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applied</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($applications as $application)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $application->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $application->address }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-900">{{ $application->formatted_job_type }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $application->phone }}</div>
                        @if($application->email)
                        <div class="text-xs text-gray-500">{{ $application->email }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-900">{{ $application->age }} years</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-900">{{ $application->experience_years ?? 'N/A' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $application->status_color }}">
                            {{ ucfirst($application->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $application->created_at->format('M d, Y') }}</div>
                        <div class="text-xs text-gray-500">{{ $application->created_at->format('h:i A') }}</div>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.job-applications.show', $application->id) }}" class="text-pink-600 hover:text-pink-900 mr-3">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm">No job applications found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applications->hasPages())
    <div class="mt-4">
        {{ $applications->links() }}
    </div>
    @endif
</div>
@endsection
