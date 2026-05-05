@extends('Admin::layout')

@section('title', 'Client Credit History')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.credits') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Credit History</h1>
        </div>
        <p class="text-sm text-gray-500">{{ $client->name }} ({{ $client->email }})</p>
    </div>

    <!-- Credit Balance Card -->
    @if($client->credits)
    <div class="bg-gradient-to-r from-[#C1666B] to-[#A85559] rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm opacity-90 mb-1">Available Credits</p>
                <p class="text-3xl font-bold">{{ $client->credits->available_credits }}</p>
            </div>
            <div>
                <p class="text-sm opacity-90 mb-1">Total Credits</p>
                <p class="text-3xl font-bold">{{ $client->credits->total_credits }}</p>
            </div>
            <div>
                <p class="text-sm opacity-90 mb-1">Used Credits</p>
                <p class="text-3xl font-bold">{{ $client->credits->used_credits }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Adjust Credits Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Adjust Credits</h2>
        <form action="{{ route('admin.credits.adjust', $client->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
                    <option value="add">Add Credits</option>
                    <option value="deduct">Deduct Credits</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                <input type="number" name="credits" required min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                <input type="text" name="reason" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md"
                    placeholder="e.g., Manual adjustment">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                    Adjust
                </button>
            </div>
        </form>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Transaction History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                            {{ $transaction->transaction_reference }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($transaction->type === 'purchase') bg-green-100 text-green-800
                                @elseif($transaction->type === 'usage') bg-blue-100 text-blue-800
                                @elseif($transaction->type === 'refund') bg-yellow-100 text-yellow-800
                                @elseif($transaction->type === 'adjustment') bg-purple-100 text-purple-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium {{ $transaction->credits > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->credits > 0 ? '+' : '' }}{{ $transaction->credits }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->balance_before }} → {{ $transaction->balance_after }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $transaction->description }}
                            @if($transaction->subscriptionPlan)
                                <span class="text-gray-500">({{ $transaction->subscriptionPlan->name }})</span>
                            @elseif($transaction->creditPackage)
                                <span class="text-gray-500">({{ $transaction->creditPackage->name }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No transactions yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
