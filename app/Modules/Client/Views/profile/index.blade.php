@extends('Client::layouts.app')

@section('title', 'Profile')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
            Profile Settings
        </h2>
        <p class="mt-1 text-sm text-gray-500">Manage your account information and password.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Profile Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>
            <form action="{{ route('client.profile.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone *</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $client->phone) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alternate_phone" class="block text-sm font-medium text-gray-700">Alternate Phone</label>
                    <input type="tel" name="alternate_phone" id="alternate_phone" value="{{ old('alternate_phone', $client->alternate_phone) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('alternate_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pickup_address" class="block text-sm font-medium text-gray-700">Default Pickup Address</label>
                    <textarea name="pickup_address" id="pickup_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">{{ old('pickup_address', $client->pickup_address) }}</textarea>
                    @error('pickup_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white brand-accent-bg brand-accent-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1666B]">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
            <form action="{{ route('client.profile.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password *</label>
                    <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">New Password *</label>
                    <input type="password" name="password" id="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm" placeholder="Minimum 8 characters">
                    <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                    @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm" placeholder="Confirm password">
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white brand-accent-bg brand-accent-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1666B]">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Information -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @if($client->company_name)
            <div>
                <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $client->company_name }}</dd>
            </div>
            @endif

            @if($client->business_type)
            <div>
                <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $client->business_type }}</dd>
            </div>
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                <dd class="mt-1">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $client->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $client->created_at->format('F d, Y') }}</dd>
            </div>

            @if($client->last_login_at)
            <div>
                <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $client->last_login_at->format('F d, Y h:i A') }}</dd>
            </div>
            @endif
        </dl>
    </div>
</div>
@endsection
