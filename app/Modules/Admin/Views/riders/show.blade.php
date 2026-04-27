@extends('Admin::layout')

@section('title', 'Rider Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.riders') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Riders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Rider Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $rider->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Rider ID: #{{ $rider->id }} | Joined {{ $rider->created_at->format('M d, Y') }}
                        </p>
                    </div>
                    <div>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            @if($rider->status === 'active') bg-green-100 text-green-800
                            @elseif($rider->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($rider->status) }}
                        </span>
                    </div>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->phone }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->vehicle_type ? ucfirst($rider->vehicle_type) : 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vehicle Number</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->vehicle_number ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">License Number</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->license_number ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rating</label>
                            <p class="mt-1 text-sm text-gray-900">⭐ {{ number_format($rider->rating, 2) }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rider->address ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex justify-end space-x-3">
                        <a href="{{ route('admin.riders.edit', $rider->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Edit Rider
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pending/Active Orders -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Currently Assigned Orders</h2>
                    <p class="text-sm text-gray-500 mt-1">Orders pending or in transit</p>
                </div>

                <div class="overflow-x-auto">
                    @if($pendingOrders->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pickup</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Drop-off</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pendingOrders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm font-medium brand-accent-text hover:underline">#{{ $order->order_number }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $order->sender_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $order->sender_phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ Str::limit($order->pickup_address, 30) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ Str::limit($order->delivery_address, 30) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                                        @elseif($order->status === 'in_transit') bg-purple-100 text-purple-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No pending orders assigned</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Completed Orders -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Order History</h2>
                    <p class="text-sm text-gray-500 mt-1">Last 10 orders</p>
                </div>

                <div class="overflow-x-auto">
                    @if($rider->orders->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($rider->orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm font-medium brand-accent-text hover:underline">#{{ $order->order_number }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $order->sender_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($order->status === 'delivered') bg-green-100 text-green-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500">No order history yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Stats -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Daily Access Code -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Today's Access Code</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-2">Share this code with the rider to enable tracking</p>
                        <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-3">
                            <div class="text-3xl font-bold text-blue-600 tracking-widest" id="access-code-display" style="letter-spacing: 0.5em;">
                                {{ $rider->getDailyCode() }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="copyCode('{{ $rider->getDailyCode() }}')" class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </button>
                            <button onclick="regenerateAccessCode()" class="flex-1 px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Regenerate
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Valid for today only • Resets daily</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @php
                        $routeShare = $rider->routeShare()->where('is_active', true)->first();
                        $shareUrl = $routeShare ? url("/route/{$rider->id}") : null;
                    @endphp
                    
                    @if($shareUrl)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-gray-600 mb-2 font-semibold">Active Route Share Link:</p>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="text" value="{{ $shareUrl }}" readonly class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md" onclick="this.select()">
                                <button onclick="copyToClipboard('{{ $shareUrl }}')" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                    Copy
                                </button>
                            </div>
                            <button onclick="disableRouteShare({{ $rider->id }})" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                                Disable Route Share
                            </button>
                        </div>
                    @else
                        <button onclick="generateShareLink()" class="block w-full px-4 py-2 text-center bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Enable Route Share
                        </button>
                    @endif
                    
                    <button onclick="openLiveLocationModal()" class="block w-full px-4 py-2 text-center bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                        View Live Location
                    </button>
                    
                    <a href="{{ route('admin.riders.edit', $rider->id) }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                        Edit Rider
                    </a>
                    
                    <form action="{{ route('admin.riders.delete', $rider->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this rider?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full px-4 py-2 text-center bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                            Delete Rider
                        </button>
                    </form>
                </div>
            </div>

            <!-- Route Planning -->
            @if(count($waypoints) > 0)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Today's Route Plan</h2>
                        <p class="text-sm text-gray-600 mt-1">{{ count($waypoints) }} stops • Starting from {{ $startingPoint }}</p>
                    </div>
                    <a href="{{ $googleMapsUrl ?? '#' }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        Open with Google Maps
                    </a>
                </div>
                <div class="px-6 py-4">
                    <!-- Route Map -->
                    <div id="route-map" class="w-full h-96 rounded-lg border border-gray-300 mb-4"></div>
                    
                    <!-- Route List -->
                    <div class="space-y-4 mb-4 max-h-96 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #C1666B #f3f4f6;">
                        @php
                            $groupedWaypoints = [];
                            $processedOrders = [];
                            
                            foreach($waypoints as $waypoint) {
                                $orderId = $waypoint['paired_order_id'];
                                
                                if (!isset($processedOrders[$orderId])) {
                                    $processedOrders[$orderId] = true;
                                    
                                    $pickup = collect($waypoints)->firstWhere(function($w) use ($orderId) {
                                        return $w['paired_order_id'] === $orderId && $w['type'] === 'pickup';
                                    });
                                    
                                    $dropoff = collect($waypoints)->firstWhere(function($w) use ($orderId) {
                                        return $w['paired_order_id'] === $orderId && $w['type'] === 'dropoff';
                                    });
                                    
                                    $groupedWaypoints[] = [
                                        'order_id' => $orderId,
                                        'pickup' => $pickup,
                                        'dropoff' => $dropoff,
                                        'order_number' => $waypoint['order_number'],
                                        'priority_level' => $waypoint['priority_level'] ?? 'normal',
                                    ];
                                }
                            }
                        @endphp
                        
                        @foreach($groupedWaypoints as $group)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <!-- Order Header -->
                            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-200">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded">
                                    #{{ $group['order_number'] }}
                                </span>
                                @if($group['priority_level'] === 'urgent')
                                <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded animate-pulse">
                                    URGENT
                                </span>
                                @elseif($group['priority_level'] === 'high')
                                <span class="text-xs font-semibold text-orange-700 bg-orange-100 px-2 py-0.5 rounded border border-orange-300">
                                    High Priority
                                </span>
                                @endif
                            </div>
                            
                            <!-- Pickup Section -->
                            @if($group['pickup'])
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="font-bold text-gray-900 text-sm">PICKUP</span>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                        {{ ucwords(str_replace('_', ' ', $group['pickup']['status'] ?? 'pending')) }}
                                    </span>
                                </div>
                                <div class="pl-4 border-l-2 border-gray-300 space-y-2">
                                    <p class="text-base font-semibold text-gray-900">{{ $group['pickup']['sender'] }}</p>
                                    <div class="text-base text-gray-600">
                                        <p>{{ $group['pickup']['address'] }}</p>
                                        <a href="tel:{{ $group['pickup']['phone'] }}" class="hover:underline" style="color: #C1666B;">{{ $group['pickup']['phone'] }}</a>
                                    </div>
                                    @if(isset($group['pickup']['item_description']) && $group['pickup']['item_description'] !== 'N/A')
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 uppercase font-semibold">Item</p>
                                        <p class="text-base font-bold text-gray-900">{{ $group['pickup']['item_description'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            <!-- Dropoff Section -->
                            @if($group['dropoff'])
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="font-bold text-gray-900 text-sm">DROPOFF</span>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                        {{ ucwords(str_replace('_', ' ', $group['dropoff']['status'] ?? 'pending')) }}
                                    </span>
                                </div>
                                <div class="pl-4 border-l-2 border-gray-300 space-y-2">
                                    <p class="text-base font-semibold text-gray-900">{{ $group['dropoff']['receiver'] }}</p>
                                    <div class="text-base text-gray-600">
                                        <p>{{ $group['dropoff']['address'] }}</p>
                                        <a href="tel:{{ $group['dropoff']['phone'] }}" class="hover:underline" style="color: #C1666B;">{{ $group['dropoff']['phone'] }}</a>
                                    </div>
                                    @if(isset($group['dropoff']['item_description']) && $group['dropoff']['item_description'] !== 'N/A')
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 uppercase font-semibold">Item</p>
                                        <p class="text-base font-bold text-gray-900">{{ $group['dropoff']['item_description'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Share Buttons -->
                    <div class="flex gap-2">
                        <button onclick="shareRouteWhatsApp()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            Share on WhatsApp
                        </button>
                        <button onclick="copyRouteLink()" class="flex-1 px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy Route Link
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistics -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Statistics</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Deliveries</label>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $rider->total_deliveries }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Active Orders</label>
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ $pendingOrders->count() }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Average Rating</label>
                        <p class="mt-1 text-2xl font-bold text-yellow-600">{{ number_format($rider->rating, 2) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Member Since</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $rider->created_at->format('F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
<script>
let map;
let directionsService;
let directionsRenderer;

function initMap() {
    const waypoints = @json($waypoints);
    const startingPoint = @json($startingPoint);
    
    if (waypoints.length === 0) return;
    
    // Initialize map
    map = new google.maps.Map(document.getElementById('route-map'), {
        zoom: 12,
        center: { lat: 6.5244, lng: 3.3792 } // Lagos coordinates
    });
    
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        suppressMarkers: false,
        polylineOptions: {
            strokeColor: '#C1666B',
            strokeWeight: 4
        }
    });
    
    // Build waypoints for Google Maps
    const googleWaypoints = waypoints.slice(0, -1).map(wp => ({
        location: wp.address,
        stopover: true
    }));
    
    const destination = waypoints[waypoints.length - 1].address;
    
    // Calculate and display route
    directionsService.route({
        origin: startingPoint,
        destination: destination,
        waypoints: googleWaypoints,
        optimizeWaypoints: true,
        travelMode: google.maps.TravelMode.DRIVING
    }, (response, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(response);
        } else {
            console.error('Directions request failed:', status);
        }
    });
}

function shareRouteWhatsApp() {
    const message = @json($whatsappText);
    const whatsappUrl = `https://wa.me/?text=${message}`;
    window.open(whatsappUrl, '_blank');
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copied to clipboard: ' + code);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy code');
    });
}

function regenerateAccessCode() {
    if (!confirm('Generate a new access code? This will stop any active tracking and require the rider to enter the new code.')) {
        return;
    }
    
    fetch(`/super-admin/riders/{{ $rider->id }}/regenerate-code`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('access-code-display').textContent = data.code;
            alert('New access code generated: ' + data.code + '\n\nThe rider must enter this new code to continue tracking. Their old code has been invalidated.');
        } else {
            alert(data.message || 'Failed to regenerate code');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to regenerate code. Please try again.');
    });
}

function copyRouteLink() {
    const waypoints = @json($waypoints);
    const startingPoint = @json($startingPoint);
    
    const origin = encodeURIComponent(startingPoint);
    const destination = encodeURIComponent(waypoints[waypoints.length - 1].address);
    const waypointsParam = waypoints.slice(0, -1).map(wp => encodeURIComponent(wp.address)).join('|');
    const mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&waypoints=${waypointsParam}&travelmode=driving`;
    
    navigator.clipboard.writeText(mapsUrl).then(() => {
        alert('Route link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function generateShareLink() {
    const riderId = {{ $rider->id }};
    
    if (!confirm('Enable route sharing for this rider?')) {
        return;
    }
    
    fetch(`/super-admin/riders/${riderId}/generate-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Route sharing enabled successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to enable route sharing.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to enable route sharing. Please try again.');
    });
}

function disableRouteShare(riderId) {
    if (!confirm('Disable route sharing for this rider? The link will stop working.')) {
        return;
    }
    
    fetch(`/super-admin/riders/${riderId}/disable-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Route sharing disabled successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to disable route sharing.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to disable route sharing. Please try again.');
    });
}

function showShareLinkModal(url, expiresAt) {
    // Create modal HTML
    const modalHtml = `
        <div id="shareLinkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="closeShareLinkModal(event)">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4" onclick="event.stopPropagation()">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">🔗 Share Link Generated</h3>
                        <button onclick="closeShareLinkModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600 mb-3">Share this link with customers to track their deliveries in real-time.</p>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center justify-between">
                            <input type="text" id="shareLinkInput" value="${url}" readonly class="flex-1 bg-transparent text-sm text-gray-700 outline-none select-all" onclick="this.select()">
                            <button onclick="copyShareLink('${url}', event)" class="ml-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Expires: ${expiresAt}</span>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="shareViaWhatsApp('${url}')" class="flex-1 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </button>
                        <button onclick="closeShareLinkModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeShareLinkModal(event) {
    if (!event || event.target.id === 'shareLinkModal') {
        const modal = document.getElementById('shareLinkModal');
        if (modal) {
            modal.remove();
        }
    }
}

function copyToClipboard(url) {
    navigator.clipboard.writeText(url).then(() => {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-blue-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy link');
    });
}

function copyShareLink(url, event) {
    navigator.clipboard.writeText(url).then(() => {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-blue-600');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy link. Please copy manually.');
    });
}

function shareViaWhatsApp(url) {
    const message = encodeURIComponent(`Track your delivery in real-time: ${url}`);
    window.open(`https://wa.me/?text=${message}`, '_blank');
}

// Initialize map when page loads
if (document.getElementById('route-map')) {
    initMap();
}

// Live Tracking Map
let trackingMap;
let riderMarker;
let accuracyCircle;
let trackingInterval;

function initTrackingMap() {
    const mapElement = document.getElementById('rider-tracking-map');
    if (!mapElement) return;

    const defaultCenter = { lat: 6.5244, lng: 3.3792 };
    
    trackingMap = new google.maps.Map(mapElement, {
        zoom: 17,
        center: defaultCenter,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true
    });

    fetchRiderLocation();
    trackingInterval = setInterval(fetchRiderLocation, 30000);
}

async function fetchRiderLocation() {
    try {
        const response = await fetch(`/super-admin/riders/{{ $rider->id }}/location`);
        const data = await response.json();
        
        if (data.success && data.current_latitude && data.current_longitude) {
            updateRiderMarker(
                parseFloat(data.current_latitude),
                parseFloat(data.current_longitude),
                50
            );
            
            if (data.last_location_update) {
                document.getElementById('last-update').textContent = new Date(data.last_location_update).toLocaleString();
            } else {
                document.getElementById('last-update').textContent = 'Never';
            }
            document.getElementById('accuracy').textContent = '±50m';
        } else {
            document.getElementById('last-update').textContent = 'No location data';
            document.getElementById('accuracy').textContent = 'N/A';
        }
    } catch (error) {
        console.error('Error fetching rider location:', error);
    }
}

function updateRiderMarker(lat, lng, accuracy) {
    const position = { lat, lng };
    
    if (!riderMarker) {
        riderMarker = new google.maps.Marker({
            position: position,
            map: trackingMap,
            title: '{{ $rider->name }}',
            label: {
                text: '{{ substr($rider->name, 0, 1) }}',
                color: '#ffffff',
                fontSize: '14px',
                fontWeight: 'bold'
            },
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 15,
                fillColor: '#C1666B',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 3
            }
        });
        
        const infoWindow = new google.maps.InfoWindow({
            content: `<div style="padding: 5px;"><strong>{{ $rider->name }}</strong><br><small>Current Location</small></div>`
        });
        
        riderMarker.addListener('click', function() {
            infoWindow.open(trackingMap, riderMarker);
        });
    } else {
        riderMarker.setPosition(position);
    }
    
    trackingMap.panTo(position);
    
    if (accuracyCircle) {
        accuracyCircle.setMap(null);
    }
    
    accuracyCircle = new google.maps.Circle({
        map: trackingMap,
        center: position,
        radius: accuracy,
        fillColor: '#C1666B',
        fillOpacity: 0.1,
        strokeColor: '#C1666B',
        strokeOpacity: 0.3,
        strokeWeight: 1
    });
    
    reverseGeocode(lat, lng);
}

function reverseGeocode(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = { lat, lng };
    
    geocoder.geocode({ location: latlng }, (results, status) => {
        const addressEl = document.getElementById('current-address');
        if (status === 'OK' && results[0]) {
            addressEl.textContent = results[0].formatted_address;
        } else {
            addressEl.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    });
}

// Map will be initialized when modal is opened

window.addEventListener('beforeunload', function() {
    if (trackingInterval) {
        clearInterval(trackingInterval);
    }
});

function openLiveLocationModal() {
    document.getElementById('live-location-modal').classList.remove('hidden');
    document.getElementById('live-location-modal').classList.add('flex');
    
    if (!trackingMap) {
        initTrackingMap();
    }
}

function closeLiveLocationModal() {
    document.getElementById('live-location-modal').classList.add('hidden');
    document.getElementById('live-location-modal').classList.remove('flex');
}
</script>

<!-- Live Location Modal -->
<div id="live-location-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center flex-shrink-0">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Live Location - {{ $rider->name }}</h2>
            </div>
            <button onclick="closeLiveLocationModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="rider-tracking-map" style="height: 500px; width: 100%; border-radius: 8px;"></div>
            <div id="location-details" class="mt-4 space-y-3">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 mb-1">Current Location</p>
                    <p class="text-sm font-medium text-gray-900" id="current-address">Loading address...</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-600">Last Updated</p>
                        <p class="font-semibold text-gray-900" id="last-update">Never</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Accuracy</p>
                        <p class="font-semibold text-gray-900" id="accuracy">N/A</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
