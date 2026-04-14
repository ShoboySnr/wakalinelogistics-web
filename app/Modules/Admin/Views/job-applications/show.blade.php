@extends('Admin::layout')

@section('title', 'Job Application Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.job-applications') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
                    ← Back to Applications
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $application->full_name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Applied for {{ $application->formatted_job_type }} on {{ $application->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $application->status_color }}">
                    {{ ucfirst($application->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Personal Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Full Name</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $application->full_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Age</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $application->age }} years</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Phone</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <a href="tel:{{ $application->phone }}" class="text-pink-600 hover:text-pink-800">{{ $application->phone }}</a>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Email</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @if($application->email)
                            <a href="mailto:{{ $application->email }}" class="text-pink-600 hover:text-pink-800">{{ $application->email }}</a>
                            @else
                            <span class="text-gray-400">Not provided</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500 uppercase">Address</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $application->address }}</p>
                    </div>
                </div>
            </div>

            <!-- License Information (for riders) -->
            @if($application->job_type === 'dispatch_rider' && $application->license_number)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    License Information
                </h2>
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">Driver's License Number</label>
                    <p class="mt-1 text-sm text-gray-900 font-mono">{{ $application->license_number }}</p>
                </div>
            </div>
            @endif

            <!-- Experience -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Experience
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Years of Experience</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $application->experience_years ?? 'Not specified' }}</p>
                    </div>
                    @if($application->previous_work)
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Previous Work Experience</label>
                        <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $application->previous_work }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Availability & Motivation -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    Additional Information
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Availability</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $application->availability ?? 'Not specified' }}</p>
                    </div>
                    @if($application->why_join)
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Why Join Wakaline?</label>
                        <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $application->why_join }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Update Status -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h2>
                <form method="POST" action="{{ route('admin.job-applications.update-status', $application->id) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500" required>
                                <option value="pending" {{ $application->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="reviewing" {{ $application->status === 'reviewing' ? 'selected' : '' }}>Reviewing</option>
                                <option value="shortlisted" {{ $application->status === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                                <option value="rejected" {{ $application->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="hired" {{ $application->status === 'hired' ? 'selected' : '' }}>Hired</option>
                            </select>
                        </div>
                        <div>
                            <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                            <textarea name="admin_notes" id="admin_notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500" placeholder="Add notes about this application...">{{ $application->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>

            <!-- Review Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Review Information</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Application Date</label>
                        <p class="mt-1 text-gray-900">{{ $application->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @if($application->reviewed_at)
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Last Reviewed</label>
                        <p class="mt-1 text-gray-900">{{ $application->reviewed_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @endif
                    @if($application->reviewer)
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Reviewed By</label>
                        <p class="mt-1 text-gray-900">{{ $application->reviewer->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
                <div class="space-y-2">
                    <a href="tel:{{ $application->phone }}" class="block w-full px-4 py-2 text-center text-white bg-green-600 rounded-md hover:bg-green-700">
                        Call Applicant
                    </a>
                    @if($application->email)
                    <a href="mailto:{{ $application->email }}" class="block w-full px-4 py-2 text-center text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Send Email
                    </a>
                    @endif
                    <form method="POST" action="{{ route('admin.job-applications.delete', $application->id) }}" onsubmit="return confirm('Are you sure you want to delete this application? This action cannot be undone.');" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700">
                            Delete Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
