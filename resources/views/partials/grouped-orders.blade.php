@if(!empty($groupedOrders) && count($groupedOrders) > 0)
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <h3 class="text-lg font-bold text-gray-900">Grouped Pickups</h3>
        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded">{{ count($groupedOrders) }}</span>
    </div>
    <p class="text-sm text-gray-600 mb-4">Multiple deliveries from same pickup location</p>
    
    @foreach($groupedOrders as $group)
    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
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
        
        <div class="space-y-3">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Deliver To ({{ count($group['dropoffs']) }} Locations)</p>
            @foreach($group['dropoffs'] as $index => $dropoff)
            <div class="relative pl-6" data-order-id="{{ $dropoff['order_id'] ?? '' }}">
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
                        <button onclick="markAsDelivered('{{ $dropoff['order_id'] }}')" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Mark Delivered
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endif

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
                @if(!($group['pickup']['is_picked_up'] ?? false))
                <button onclick="markAsPickedUp('{{ $group['pickup']['order_id'] }}')" class="inline-flex items-center gap-1 px-3 py-1 brand-accent-bg hover:opacity-90 text-white text-xs font-medium rounded transition-colors">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Mark Picked Up
                </button>
                @else
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Picked Up ✓
                </span>
                @endif
            </div>
        </div>
    </div>
    @endif
    
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
