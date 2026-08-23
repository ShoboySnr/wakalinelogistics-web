<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop-off Route - {{ $rider->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="rider-id" content="{{ $rider->id }}">
    <style>
        :root {
            --brand-dark: #2F3437;
            --brand-accent: #C1666B;
            --brand-accent-hover: #a8555a;
        }
        body {
            font-family: Tahoma, Geneva, sans-serif;
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .rider-marker {
            width: 40px;
            height: 40px;
            background: var(--brand-accent);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .brand-bg { background-color: var(--brand-dark); }
        .brand-accent-bg { background-color: var(--brand-accent); }
        .brand-accent-hover:hover { background-color: var(--brand-accent-hover); }
        .brand-accent-text { color: var(--brand-accent); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
            <!-- Admin Banner -->
            <div class="rounded-lg p-3 mb-4" style="background-color:#fdf1f1;border-left:4px solid #C1666B;">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 brand-accent-text" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium" style="color:#8f4a4e;">Admin Mode - Full access enabled</p>
                </div>
            </div>
            @endif

            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-4 md:p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 md:gap-3">
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">Drop-off Route</h1>
                            <span id="live-indicator" class="flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full pulse"></span>
                                Live
                            </span>
                            <span id="tracking-status" class="flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                View Only
                            </span>
                        </div>
                        <p class="text-sm md:text-base text-gray-600 mt-2">Rider: <span class="font-semibold">{{ $rider->name }}</span></p>
                        <p class="text-xs text-gray-500 mt-1" id="last-update">Last updated: Just now</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button onclick="shareToWhatsApp()" class="flex-1 md:flex-none px-3 md:px-4 py-2 brand-accent-bg brand-accent-hover text-white text-xs md:text-sm rounded-md transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                <span class="hidden sm:inline">Share to WhatsApp</span>
                                <span class="sm:hidden">WhatsApp</span>
                            </button>
                            <button onclick="copyRouteLink()" class="flex-1 md:flex-none px-3 md:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm rounded-md transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                </svg>
                                <span class="hidden sm:inline">Copy Details</span>
                                <span class="sm:hidden">Copy</span>
                            </button>
                        </div>
                        <button id="enable-tracking-btn" onclick="showCodeModal()" class="w-full px-3 md:px-4 py-2 brand-accent-bg text-white text-xs md:text-sm rounded-md brand-accent-hover transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            Enable Tracking
                        </button>
                        <button onclick="showBankDetailsModal()" class="w-full px-3 md:px-4 py-2 brand-accent-bg brand-accent-hover text-white text-xs md:text-sm rounded-md transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            Bank Details
                        </button>
                    </div>
                </div>
                
                @if(count($waypoints) > 0)
                <div class="p-4 rounded" style="background-color: #fef2f2; border-left: 4px solid #C1666B;">
                    <p class="text-sm" style="color: var(--brand-accent);">
                        <strong data-stops-count>{{ count($waypoints) }}</strong> stops on this route
                    </p>
                </div>
                @else
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded">
                    <p class="text-sm text-gray-700">No active deliveries at the moment.</p>
                </div>
                @endif
            </div>

            @if(count($waypoints) > 0)
            <!-- Order Summary Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold" style="color: #C1666B;" id="pickup-count">0</div>
                    <div class="text-sm text-gray-600">To Pickup</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold" style="color: #2F3437;" id="transit-count">0</div>
                    <div class="text-sm text-gray-600">In Transit</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" id="completed-count">0</div>
                    <div class="text-sm text-gray-600">Completed</div>
                </div>
            </div>

            <!-- Live Tracking Section -->
            <div id="live-tracking-section" class="bg-white rounded-lg shadow p-4 mb-6 hidden">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900">Live Tracking Active</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Last Update</p>
                        <p class="font-semibold text-gray-900" id="location-last-update">--:--:--</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Accuracy</p>
                        <p class="font-semibold text-gray-900" id="location-accuracy">N/A</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Latitude</p>
                        <p class="font-mono text-xs text-gray-900" id="location-latitude">0.000000</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Longitude</p>
                        <p class="font-mono text-xs text-gray-900" id="location-longitude">0.000000</p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <input 
                    type="text" 
                    id="search-orders" 
                    placeholder="Search orders by number, customer name, phone, or address..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                >
            </div>

            <div id="orders-container">
            <!-- Grouped Orders (Multiple Deliveries from Same Pickup) -->
            @if(!empty($groupedOrders) && count($groupedOrders) > 0)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Grouped Pickups</h3>
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded">{{ count($groupedOrders) }}</span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Multiple deliveries from same pickup location</p>
                
                @foreach($groupedOrders as $group)
                <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                    <!-- Pickup Info -->
                    <div class="mb-3 pb-3 border-b border-gray-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Pickup From</span>
                            @if(isset($group['pickup_orders'][0]['status']))
                            <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                {{ ucwords(str_replace('_', ' ', $group['pickup_orders'][0]['status'])) }}
                            </span>
                            @endif
                        </div>
                        <p class="text-gray-900 font-semibold">{{ $group['sender'] }}</p>
                        <p class="text-gray-600 text-base">{{ $group['address'] }}</p>
                        <a href="tel:{{ $group['phone'] }}" class="text-base hover:underline" style="color: #C1666B;">{{ $group['phone'] }}</a>
                        
                        <div class="flex gap-2 mt-3">
                            <a href="tel:{{ $group['phone'] }}" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition-colors">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                Call
                            </a>
                            @if(isset($group['pickup_orders'][0]['order_id']))
                                @php
                                    $allPickedUp = collect($group['pickup_orders'])->every(fn($order) => $order['is_picked_up'] ?? false);
                                @endphp
                                @if(!$allPickedUp)
                                <button onclick="markGroupAsPickedUp([{{ implode(',', array_column($group['pickup_orders'], 'order_id')) }}])" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Mark Picked Up ({{ count($group['pickup_orders']) }})
                                </button>
                                @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Picked Up ✓
                                </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <!-- Dropoffs List -->
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Deliver To ({{ count($group['dropoffs']) }} Locations)</p>
                        @foreach($group['dropoffs'] as $index => $dropoff)
                        <div class="relative pl-6" data-order-id="{{ $dropoff['order_id'] ?? '' }}">
                            <!-- Connection Line -->
                            <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                            <div class="absolute left-0 top-4 w-2 h-2 bg-gray-400 rounded-full" style="transform: translateX(-3px);"></div>
                            
                            <div class="p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-semibold text-gray-900">{{ $dropoff['receiver'] }}</span>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded">
                                        #{{ $dropoff['order_number'] }}
                                    </span>
                                    @if(isset($dropoff['priority_level']) && $dropoff['priority_level'] === 'urgent')
                                    <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded">
                                        URGENT
                                    </span>
                                    @endif
                                </div>
                                <p class="text-gray-600 text-base mb-1">{{ $dropoff['address'] }}</p>
                                <a href="tel:{{ $dropoff['phone'] }}" class="text-base hover:underline" style="color: #C1666B;">{{ $dropoff['phone'] }}</a>
                                @if(isset($dropoff['item_description']) && $dropoff['item_description'] !== 'N/A')
                                <div class="mt-3">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Item Description</p>
                                    <p class="text-base font-bold text-gray-900">{{ $dropoff['item_description'] }}</p>
                                </div>
                                @endif
                                
                                <div class="flex gap-2 mt-3">
                                    <a href="tel:{{ $dropoff['phone'] }}" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                        </svg>
                                        Call
                                    </a>
                                    @if(isset($dropoff['order_id']))
                                    <button onclick="markAsDelivered('{{ $dropoff['order_id'] }}')" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Mark Delivered
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Route Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <!-- Orders List -->
                <div class="space-y-4">
                    @php
                        $groupedWaypoints = [];
                        $processedOrders = [];
                        
                        $groupedOrderIds = [];
                        if (!empty($groupedOrders)) {
                            foreach ($groupedOrders as $group) {
                                foreach ($group['dropoffs'] as $dropoff) {
                                    $groupedOrderIds[] = $dropoff['order_id'];
                                }
                            }
                        }
                        
                        foreach($waypoints as $waypoint) {
                            $orderId = $waypoint['paired_order_id'];
                            
                            if (in_array($waypoint['order_id'], $groupedOrderIds)) {
                                continue;
                            }
                            
                            if (!isset($processedOrders[$orderId])) {
                                $processedOrders[$orderId] = true;
                                
                                $pickup = collect($waypoints)->firstWhere(function($w) use ($orderId) {
                                    return $w['paired_order_id'] === $orderId && $w['type'] === 'pickup';
                                });
                                
                                $dropoff = collect($waypoints)->firstWhere(function($w) use ($orderId) {
                                    return $w['paired_order_id'] === $orderId && $w['type'] === 'dropoff';
                                });
                                
                                if ($pickup && $dropoff && 
                                    in_array($pickup['order_id'], $groupedOrderIds) && 
                                    in_array($dropoff['order_id'], $groupedOrderIds)) {
                                    continue;
                                }
                                
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
                    <div class="order-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
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
                                <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                    {{ ucwords(str_replace('_', ' ', $group['pickup']['status'] ?? 'pending')) }}
                                </span>
                            </div>
                            <div class="pl-4 border-l-2 border-gray-300 space-y-2">
                                <p class="text-base font-semibold text-gray-900">{{ $group['pickup']['sender'] }}</p>
                                <div class="text-base text-gray-600">
                                    <p>{{ $group['pickup']['address'] }}</p>
                                    <a href="tel:{{ $group['pickup']['phone'] }}" class="hover:underline" style="color: #C1666B;">{{ $group['pickup']['phone'] }}</a>
                                </div>
                                <div class="flex gap-2 mt-2">
                                    <a href="tel:{{ $group['pickup']['phone'] }}" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                        </svg>
                                        Call
                                    </a>
                                    <button onclick="markAsPickedUp('{{ $group['pickup']['order_id'] }}')" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Mark Picked Up
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Dropoff Section -->
                        @if($group['dropoff'])
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-gray-900 text-sm">DROP OFF</span>
                                <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
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
                                <div class="mt-3">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Item Description</p>
                                    <p class="text-base font-bold text-gray-900">{{ $group['dropoff']['item_description'] }}</p>
                                </div>
                                @endif
                                <div class="flex gap-2 mt-2">
                                    <a href="tel:{{ $group['dropoff']['phone'] }}" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                        </svg>
                                        Call
                                    </a>
                                    <button onclick="markAsDelivered('{{ $group['dropoff']['order_id'] }}')" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Mark Delivered
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>This link will expire on {{ $routeShare->expires_at->format('F d, Y \a\t g:i A') }}</p>
                <p class="mt-1">Viewed {{ $routeShare->view_count }} time(s)</p>
            </div>
        </div>
    </div>

    <!-- Bank Details Modal -->
    <div id="bank-details-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Bank Account Details</h3>
                <button onclick="hideBankDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="rounded-lg p-4" style="background-color:#fdf1f1;border:1px solid #e8a0a4;">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Bank Name</p>
                            <p class="font-semibold text-gray-900">OPay Digital Services Limited (OPay)</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Account Number</p>
                            <p class="font-semibold text-gray-900 text-lg" id="account-number">8100665758</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Account Name</p>
                            <p class="font-semibold text-gray-900">MARIA ANUOLUWAPO OYEYEMI</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="copyBankDetails()" class="flex-1 px-4 py-2 brand-accent-bg brand-accent-hover text-white rounded-md transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                    </svg>
                    Copy Details
                </button>
                <button onclick="hideBankDetailsModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Code Entry Modal -->
    <div id="code-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Enable Location Tracking</h3>
                <button onclick="hideCodeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <p class="text-sm text-gray-600 mb-4">
                Enter your daily access code to enable location tracking. Your admin will provide this code.
            </p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Access Code</label>
                <input 
                    type="text" 
                    id="access-code-input" 
                    maxlength="6"
                    placeholder="Enter 6-digit code"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#C1666B] focus:border-[#C1666B] uppercase text-center text-lg tracking-widest"
                    style="letter-spacing: 0.5em;"
                />
                <p id="code-error" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            
            <div class="flex gap-3">
                <button 
                    onclick="hideCodeModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    onclick="validateCode()" 
                    id="validate-btn"
                    class="flex-1 px-4 py-2 brand-accent-bg text-white rounded-md brand-accent-hover transition-colors"
                >
                    Verify Code
                </button>
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
        
        map = new google.maps.Map(document.getElementById('route-map'), {
            zoom: 12,
            center: { lat: 6.5244, lng: 3.3792 }
        });
        
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: false
        });
        
        const waypointAddresses = waypoints.slice(0, -1).map(wp => ({
            location: wp.address,
            stopover: true
        }));
        
        directionsService.route({
            origin: startingPoint,
            destination: waypoints[waypoints.length - 1].address,
            waypoints: waypointAddresses,
            travelMode: google.maps.TravelMode.DRIVING,
            optimizeWaypoints: false
        }, (response, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
            } else {
                console.error('Directions request failed:', status);
            }
        });
    }

    window.onload = initMap;

    const riderId = document.querySelector('meta[name="rider-id"]').content;
    const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
    let riderMarker = null;
    let updateInterval = null;
    let isTrackingEnabled = false;
    let riderAccessCode = localStorage.getItem(`rider_code_${riderId}`) || null;
    
    if (isAdmin) {
        isTrackingEnabled = true;
        localStorage.setItem(`tracking_enabled_${riderId}`, 'true');
        const enableBtn = document.getElementById('enable-tracking-btn');
        if (enableBtn) {
            enableBtn.style.display = 'none';
        }
        updateTrackingStatus(true);
        startLocationTracking();
    }

    async function fetchRouteData() {
        try {
            const response = await fetch(`/api/route-share/${riderId}/data`);
            const data = await response.json();
            
            if (data.success) {
                updateRouteDisplay(data);
                updateRiderLocation(data.rider);
                updateLastUpdateTime();
            }
        } catch (error) {
            console.error('Error fetching route data:', error);
            updateConnectionStatus(false);
        }
    }

    function updateLastUpdateTime() {
        const lastUpdateEl = document.getElementById('last-update');
        if (lastUpdateEl) {
            lastUpdateEl.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
        }
        updateConnectionStatus(true);
    }

    function updateConnectionStatus(isConnected) {
        const indicator = document.getElementById('live-indicator');
        if (!indicator) return;
        
        if (isConnected) {
            indicator.className = 'flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full';
            indicator.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full pulse"></span> Live';
        } else {
            indicator.className = 'flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full';
            indicator.innerHTML = '<span class="w-2 h-2 bg-red-500 rounded-full"></span> Offline';
        }
    }

    let lastOrderCount = {{ count($waypoints) }};
    
    async function updateRouteDisplay(data) {
        const currentOrderCount = data.waypoints.length;
        
        if (currentOrderCount !== lastOrderCount) {
            try {
                const response = await fetch(`/api/route-share/${riderId}/grouped-orders`);
                const result = await response.json();
                
                if (result.success) {
                    const container = document.getElementById('orders-container');
                    if (container) {
                        container.innerHTML = result.html;
                        
                        const stopsCount = document.querySelector('[data-stops-count]');
                        if (stopsCount) {
                            stopsCount.style.transition = 'all 0.3s ease';
                            stopsCount.style.transform = 'scale(1.2)';
                            stopsCount.textContent = currentOrderCount;
                            setTimeout(() => {
                                stopsCount.style.transform = 'scale(1)';
                            }, 300);
                        }
                        
                        if (currentOrderCount < lastOrderCount) {
                            const completed = lastOrderCount - currentOrderCount;
                            showNotification(`${completed} order(s) completed!`, 'success');
                        } else if (currentOrderCount > lastOrderCount) {
                            const added = currentOrderCount - lastOrderCount;
                            showNotification(`${added} new order(s) added!`, 'info');
                        }
                        
                        lastOrderCount = currentOrderCount;
                    }
                }
            } catch (error) {
                console.error('Error updating orders:', error);
            }
            return;
        }
        
        data.waypoints.forEach((waypoint, index) => {
            const waypointEl = document.querySelector(`[data-waypoint-id="${waypoint.order_id}"]`);
            if (waypointEl) {
                const stepNumber = waypointEl.querySelector('.rounded-full');
                if (stepNumber) {
                    stepNumber.textContent = index + 1;
                }
                
                const statusBadge = waypointEl.querySelector('.status-badge');
                if (statusBadge) {
                    const oldStatus = statusBadge.textContent;
                    updateStatusBadge(statusBadge, waypoint.status);
                    if (oldStatus !== statusBadge.textContent) {
                        statusBadge.style.animation = 'pulse 0.5s ease';
                    }
                }
                
                const etaBadge = waypointEl.querySelector('.text-blue-600');
                if (etaBadge) {
                    etaBadge.innerHTML = waypoint.eta;
                }
                const timeToStop = waypointEl.querySelector('[data-time-to-stop]');
                if (timeToStop) {
                    const previousWaypoint = index > 0 ? data.waypoints[index - 1] : null;
                    const timeToReach = previousWaypoint 
                        ? waypoint.estimated_time - previousWaypoint.estimated_time 
                        : waypoint.estimated_time;
                    timeToStop.innerHTML = `<strong>Time to reach:</strong> ~${timeToReach} min`;
                }
            }
        });
        
        if (newWaypoints.length > 0) {
            newWaypoints.forEach((waypoint, idx) => {
                const waypointIndex = data.waypoints.findIndex(wp => wp.order_id === waypoint.order_id);
                const waypointHtml = createWaypointElement(waypoint, waypointIndex, data.waypoints);
                waypointsContainer.insertAdjacentHTML('beforeend', waypointHtml);
                
                setTimeout(() => {
                    const newEl = document.querySelector(`[data-waypoint-id="${waypoint.order_id}"]`);
                    if (newEl) {
                        newEl.style.opacity = '0';
                        newEl.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            newEl.style.transition = 'all 0.5s ease';
                            newEl.style.opacity = '1';
                            newEl.style.transform = 'translateY(0)';
                        }, 10);
                    }
                }, 10);
            });
            
            showNotification(`${newWaypoints.length} new order(s) added to your route!`, 'info');
            updateMapRoute(data.waypoints);
        }
        
        const stopsCount = document.querySelector('[data-stops-count]');
        if (stopsCount && stopsCount.textContent !== data.total_stops.toString()) {
            stopsCount.style.transition = 'all 0.3s ease';
            stopsCount.style.transform = 'scale(1.2)';
            stopsCount.textContent = data.total_stops;
            setTimeout(() => {
                stopsCount.style.transform = 'scale(1)';
            }, 300);
        }
    }
    
    function createWaypointElement(waypoint, index, allWaypoints) {
        const isPickup = waypoint.type === 'pickup';
        const bgColor = isPickup ? 'brand-accent-bg' : 'brand-bg';
        const icon = '';
        const actionType = isPickup ? 'PICKUP' : 'DROP OFF';
        const contactLabel = isPickup ? 'From:' : 'To:';
        const contactName = isPickup ? waypoint.sender : waypoint.receiver;
        
        const previousWaypoint = index > 0 ? allWaypoints[index - 1] : null;
        const timeToReach = previousWaypoint 
            ? waypoint.estimated_time - previousWaypoint.estimated_time 
            : waypoint.estimated_time;
        
        return `
            <div class="flex items-start pb-4 border-b border-gray-200" data-waypoint-id="${waypoint.order_id}">
                <div class="flex-shrink-0 w-8 h-8 ${bgColor} rounded-full flex items-center justify-center text-white font-bold text-sm">
                    ${index + 1}
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="text-lg">${icon}</span>
                            <span class="font-semibold text-gray-900">${actionType}</span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                Order #${waypoint.order_number}
                            </span>
                            <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                ${formatStatus(waypoint.status)}
                            </span>
                        </div>
                        <span class="text-xs font-semibold bg-pink-50 px-2 py-1 rounded whitespace-nowrap brand-accent-text">
                            ${waypoint.eta}
                        </span>
                    </div>
                    <div class="space-y-1 text-sm">
                        <p class="text-gray-700">
                            <strong>${contactLabel}</strong> ${contactName}
                        </p>
                        <p class="text-gray-600">
                            <strong>Phone:</strong> ${waypoint.phone}
                        </p>
                        <p class="text-gray-600">
                            <strong>Location:</strong> ${waypoint.address}
                        </p>
                        <p class="text-xs text-gray-400 mt-1" data-time-to-stop>
                            <strong>Time to reach:</strong> ~${timeToReach} min
                        </p>
                    </div>
                </div>
            </div>
        `;
    }
    
    function formatStatus(status) {
        return status.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    function showNotification(message, type = 'info') {
        const colors = {
            info: 'brand-accent-bg',
            success: 'bg-green-600',
            warning: 'bg-yellow-600',
            error: 'bg-red-600'
        };
        
        const icons = {
            info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>',
            success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
            warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
            error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300`;
        notification.style.transform = 'translateX(400px)';
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    ${icons[type]}
                </svg>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    function showNotificationWithAction(message, type = 'info', buttonText, onClickAction) {
        const colors = {
            info: 'brand-accent-bg',
            success: 'bg-green-600',
            warning: 'bg-yellow-600',
            error: 'bg-red-600'
        };
        
        const icons = {
            info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>',
            success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
            warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
            error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300`;
        notification.style.transform = 'translateX(400px)';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    ${icons[type]}
                </svg>
                <span>${message}</span>
                <button class="ml-2 px-3 py-1 bg-white bg-opacity-20 hover:bg-opacity-30 rounded text-sm font-medium transition-colors">
                    ${buttonText}
                </button>
            </div>
        `;
        document.body.appendChild(notification);
        
        const button = notification.querySelector('button');
        button.addEventListener('click', () => {
            onClickAction();
            notification.remove();
        });
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }, 10000);
    }
    
    function updateMapRoute(waypoints) {
        if (!map || !directionsService || !directionsRenderer || waypoints.length === 0) return;
        
        const startingPoint = @json($startingPoint);
        const waypointAddresses = waypoints.slice(0, -1).map(wp => ({
            location: wp.address,
            stopover: true
        }));
        
        directionsService.route({
            origin: startingPoint,
            destination: waypoints[waypoints.length - 1].address,
            waypoints: waypointAddresses,
            travelMode: google.maps.TravelMode.DRIVING,
            optimizeWaypoints: false
        }, (response, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
            }
        });
    }

    function updateStatusBadge(badge, status) {
        badge.className = 'status-badge px-2 py-1 text-xs rounded';
        
        switch(status) {
            case 'delivered':
                badge.classList.add('bg-green-100', 'text-green-800');
                badge.textContent = 'Delivered';
                break;
            case 'in_transit':
                badge.classList.add('bg-pink-100');
                badge.style.color = 'var(--brand-accent)';
                badge.textContent = 'In Transit';
                break;
            case 'confirmed':
                badge.classList.add('bg-yellow-100', 'text-yellow-800');
                badge.textContent = 'Confirmed';
                break;
            default:
                badge.classList.add('bg-gray-100', 'text-gray-800');
                badge.textContent = 'Pending';
        }
    }

    function updateRiderLocation(rider) {
        if (!map || !rider.current_latitude || !rider.current_longitude) return;
        
        const riderPosition = {
            lat: parseFloat(rider.current_latitude),
            lng: parseFloat(rider.current_longitude)
        };
        
        if (!riderMarker) {
            riderMarker = new google.maps.Marker({
                position: riderPosition,
                map: map,
                title: `${rider.name} (Current Location)`,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#C1666B',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3
                },
                zIndex: 1000
            });
            
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="p-2">
                        <h3 class="font-bold">${rider.name}</h3>
                        <p class="text-sm text-gray-600">Current Location</p>
                        <p class="text-xs text-gray-500">Updated: ${new Date(rider.last_location_update).toLocaleTimeString()}</p>
                    </div>
                `
            });
            
            riderMarker.addListener('click', () => {
                infoWindow.open(map, riderMarker);
            });
        } else {
            riderMarker.setPosition(riderPosition);
        }
    }

    function showCodeModal() {
        document.getElementById('code-modal').classList.remove('hidden');
        document.getElementById('code-modal').classList.add('flex');
        document.getElementById('access-code-input').focus();
    }

    function hideCodeModal() {
        document.getElementById('code-modal').classList.add('hidden');
        document.getElementById('code-modal').classList.remove('flex');
        document.getElementById('access-code-input').value = '';
        document.getElementById('code-error').classList.add('hidden');
    }

    function showBankDetailsModal() {
        document.getElementById('bank-details-modal').classList.remove('hidden');
        document.getElementById('bank-details-modal').classList.add('flex');
    }

    function hideBankDetailsModal() {
        document.getElementById('bank-details-modal').classList.add('hidden');
        document.getElementById('bank-details-modal').classList.remove('flex');
    }

    function copyBankDetails() {
        const bankDetails = `Bank Details

Bank: OPay Digital Services Limited (OPay)
Account Number: 8100665758
Account Name: MARIA ANUOLUWAPO OYEYEMI`;

        navigator.clipboard.writeText(bankDetails).then(() => {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg> Copied!`;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy bank details');
        });
    }

    async function validateCode() {
        const code = document.getElementById('access-code-input').value.trim();
        const errorEl = document.getElementById('code-error');
        const validateBtn = document.getElementById('validate-btn');
        
        if (code.length !== 6) {
            errorEl.textContent = 'Please enter a 6-character code';
            errorEl.classList.remove('hidden');
            return;
        }
        
        validateBtn.disabled = true;
        validateBtn.textContent = 'Verifying...';
        
        try {
            const response = await fetch(`/api/route-share/${riderId}/validate-code`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code })
            });
            
            const data = await response.json();
            
            if (data.success) {
                localStorage.setItem(`rider_code_${riderId}`, code);
                riderAccessCode = code;
                isTrackingEnabled = true;
                
                updateTrackingStatus(true);
                hideCodeModal();
                startLocationTracking();
                
                showNotification(`Welcome ${data.rider_name}! Location tracking enabled.`, 'success');
            } else {
                localStorage.removeItem(`rider_code_${riderId}`);
                localStorage.removeItem(`tracking_enabled_${riderId}`);
                riderAccessCode = null;
                isTrackingEnabled = false;
                
                errorEl.textContent = data.message || 'Invalid code. Please enter the current access code.';
                errorEl.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error validating code:', error);
            errorEl.textContent = 'Error validating code. Please try again.';
            errorEl.classList.remove('hidden');
        } finally {
            validateBtn.disabled = false;
            validateBtn.textContent = 'Verify Code';
        }
    }

    function updateTrackingStatus(enabled) {
        const statusBadge = document.getElementById('tracking-status');
        const enableBtn = document.getElementById('enable-tracking-btn');
        
        if (enabled) {
            statusBadge.className = 'flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full';
            statusBadge.innerHTML = `
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                Tracking Active
            `;
            enableBtn.style.display = 'none';
        } else {
            statusBadge.className = 'flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full';
            statusBadge.innerHTML = `
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                View Only
            `;
            enableBtn.style.display = 'flex';
        }
    }

    let trackingMap = null;
    let trackingMarker = null;
    let trackingCircle = null;

    function initializeTrackingMap(latitude, longitude) {
        const mapElement = document.getElementById('rider-tracking-map');
        if (!mapElement) return;

        trackingMap = new google.maps.Map(mapElement, {
            zoom: 15,
            center: { lat: latitude, lng: longitude },
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true
        });

        trackingMarker = new google.maps.Marker({
            position: { lat: latitude, lng: longitude },
            map: trackingMap,
            title: 'Current Location',
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 12,
                fillColor: '#C1666B',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 3
            }
        });

        trackingCircle = new google.maps.Circle({
            map: trackingMap,
            center: { lat: latitude, lng: longitude },
            radius: 50,
            fillColor: '#C1666B',
            fillOpacity: 0.1,
            strokeColor: '#C1666B',
            strokeOpacity: 0.3,
            strokeWeight: 1
        });

        document.getElementById('live-tracking-section').classList.remove('hidden');
    }

    function updateTrackingMap(latitude, longitude, accuracy) {
        if (!trackingMap) {
            initializeTrackingMap(latitude, longitude);
        } else {
            const position = { lat: latitude, lng: longitude };
            
            // Update marker position
            trackingMarker.setPosition(position);
            
            // Update accuracy circle
            trackingCircle.setCenter(position);
            trackingCircle.setRadius(accuracy || 50);
            
            // Center map on new position
            trackingMap.panTo(position);
        }

        const lastUpdateEl = document.getElementById('location-last-update');
        const latEl = document.getElementById('location-latitude');
        const lngEl = document.getElementById('location-longitude');
        const accEl = document.getElementById('location-accuracy');
        
        if (lastUpdateEl) lastUpdateEl.textContent = new Date().toLocaleTimeString();
        if (latEl) latEl.textContent = latitude.toFixed(6);
        if (lngEl) lngEl.textContent = longitude.toFixed(6);
        if (accEl) accEl.textContent = accuracy ? `±${Math.round(accuracy)}m` : 'N/A';
    }

    function startLocationTracking() {
        if (!isTrackingEnabled) {
            console.log('Location tracking not enabled');
            return;
        }
        
        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(
                async (position) => {
                    const { latitude, longitude, accuracy } = position.coords;
                    
                    updateTrackingMap(latitude, longitude, accuracy);
                    
                    try {
                        await fetch(`/api/route-share/${riderId}/location`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ latitude, longitude })
                        });
                    } catch (error) {
                        console.error('Error updating location:', error);
                    }
                },
                (error) => {},
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        }
    }

    if (riderAccessCode) {
        const codeInput = document.getElementById('access-code-input');
        if (codeInput) {
            codeInput.value = riderAccessCode;
        }
        validateCode();
    }

    // Auto-refresh orders every 30 seconds
    let orderRefreshInterval = setInterval(() => {
        fetchRouteData();
    }, 30000);

    // Initial fetch on page load
    setTimeout(() => {
        fetchRouteData();
    }, 2000);

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (orderRefreshInterval) {
            clearInterval(orderRefreshInterval);
        }
    });

    function toggleOrderDetails(element) {
        const card = element.closest('.order-card');
        const details = card.querySelector('.order-details');
        const icon = card.querySelector('.order-toggle-icon');
        
        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            details.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    let allExpanded = false;
    function toggleAllOrders() {
        const cards = document.querySelectorAll('.order-card');
        const toggleText = document.getElementById('toggle-text');
        
        cards.forEach(card => {
            const details = card.querySelector('.order-details');
            const icon = card.querySelector('.order-toggle-icon');
            
            if (allExpanded) {
                details.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            } else {
                details.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            }
        });
        
        allExpanded = !allExpanded;
        toggleText.textContent = allExpanded ? 'Collapse All' : 'Expand All';
    }

    function copyAddress(address) {
        navigator.clipboard.writeText(address).then(() => {
            showNotification('Address copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Failed to copy address', 'error');
        });
    }

    document.getElementById('search-orders').addEventListener('input', filterOrders);

    function filterOrders() {
        const searchTerm = document.getElementById('search-orders').value.toLowerCase();
        const cards = document.querySelectorAll('.order-card');
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const matchesSearch = cardText.includes(searchTerm);
            
            if (matchesSearch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        updateOrderCounts();
    }

    function updateOrderCounts() {
        let pickupCount = 0;
        let transitCount = 0;
        let completedCount = 0;
        
        // Count from all status badges on the page
        const statusBadges = document.querySelectorAll('.status-badge');
        
        statusBadges.forEach(badge => {
            // Skip if parent card is hidden
            const parentCard = badge.closest('.order-card, .bg-gray-50, .bg-white');
            if (parentCard && parentCard.style.display === 'none') return;
            
            const statusText = badge.textContent.trim().toLowerCase();
            
            // Find the section label by looking at the parent structure
            let sectionLabel = '';
            
            // Look for PICKUP or DROP OFF text in the same parent div
            const parentSection = badge.closest('.mb-4, .space-y-2, div');
            if (parentSection) {
                const allText = parentSection.textContent.toUpperCase();
                if (allText.includes('PICKUP') && !allText.includes('DROP OFF')) {
                    sectionLabel = 'PICKUP';
                } else if (allText.includes('DROP OFF')) {
                    sectionLabel = 'DROP OFF';
                }
            }
            
            // Count based on status and section
            // Status values: pending, confirmed, in_transit, delivered, cancelled
            if (sectionLabel === 'PICKUP') {
                if (statusText.includes('pending') || statusText.includes('confirmed')) {
                    pickupCount++;
                } else if (statusText.includes('in transit') || statusText.includes('transit')) {
                    transitCount++;
                }
            } else if (sectionLabel === 'DROP OFF') {
                if (statusText.includes('delivered')) {
                    completedCount++;
                } else if (statusText.includes('in transit') || statusText.includes('transit')) {
                    transitCount++;
                }
            }
        });
        
        document.getElementById('pickup-count').textContent = pickupCount;
        document.getElementById('transit-count').textContent = transitCount;
        document.getElementById('completed-count').textContent = completedCount;
    }

    updateOrderCounts();

    async function updateOrderStatus(orderId, status) {
        const url = `/api/route-share/${riderId}/orders/${orderId}/status`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    [status === 'in_transit' ? 'pickup_date' : 'dropoff_date']: new Date().toISOString()
                })
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error Response:', errorText);
                showNotification(`Error ${response.status}: ${errorText}`, 'error');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Order status updated successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('Failed to update order status', 'error');
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            showNotification('Error updating order status', 'error');
        }
    }
    
    function markAsPickedUp(orderId) {
        if (confirm('Mark this order as picked up?')) {
            updateOrderStatus(orderId, 'in_transit');
        }
    }
    
    async function markGroupAsPickedUp(orderIds) {
        const count = orderIds.length;
        if (confirm(`Mark all ${count} orders in this group as picked up?`)) {
            try {
                const response = await fetch(`/api/route-share/${riderId}/orders/0/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        status: 'in_transit',
                        order_ids: orderIds,
                        pickup_date: new Date().toISOString()
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showNotification(`${count} orders marked as picked up!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Failed to update orders', 'error');
                }
            } catch (error) {
                console.error('Error updating grouped orders:', error);
                showNotification('Error updating orders', 'error');
            }
        }
    }
    
    function markAsDelivered(orderId) {
        if (confirm('Mark this order as delivered?')) {
            updateOrderStatus(orderId, 'delivered');
        }
    }

    startLocationTracking();
    
    function copyRouteLink() {
        const riderName = "{{ $rider->name }}";
        const routeUrl = window.location.href;
        const expiryDate = "{{ $routeShare->expires_at->format('M d, Y') }}";
        const totalOrders = {{ count($waypoints) }};
        
        let message = `DELIVERY ROUTE FOR ${riderName.toUpperCase()}\n\n`;
        message += `Total Orders: ${totalOrders}\n`;
        message += `Expires: ${expiryDate}\n`;
        message += `━━━━━━━━━━━━━━━━━━━━\n\n`;
        
        @if(!empty($groupedOrders) && count($groupedOrders) > 0)
        message += `GROUPED PICKUPS ({{ count($groupedOrders) }})\n\n`;
        @foreach($groupedOrders as $index => $group)
        message += `{{ $index + 1 }}. PICKUP FROM:\n`;
        message += `   Name: {{ $group['sender'] }}\n`;
        message += `   Address: {{ $group['address'] }}\n`;
        message += `   Phone: {{ $group['phone'] }}\n`;
        message += `   Deliveries: {{ count($group['dropoffs']) }}\n\n`;
        message += `   DELIVER TO:\n`;
        @foreach($group['dropoffs'] as $dIndex => $dropoff)
        message += `   {{ $dIndex + 1 }}. {{ $dropoff['receiver'] }} (#{{ $dropoff['order_number'] }})\n`;
        message += `      Address: {{ $dropoff['address'] }}\n`;
        message += `      Phone: {{ $dropoff['phone'] }}\n`;
        @if(isset($dropoff['item_description']) && $dropoff['item_description'] !== 'N/A')
        message += `      Item: {{ $dropoff['item_description'] }}\n`;
        @endif
        @if(isset($dropoff['priority_level']) && $dropoff['priority_level'] === 'urgent')
        message += `      URGENT\n`;
        @endif
        message += `\n`;
        @endforeach
        message += `━━━━━━━━━━━━━━━━━━━━\n\n`;
        @endforeach
        @endif
        
        message += `ALL ORDERS\n\n`;
        let orderNum = 1;
        @foreach($waypoints as $waypoint)
        message += `${orderNum}. {{ strtoupper($waypoint['type']) }} (#{{ $waypoint['order_number'] }})\n`;
        @if($waypoint['type'] === 'pickup')
        message += `   Name: {{ $waypoint['sender'] }}\n`;
        @else
        message += `   Name: {{ $waypoint['receiver'] }}\n`;
        @endif
        message += `   Address: {{ $waypoint['address'] }}\n`;
        message += `   Phone: {{ $waypoint['phone'] }}\n`;
        @if(isset($waypoint['item_description']) && $waypoint['item_description'] !== 'N/A')
        message += `   Item: {{ $waypoint['item_description'] }}\n`;
        @endif
        @if(isset($waypoint['priority_level']) && $waypoint['priority_level'] === 'urgent')
        message += `   URGENT\n`;
        @elseif(isset($waypoint['priority_level']) && $waypoint['priority_level'] === 'high')
        message += `   HIGH PRIORITY\n`;
        @endif
        message += `\n`;
        orderNum++;
        @endforeach
        
        message += `━━━━━━━━━━━━━━━━━━━━\n`;
        message += `Track Live: ${routeUrl}\n\n`;
        message += `Click the link to view live tracking and update delivery status`;
        
        navigator.clipboard.writeText(message).then(() => {
            showNotification('Route details copied to clipboard!', 'success');
        }).catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = message;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showNotification('Route details copied to clipboard!', 'success');
            } catch (err) {
                showNotification('Failed to copy details', 'error');
            }
            document.body.removeChild(textarea);
        });
    }
    
    function shareToWhatsApp() {
        const riderName = "{{ $rider->name }}";
        const routeUrl = window.location.href;
        const expiryDate = "{{ $routeShare->expires_at->format('M d, Y') }}";
        const totalOrders = {{ count($waypoints) }};
        
        let message = `*DELIVERY ROUTE FOR ${riderName.toUpperCase()}*\n\n`;
        message += `Total Orders: ${totalOrders}\n`;
        message += `Expires: ${expiryDate}\n`;
        message += `━━━━━━━━━━━━━━━━━━━━\n\n`;
        
        @if(!empty($groupedOrders) && count($groupedOrders) > 0)
        message += `*GROUPED PICKUPS* ({{ count($groupedOrders) }})\n\n`;
        @foreach($groupedOrders as $index => $group)
        message += `*{{ $index + 1 }}. PICKUP FROM:*\n`;
        message += `   Name: {{ $group['sender'] }}\n`;
        message += `   Address: {{ $group['address'] }}\n`;
        message += `   Phone: {{ $group['phone'] }}\n`;
        message += `   Deliveries: {{ count($group['dropoffs']) }}\n\n`;
        message += `   *DELIVER TO:*\n`;
        @foreach($group['dropoffs'] as $dIndex => $dropoff)
        message += `   {{ $dIndex + 1 }}. {{ $dropoff['receiver'] }} (#{{ $dropoff['order_number'] }})\n`;
        message += `      Address: {{ $dropoff['address'] }}\n`;
        message += `      Phone: {{ $dropoff['phone'] }}\n`;
        @if(isset($dropoff['item_description']) && $dropoff['item_description'] !== 'N/A')
        message += `      Item: {{ $dropoff['item_description'] }}\n`;
        @endif
        @if(isset($dropoff['priority_level']) && $dropoff['priority_level'] === 'urgent')
        message += `      URGENT\n`;
        @endif
        message += `\n`;
        @endforeach
        message += `━━━━━━━━━━━━━━━━━━━━\n\n`;
        @endforeach
        @endif
        
        message += `*ALL ORDERS*\n\n`;
        let orderNum = 1;
        @foreach($waypoints as $waypoint)
        message += `*${orderNum}. {{ strtoupper($waypoint['type']) }}* (#{{ $waypoint['order_number'] }})\n`;
        @if($waypoint['type'] === 'pickup')
        message += `   Name: {{ $waypoint['sender'] }}\n`;
        @else
        message += `   Name: {{ $waypoint['receiver'] }}\n`;
        @endif
        message += `   Address: {{ $waypoint['address'] }}\n`;
        message += `   Phone: {{ $waypoint['phone'] }}\n`;
        @if(isset($waypoint['item_description']) && $waypoint['item_description'] !== 'N/A')
        message += `   Item: {{ $waypoint['item_description'] }}\n`;
        @endif
        @if(isset($waypoint['priority_level']) && $waypoint['priority_level'] === 'urgent')
        message += `   URGENT\n`;
        @elseif(isset($waypoint['priority_level']) && $waypoint['priority_level'] === 'high')
        message += `   HIGH PRIORITY\n`;
        @endif
        message += `\n`;
        orderNum++;
        @endforeach
        
        message += `━━━━━━━━━━━━━━━━━━━━\n`;
        message += `*Track Live:*\n${routeUrl}\n\n`;
        message += `Click the link to view live tracking and update delivery status`;
        
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
        
        window.open(whatsappUrl, '_blank');
    }
    </script>
</body>
</html>
