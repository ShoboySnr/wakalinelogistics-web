@extends('Admin::layout')

@section('title', 'User Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Users
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- User Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Joined on {{ $user->created_at->format('M d, Y \a\t h:i A') }}
                            </p>
                            @if($user->id === auth()->id())
                                <p class="text-sm text-blue-600 mt-1">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        This is you
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-6">
                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Contact Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
                            </div>
                            @if($user->phone)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $user->phone }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Account Details -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Account Details</h3>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">User ID</label>
                                    <p class="mt-1 text-sm text-gray-900">#{{ $user->id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Account Type</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        @if($user->is_admin)
                                            Super Administrator
                                        @else
                                            Regular User
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F d, Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('F d, Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Status -->
                    @if($user->email_verified_at || $user->provider)
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Verification & Authentication</h3>
                        <div class="space-y-3">
                            @if($user->email_verified_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Verification</label>
                                <p class="mt-1 text-sm text-green-600 font-semibold">✓ Verified on {{ $user->email_verified_at->format('F d, Y') }}</p>
                            </div>
                            @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Verification</label>
                                <p class="mt-1 text-sm text-yellow-600 font-semibold">⚠ Not Verified</p>
                            </div>
                            @endif

                            @if($user->provider)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Authentication Provider</label>
                                <p class="mt-1 text-sm text-gray-900">{{ ucfirst($user->provider) }}</p>
                                @if($user->provider_id)
                                    <p class="text-xs text-gray-500">Provider ID: {{ $user->provider_id }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status & Actions -->
        <div class="space-y-6">
            <!-- User Role -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">User Role</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Role</label>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($user->is_admin) bg-purple-100 text-purple-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @if($user->is_admin)
                                Super Administrator
                            @else
                                Regular User
                            @endif
                        </span>
                    </div>

                    @if($user->id !== auth()->id())
                    <div class="pt-3 border-t">
                        <form action="{{ route('admin.users.toggle-admin', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-sm font-medium rounded-md {{ $user->is_admin ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-purple-600 hover:bg-purple-700' }} text-white transition-colors">
                                {{ $user->is_admin ? 'Revoke Admin Access' : 'Grant Admin Access' }}
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                        Edit User
                    </a>

                    @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full px-4 py-2 text-center text-white rounded-md bg-red-600 hover:bg-red-700 transition-colors">
                            Delete User
                        </button>
                    </form>
                    @else
                    <div class="px-4 py-3 bg-blue-50 text-blue-700 rounded-md text-sm text-center">
                        You cannot delete your own account
                    </div>
                    @endif
                </div>
            </div>

            <!-- Account Stats -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Statistics</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Age</label>
                        <p class="mt-1 text-sm text-gray-900">{{ floor($user->created_at->diffInDays(now())) }} days</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Member Since</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
