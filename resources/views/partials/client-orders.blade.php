<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rider</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ $order->created_at->format('M d, Y') }}</div>
                    <div class="text-xs text-gray-500">{{ $order->created_at->format('h:i A') }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">
                        {{ $order->pickup_address }}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 mb-1">
                        {{ $order->delivery_address }}
                    </div>
                    @if($order->receiver_name || $order->receiver_phone)
                    <div class="text-xs text-gray-600 mt-2 pt-2 border-t border-gray-100">
                        @if($order->receiver_name)
                        <div class="font-medium">📦 {{ $order->receiver_name }}</div>
                        @endif
                        @if($order->receiver_phone)
                        <div class="text-gray-500">📞 {{ $order->receiver_phone }}</div>
                        @endif
                    </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($order->rider)
                    <div class="text-sm text-gray-900">{{ $order->rider->name }}</div>
                    <div class="text-xs text-gray-500">{{ $order->rider->phone }}</div>
                    @else
                    <span class="text-xs text-gray-400">Not assigned</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                        @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                        @elseif($order->status === 'picked_up') bg-indigo-100 text-indigo-800
                        @elseif($order->status === 'in_transit') bg-purple-100 text-purple-800
                        @elseif($order->status === 'delivered') bg-green-100 text-green-800
                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">₦{{ number_format($order->price, 2) }}</div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">No orders found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
