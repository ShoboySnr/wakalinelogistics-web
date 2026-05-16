@extends('Admin::layout')

@section('title', 'Credit Package: ' . $package->name)

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.credits.packages') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $package->name }}</h1>
            @if($package->is_popular)
            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-[#C1666B] text-white">Best Value</span>
            @endif
            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $package->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <p class="text-sm text-gray-500">Credit Package Details</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Pricing Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing & Credits</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Price</p>
                        <p class="text-2xl font-bold text-gray-900">₦{{ number_format($package->price, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Base Credits</p>
                        <p class="text-2xl font-bold text-[#C1666B]">{{ number_format($package->credits) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Bonus Credits</p>
                        <p class="text-2xl font-bold text-green-600">+{{ number_format($package->bonus_credits) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Total Credits</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($package->credits + $package->bonus_credits) }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-sm">
                    <span class="text-gray-500">Price per Credit</span>
                    <span class="font-semibold text-gray-900">₦{{ number_format($package->price_per_credit, 4) }}</span>
                </div>
                @if($package->bonus_credits > 0)
                <div class="flex justify-between text-sm mt-2">
                    <span class="text-gray-500">Effective Price per Credit (with bonus)</span>
                    <span class="font-semibold text-green-600">₦{{ number_format($package->price / ($package->credits + $package->bonus_credits), 4) }}</span>
                </div>
                @endif
            </div>

            <!-- Package Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Package Information</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $package->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Slug</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900">{{ $package->slug }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $package->description ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Validity</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $package->validity_days ? $package->validity_days . ' days after purchase' : 'Lifetime (no expiry)' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Sort Order</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $package->sort_order }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $package->created_at->format('d M Y, g:ia') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $package->updated_at->format('d M Y, g:ia') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.credits.packages.edit', $package->id) }}"
                        class="block w-full text-center px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                        Edit Package
                    </a>
                    <form action="{{ route('admin.credits.packages.delete', $package->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this package? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full px-4 py-2 text-red-700 bg-red-50 hover:bg-red-100 rounded-md border border-red-200 transition-colors">
                            Delete Package
                        </button>
                    </form>
                    <a href="{{ route('admin.credits.packages') }}"
                        class="block w-full text-center px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Back to Packages
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Stats</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Times Purchased</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $package->creditTransactions->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Revenue</dt>
                        <dd class="text-sm font-semibold text-gray-900">₦{{ number_format($package->creditTransactions->sum('amount_paid'), 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Credits Sold</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($package->creditTransactions->sum('credits')) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
