@extends('Admin::layout')

@section('title', 'Client Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.clients') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                @if($client->is_active)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                @endif
            </div>
            @if($client->company_name)
            <p class="text-sm text-gray-500">{{ $client->company_name }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.clients.edit', $client->id) }}" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Edit Client
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Total Orders</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Completed</p>
            <p class="text-2xl font-bold text-green-600">{{ $completedOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900">₦{{ number_format($totalRevenue, 2) }}</p>
        </div>

        <div class="rounded-lg shadow p-6 text-white" style="background: linear-gradient(to bottom right, #C1666B, #A85559);">
            <div class="flex items-center justify-between mb-1">
                <p class="text-sm opacity-90">Credit Balance</p>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-2xl font-bold">{{ number_format($clientCredit->available_credits) }}</p>
            <p class="text-xs opacity-75 mt-1">of {{ number_format($clientCredit->total_credits) }} total</p>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Client Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Primary Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="tel:{{ $client->phone }}" class="hover:underline" style="color: #C1666B;">{{ $client->phone }}</a>
                            </dd>
                        </div>
                        @if($client->alternate_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Alternate Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="tel:{{ $client->alternate_phone }}" class="hover:underline" style="color: #C1666B;">{{ $client->alternate_phone }}</a>
                            </dd>
                        </div>
                        @endif
                        @if($client->email)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="mailto:{{ $client->email }}" class="hover:underline" style="color: #C1666B;">{{ $client->email }}</a>
                            </dd>
                        </div>
                        @endif
                        @if($client->contact_person)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->contact_person }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Address Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Address Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pickup Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->pickup_address }}</dd>
                        </div>
                        @if($client->city || $client->state)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">City / State</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->city }}{{ $client->city && $client->state ? ', ' : '' }}{{ $client->state }}</dd>
                        </div>
                        @endif
                        @if($client->landmark)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Landmark</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->landmark }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('admin.orders') }}?client_id={{ $client->id }}" class="text-sm font-medium hover:underline" style="color: #C1666B;">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($client->orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($order->status === 'delivered') bg-green-100 text-green-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">₦{{ number_format($order->price, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No orders yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                </div>
                <div class="px-6 py-4 space-y-2">
                    <a href="{{ route('admin.orders.create') }}?client_id={{ $client->id }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover">
                        Create Order
                    </a>
                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="block w-full px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Edit Client
                    </a>
                    <form action="{{ route('admin.clients.delete', $client->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full px-4 py-2 text-center brand-accent-bg text-white rounded-md brand-accent-hover">
                            Delete Client
                        </button>
                    </form>
                </div>
            </div>

            <!-- Business Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Business Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        @if($client->business_type)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($client->business_type) }}
                                </span>
                            </dd>
                        </div>
                        @endif
                        @if($client->payment_terms)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Payment Terms</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($client->payment_terms === 'prepaid')
                                    Prepaid
                                @elseif($client->payment_terms === 'postpaid')
                                    Postpaid
                                @elseif($client->payment_terms === 'credit_30')
                                    Net 30
                                @elseif($client->payment_terms === 'credit_60')
                                    Net 60
                                @endif
                            </dd>
                        </div>
                        @endif
                        @if($client->credit_limit)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Credit Limit</dt>
                            <dd class="mt-1 text-sm text-gray-900">₦{{ number_format($client->credit_limit, 2) }}</dd>
                        </div>
                        @endif
                        @if($client->tax_id)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tax ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->tax_id }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Additional Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->updated_at->format('M d, Y') }}</dd>
                        </div>
                        @if($client->notes)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->notes }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Email Verification -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Email Verification</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($client->email_verified_at)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-green-700">Email Verified</p>
                                <p class="text-xs text-gray-500">{{ $client->email_verified_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-yellow-700">Email Not Verified</p>
                        </div>

                        @if($client->email_verification_code && $client->email_verification_expires_at && $client->email_verification_expires_at->isFuture())
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-xs text-blue-700 font-semibold mb-2">Active Verification Code</p>
                                <p class="text-2xl font-black tracking-[0.35em] text-blue-900 text-center py-2">{{ $client->email_verification_code }}</p>
                                <p class="text-xs text-gray-500 text-center mt-1">
                                    Expires {{ $client->email_verification_expires_at->diffForHumans() }}
                                    ({{ $client->email_verification_expires_at->format('h:i A') }})
                                </p>
                            </div>
                        @else
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
                                <p class="text-sm text-gray-500">No active verification code</p>
                                <p class="text-xs text-gray-400 mt-1">Code was never sent or has expired</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.clients.verify-email', $client->id) }}" class="mt-3" onsubmit="return confirm('Manually verify this client\'s email? This will also credit the ₦2,000 signup bonus if not yet credited.')">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                                Verify Email Manually
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Subscription Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Subscription Plan</h2>
                    <p class="text-xs text-gray-500 mt-1">Manually assign a subscription plan to this client</p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @if($activeSubscription)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-green-800">{{ $activeSubscription->plan->name }}</p>
                                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Active</span>
                            </div>
                            <p class="text-xs text-gray-600">Credits: {{ number_format($activeSubscription->credits_used) }} / {{ number_format($activeSubscription->credits_allocated) }} used</p>
                            @if($activeSubscription->expires_at)
                            <p class="text-xs text-gray-500 mt-1">Expires: {{ $activeSubscription->expires_at->format('M d, Y') }}</p>
                            @else
                            <p class="text-xs text-gray-500 mt-1">No expiry</p>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                            <p class="text-sm text-gray-500">No active subscription</p>
                        </div>
                    @endif

                    @if($subscriptionPlans->count() > 0)
                    <form method="POST" action="{{ route('admin.clients.subscribe', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4">
                        @csrf
                        <h3 class="text-sm font-semibold text-gray-900">{{ $activeSubscription ? 'Change Subscription' : 'Assign Subscription' }}</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                            <select name="subscription_plan_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                                <option value="">Select a plan…</option>
                                @foreach($subscriptionPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->credits }} credits ({{ $plan->billing_cycle }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="starts_at" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <input type="text" name="notes" placeholder="Reason for manual subscription" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <button type="submit" onclick="return confirm('This will cancel any existing active subscription and assign the selected plan. Credits from the plan will be added immediately. Continue?')" class="w-full px-4 py-2 brand-accent-bg text-white text-sm rounded-md brand-accent-hover transition-colors">
                            Assign Plan
                        </button>
                    </form>
                    @else
                    <p class="text-xs text-gray-500 text-center">No active subscription plans available. <a href="{{ route('admin.credits.plans') }}" class="underline" style="color:#C1666B;">Create one</a></p>
                    @endif
                </div>
            </div>

            <!-- Credits Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Delivery Credits</h2>
                    <p class="text-xs text-gray-500 mt-1">Manually add delivery credits to this client</p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-xs text-green-700 font-medium">Available</p>
                            <p class="text-xl font-bold text-green-900">{{ number_format($clientCredit->available_credits) }}</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-700 font-medium">Total</p>
                            <p class="text-xl font-bold text-blue-900">{{ number_format($clientCredit->total_credits) }}</p>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <p class="text-xs text-red-700 font-medium">Used</p>
                            <p class="text-xl font-bold text-red-900">{{ number_format($clientCredit->used_credits) }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.clients.add-credits', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4">
                        @csrf
                        <h3 class="text-sm font-semibold text-gray-900">Add Credits</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Number of Credits</label>
                            <input type="number" name="credits" required min="1" placeholder="e.g. 50" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reason</label>
                            <input type="text" name="reason" required placeholder="e.g. Goodwill credit, Compensation" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 brand-accent-bg text-white text-sm rounded-md brand-accent-hover transition-colors">
                            Add Credits
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.clients.deduct-credits', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4" onsubmit="return confirm('Are you sure you want to deduct credits from this client?')">
                        @csrf
                        <h3 class="text-sm font-semibold text-red-700">Deduct Credits</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Number of Credits to Deduct</label>
                            <input type="number" name="credits" required min="1" max="{{ $clientCredit->available_credits }}" placeholder="e.g. 10" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reason</label>
                            <input type="text" name="reason" required placeholder="e.g. Correction, Reversal" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                            Deduct Credits
                        </button>
                    </form>

                    <a href="{{ route('admin.credits.client-history', $client->id) }}" class="block w-full px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 text-sm">
                        View Credit History
                    </a>
                </div>
            </div>

            <!-- Order Tracking Share Link -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Order Tracking</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @php
                        $clientShare = $client->clientShare()->where('is_active', true)->first();
                        $shareUrl = $clientShare ? config('app.frontend_url') . '/share/' . $clientShare->token : null;
                    @endphp
                    
                    @if($shareUrl)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-gray-600 mb-2 font-semibold">Active Order Tracking Link:</p>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="text" value="{{ $shareUrl }}" readonly class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md" onclick="this.select()">
                                <button onclick="copyToClipboard('{{ $shareUrl }}')" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                    Copy
                                </button>
                            </div>
                            <button onclick="disableClientShare({{ $client->id }})" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                                Disable Order Tracking
                            </button>
                        </div>
                    @else
                        <button onclick="generateClientShareLink()" class="block w-full px-4 py-2 text-center bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Enable Order Tracking
                        </button>
                    @endif
                </div>
            </div>

            <!-- API Access Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">API Access</h2>
                    <p class="text-xs text-gray-500 mt-1">Manage client's API key for external integrations</p>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($client->api_enabled && $client->api_key)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-2">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-600 font-semibold">API Access: <span class="text-green-600">Enabled</span></p>
                                @if($client->api_key_generated_at)
                                <p class="text-xs text-gray-500">Generated: {{ $client->api_key_generated_at->format('M d, Y h:i A') }}</p>
                                @endif
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-xs text-gray-600 mb-2 font-semibold">API Key:</p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    <input type="password" id="apiKeyDisplay" value="{{ $client->api_key }}" readonly class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md font-mono min-w-0" onclick="this.select()">
                                    <div class="flex gap-2 flex-shrink-0">
                                        <button onclick="toggleApiKeyVisibility()" class="px-3 py-2 bg-gray-600 text-white text-xs rounded-md hover:bg-gray-700 transition-colors whitespace-nowrap">
                                            <span id="toggleText">View</span>
                                        </button>
                                        <button onclick="copyApiKey('{{ $client->api_key }}')" class="px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-colors whitespace-nowrap">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($client->api_last_used_at)
                            <p class="text-xs text-gray-500 mb-3">Last used: {{ $client->api_last_used_at->diffForHumans() }}</p>
                            @else
                            <p class="text-xs text-gray-500 mb-3">Never used</p>
                            @endif
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button onclick="regenerateClientApiKey({{ $client->id }})" class="flex-1 px-4 py-2 bg-yellow-600 text-white text-xs sm:text-sm rounded-md hover:bg-yellow-700 transition-colors whitespace-nowrap">
                                    Regenerate Key
                                </button>
                                <button onclick="disableClientApiAccess({{ $client->id }})" class="flex-1 px-4 py-2 bg-red-600 text-white text-xs sm:text-sm rounded-md hover:bg-red-700 transition-colors whitespace-nowrap">
                                    Disable API Access
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">
                                <span class="font-semibold">API Access: <span class="text-red-600">Disabled</span></span><br>
                                <span class="text-xs">Enable API access to allow this client to integrate with external systems.</span>
                            </p>
                            <button onclick="enableClientApiAccess({{ $client->id }})" class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                                Enable API Access
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const clientId = {{ $client->id }};

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function generateClientShareLink() {
    if (!confirm('This will generate a shareable link for the client to track their orders. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/generate-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order tracking link generated successfully!');
            location.reload();
        } else {
            alert('Failed to generate link. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function disableClientShare(clientId) {
    if (!confirm('This will disable the order tracking link. The client will no longer be able to access it. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/disable-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order tracking link disabled successfully!');
            location.reload();
        } else {
            alert('Failed to disable link. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// API Key Management Functions
function toggleApiKeyVisibility() {
    const input = document.getElementById('apiKeyDisplay');
    const toggleText = document.getElementById('toggleText');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggleText.textContent = 'Hide';
    } else {
        input.type = 'password';
        toggleText.textContent = 'View';
    }
}

function copyApiKey(apiKey) {
    navigator.clipboard.writeText(apiKey).then(() => {
        alert('API key copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy API key');
    });
}

function enableClientApiAccess(clientId) {
    if (!confirm('This will generate an API key for the client. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/enable-api-access`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API access enabled successfully!\n\nAPI Key: ' + data.api_key + '\n\nPlease save this key securely.');
            location.reload();
        } else {
            alert('Failed to enable API access. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function regenerateClientApiKey(clientId) {
    if (!confirm('WARNING: This will invalidate the current API key. Any integrations using the old key will stop working. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/regenerate-api-key`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API key regenerated successfully!\n\nNew API Key: ' + data.api_key + '\n\nPlease save this key securely.');
            location.reload();
        } else {
            alert('Failed to regenerate API key. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function disableClientApiAccess(clientId) {
    if (!confirm('This will disable API access for this client. Their integrations will stop working. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/disable-api-access`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API access disabled successfully!');
            location.reload();
        } else {
            alert('Failed to disable API access. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>
@endsection
