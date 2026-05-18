@extends('Admin::layout')

@section('title', 'Transaction Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.transactions') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Transaction Details</h1>
                @if($transaction->status === 'completed')
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                @elseif($transaction->status === 'pending')
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                @elseif($transaction->status === 'failed')
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                @else
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($transaction->status) }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500">{{ $transaction->transaction_reference }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            @if($transaction->status === 'pending')
            <button onclick="document.getElementById('approveModal').classList.remove('hidden')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Approve
            </button>
            <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                Reject
            </button>
            @endif

            @if($transaction->status === 'completed')
            <button onclick="document.getElementById('reverseModal').classList.remove('hidden')" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                Reverse
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Transaction Information</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Transaction Reference</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $transaction->transaction_reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Payment Reference</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $transaction->payment_reference ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1">
                            @if($transaction->type === 'purchase')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Purchase</span>
                            @elseif($transaction->type === 'refund')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Refund</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Usage</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($transaction->status === 'completed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completed</span>
                            @elseif($transaction->status === 'pending')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                            @elseif($transaction->status === 'failed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($transaction->status) }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Credits</dt>
                        <dd class="mt-1 text-lg font-bold {{ $transaction->credits >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->credits >= 0 ? '+' : '' }}{{ number_format($transaction->credits) }} credits
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount Paid</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $transaction->amount_paid ? '₦' . number_format($transaction->amount_paid, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Credit Balance Before</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($transaction->balance_before) }} credits</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Credit Balance After</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($transaction->balance_after) }} credits</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($transaction->payment_method ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->created_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->description }}</dd>
                    </div>
                </dl>
            </div>

            @if($transaction->metadata)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h2>
                <pre class="bg-gray-50 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Client Info -->
            @if($transaction->client)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Client Information</h2>
                <div class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->client->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->client->email ?? 'N/A' }}</dd>
                    </div>
                    @php $clientCredit = $transaction->client->getOrCreateCredits(); @endphp
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Current Credit Balance</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">{{ number_format($clientCredit->available_credits) }} credits</dd>
                    </div>
                    <a href="{{ route('admin.clients.show', $transaction->client->id) }}" class="block w-full text-center px-4 py-2 brand-accent text-white rounded-lg hover:opacity-90 transition-opacity mt-4">
                        View Client Profile
                    </a>
                </div>
            </div>
            @endif

            <!-- Related Order -->
            @if($transaction->order)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Related Order</h2>
                <div class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $transaction->order->order_number }}</dd>
                    </div>
                    <a href="{{ route('admin.orders.show', $transaction->order->id) }}" class="block w-full text-center px-4 py-2 brand-accent text-white rounded-lg hover:opacity-90 transition-opacity mt-4">
                        View Order
                    </a>
                </div>
            </div>
            @endif

            <!-- Related Plan / Package -->
            @if($transaction->subscriptionPlan)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Plan</h2>
                <p class="text-sm font-medium text-gray-900">{{ $transaction->subscriptionPlan->name }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($transaction->subscriptionPlan->credits) }} credits · {{ $transaction->subscriptionPlan->billing_cycle }}</p>
            </div>
            @elseif($transaction->creditPackage)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Credit Package</h2>
                <p class="text-sm font-medium text-gray-900">{{ $transaction->creditPackage->name }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($transaction->creditPackage->credits) }} credits</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Approve Transaction</h3>
        <p class="text-gray-600 mb-6">This will mark the transaction as completed and add the credits to the client's account.</p>
        <form method="POST" action="{{ route('admin.transactions.approve', $transaction->id) }}">
            @csrf
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('approveModal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Approve
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Reject Transaction</h3>
        <form method="POST" action="{{ route('admin.transactions.reject', $transaction->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Rejection</label>
                <textarea name="reason" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#C1666B] focus:border-transparent" placeholder="Enter reason..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Reject
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reverse Modal -->
<div id="reverseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Reverse Transaction</h3>
        <p class="text-red-600 mb-4 text-sm font-medium">⚠️ Warning: This will reverse the credit balance changes for this client.</p>
        <form method="POST" action="{{ route('admin.transactions.reverse', $transaction->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Reversal</label>
                <textarea name="reason" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#C1666B] focus:border-transparent" placeholder="Enter reason..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('reverseModal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    Reverse
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
