@extends('Admin::layout')

@section('title', 'Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Dashboard</h1>

    <!-- Bike Alerts -->
    @if($expired_bike_docs->count() > 0 || $expiring_bike_docs->count() > 0 || $upcoming_maintenance->count() > 0)
    <div class="mb-6 space-y-4">
        <!-- Upcoming Maintenance Alert -->
        @if($upcoming_maintenance->count() > 0)
        <div class="p-4" style="background-color: rgba(193, 102, 107, 0.05); border-left: 4px solid #C1666B;">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 brand-accent-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium brand-accent-text">Upcoming Bike Maintenance</h3>
                    <div class="mt-2 text-sm text-gray-700">
                        <p class="mb-2">{{ $upcoming_maintenance->count() }} bike(s) due for maintenance within the next 7 days:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($upcoming_maintenance->take(5) as $bike)
                            <li>
                                <a href="{{ route('admin.bikes.show', $bike->id) }}" class="font-semibold hover:underline">
                                    {{ $bike->bike_number }}
                                </a>
                                @if($bike->assignedRider)
                                    ({{ $bike->assignedRider->name }})
                                @endif
                                - Due: {{ $bike->next_maintenance_date->format('M d, Y') }}
                                ({{ $bike->next_maintenance_date->diffForHumans() }})
                            </li>
                            @endforeach
                            @if($upcoming_maintenance->count() > 5)
                            <li class="text-xs">
                                <a href="{{ route('admin.bikes') }}" class="font-semibold hover:underline">
                                    View all {{ $upcoming_maintenance->count() }} bikes →
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Expired Documents Alert -->
        @if($expired_bike_docs->count() > 0)
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-red-800">Expired Bike Documents</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p class="mb-2">{{ $expired_bike_docs->count() }} bike(s) have expired documents that need immediate attention:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($expired_bike_docs->take(3) as $item)
                            <li>
                                <a href="{{ route('admin.bikes.show', $item['bike']->id) }}" class="font-semibold hover:underline">
                                    {{ $item['bike']->bike_number }}
                                </a>
                                - {{ collect($item['expired_docs'])->pluck('name')->join(', ') }}
                            </li>
                            @endforeach
                            @if($expired_bike_docs->count() > 3)
                            <li class="text-xs">
                                <a href="{{ route('admin.bikes') }}" class="font-semibold hover:underline">
                                    View all {{ $expired_bike_docs->count() }} bikes →
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Expiring Documents Alert -->
        @if($expiring_bike_docs->count() > 0)
        <div class="bg-orange-50 border-l-4 border-orange-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-orange-800">Documents Expiring Soon</h3>
                    <div class="mt-2 text-sm text-orange-700">
                        <p class="mb-2">{{ $expiring_bike_docs->count() }} bike(s) have documents expiring within 30 days:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($expiring_bike_docs->take(3) as $item)
                            <li>
                                <a href="{{ route('admin.bikes.show', $item['bike']->id) }}" class="font-semibold hover:underline">
                                    {{ $item['bike']->bike_number }}
                                </a>
                                - {{ collect($item['expiring_docs'])->pluck('name')->join(', ') }}
                                ({{ collect($item['expiring_docs'])->min('days_remaining') }} days)
                            </li>
                            @endforeach
                            @if($expiring_bike_docs->count() > 3)
                            <li class="text-xs">
                                <a href="{{ route('admin.bikes') }}" class="font-semibold hover:underline">
                                    View all {{ $expiring_bike_docs->count() }} bikes →
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Today's Overview -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Today's Overview</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Today's Orders</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['today_orders'] }}</p>
                    </div>
                    <div class="p-3 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Today's Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">₦{{ number_format($stats['today_revenue'], 2) }}</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Today</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['today_pending'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Delivered Today</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['today_delivered'] }}</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Overall Statistics</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Pending</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">In Transit</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['in_transit_orders'] }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-1">Delivered</p>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['delivered_orders'] }}</p>
            </div>
        </div>
    </div>

    <!-- Revenue & Riders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Stats -->
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Overview</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">Total Revenue</p>
                        <p class="text-xl font-bold text-gray-900">₦{{ number_format($stats['total_revenue'], 2) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">This Week</p>
                        <p class="text-xl font-bold text-gray-900">₦{{ number_format($stats['week_revenue'], 2) }}</p>
                    </div>
                    <span class="text-xs text-gray-500">{{ $stats['week_orders'] }} orders</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">This Month</p>
                        <p class="text-xl font-bold text-gray-900">₦{{ number_format($stats['month_revenue'], 2) }}</p>
                    </div>
                    <span class="text-xs text-gray-500">{{ $stats['month_orders'] }} orders</span>
                </div>
            </div>
        </div>

        <!-- Rider Stats -->
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rider Overview</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">Total Riders</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['total_riders'] }}</p>
                    </div>
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">Active Riders</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['active_riders'] }}</p>
                    </div>
                    <span class="text-xs text-blue-600 font-medium">Available</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">On Delivery</p>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['riders_with_orders'] }}</p>
                    </div>
                    <span class="text-xs text-blue-600 font-medium">Busy</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Orders</h3>
            <a href="{{ route('admin.orders') }}" class="text-sm brand-accent-text hover:underline font-medium">
                View All Orders
            </a>
        </div>
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
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recent_orders as $order)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->customer_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="max-w-xs truncate">{{ $order->pickup_address }} → {{ $order->delivery_address }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₦{{ number_format($order->price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                                @elseif($order->status == 'in_transit') bg-purple-100 text-purple-800
                                @elseif($order->status == 'delivered') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No orders yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recent_orders->count() > 0)
        <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
            <a href="{{ route('admin.orders') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                View all orders
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
