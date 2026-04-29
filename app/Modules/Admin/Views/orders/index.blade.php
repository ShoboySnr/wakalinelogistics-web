@extends('Admin::layout')

@section('title', 'Orders')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Orders Management</h1>
        <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover whitespace-nowrap" style="transition: background-color 0.2s ease;">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Order
        </a>
    </div>

    <!-- Revenue Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <!-- Today's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_today'], 2) }}</p>
        </div>

        <!-- Today's Incoming Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Incoming Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['incoming_revenue_today'], 2) }}</p>
        </div>

        <!-- Today's Delivered Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Delivered</p>
            <p class="text-lg font-bold text-gray-900">{{ $stats['today_delivered'] ?? 0 }}</p>
        </div>

        <!-- This Week's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Week's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_week'], 2) }}</p>
        </div>

        <!-- This Week's Incoming Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Week's Incoming Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['incoming_revenue_week'], 2) }}</p>
        </div>

        <!-- This Month's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Month's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_month'], 2) }}</p>
        </div>
    </div>

    <!-- Order Status Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <!-- Total Orders -->
        <a href="{{ route('admin.orders') }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ !request('status') ? 'ring-2 ring-pink-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </a>

        <!-- Pending Orders -->
        <a href="{{ route('admin.orders', ['status' => 'pending']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'pending' ? 'ring-2 ring-yellow-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Pending</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
        </a>

        <!-- Confirmed Orders -->
        <a href="{{ route('admin.orders', ['status' => 'confirmed']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'confirmed' ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Confirmed</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['confirmed'] }}</p>
        </a>

        <!-- In Transit Orders -->
        <a href="{{ route('admin.orders', ['status' => 'in_transit']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'in_transit' ? 'ring-2 ring-purple-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">In Transit</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['in_transit'] }}</p>
        </a>

        <!-- Delivered Orders -->
        <a href="{{ route('admin.orders', ['status' => 'delivered']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'delivered' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Delivered</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['delivered'] }}</p>
        </a>

        <!-- Cancelled Orders -->
        <a href="{{ route('admin.orders', ['status' => 'cancelled']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'cancelled' ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Cancelled</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['cancelled'] }}</p>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.orders') }}" class="space-y-4">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" placeholder="Search by order #, name, or phone" 
                           value="{{ request('search') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                <div class="min-w-[180px]">
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <select name="date_filter" id="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="" {{ request('date_filter', '') == '' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                        <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>
            </div>

            <!-- Custom Date Range (shown when Custom Range is selected) -->
            <div id="custom_date_range" class="flex flex-wrap gap-4 {{ request('date_filter') == 'custom' ? '' : 'hidden' }}">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.orders') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('date_filter').addEventListener('change', function() {
            const customRange = document.getElementById('custom_date_range');
            if (this.value === 'custom') {
                customRange.classList.remove('hidden');
            } else {
                customRange.classList.add('hidden');
            }
        });
    </script>

    <!-- Orders Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                            @if($order->customer_email)
                            <div class="text-xs text-gray-400">{{ $order->customer_email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="max-w-xs">
                                <div class="truncate text-xs">From: {{ $order->pickup_address }}</div>
                                <div class="truncate text-xs">To: {{ $order->delivery_address }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">₦{{ number_format($order->price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select class="status-dropdown px-2 py-1 text-xs font-semibold rounded-full border-0 focus:ring-2 focus:ring-pink-500 cursor-pointer
                                @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                                @elseif($order->status == 'in_transit') bg-purple-100 text-purple-800
                                @elseif($order->status == 'delivered') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif"
                                data-order-id="{{ $order->id }}"
                                data-current-status="{{ $order->status }}">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="in_transit" {{ $order->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>No orders found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($orders->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusDropdowns = document.querySelectorAll('.status-dropdown');
    
    statusDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            const currentStatus = this.dataset.currentStatus;
            const selectElement = this;
            
            // Confirm status change
            if (!confirm(`Change order status to "${newStatus.replace('_', ' ')}"?`)) {
                // Revert to previous status
                this.value = currentStatus;
                return;
            }
            
            // Disable dropdown during update
            selectElement.disabled = true;
            
            // Send AJAX request
            fetch(`/super-admin/orders/${orderId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update current status
                    selectElement.dataset.currentStatus = newStatus;
                    
                    // Update dropdown styling based on new status
                    selectElement.className = 'status-dropdown px-2 py-1 text-xs font-semibold rounded-full border-0 focus:ring-2 focus:ring-pink-500 cursor-pointer';
                    
                    if (newStatus === 'pending') {
                        selectElement.classList.add('bg-yellow-100', 'text-yellow-800');
                    } else if (newStatus === 'confirmed') {
                        selectElement.classList.add('bg-blue-100', 'text-blue-800');
                    } else if (newStatus === 'in_transit') {
                        selectElement.classList.add('bg-purple-100', 'text-purple-800');
                    } else if (newStatus === 'delivered') {
                        selectElement.classList.add('bg-green-100', 'text-green-800');
                    } else if (newStatus === 'cancelled') {
                        selectElement.classList.add('bg-red-100', 'text-red-800');
                    }
                    
                    // Show success message
                    showNotification('Order status updated successfully!', 'success');
                } else {
                    // Revert to previous status
                    selectElement.value = currentStatus;
                    showNotification(data.message || 'Failed to update order status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert to previous status
                selectElement.value = currentStatus;
                showNotification('An error occurred while updating order status', 'error');
            })
            .finally(() => {
                // Re-enable dropdown
                selectElement.disabled = false;
            });
        });
    });
    
    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>
@endsection
