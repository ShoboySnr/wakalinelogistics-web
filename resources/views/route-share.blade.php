<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Route - {{ $rider->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="route-token" content="{{ $routeShare->token }}">
    <style>
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
            background: #3B82F6;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">🚚 Delivery Route</h1>
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
                        <p class="text-gray-600 mt-1">Rider: <span class="font-semibold">{{ $rider->name }}</span></p>
                        <p class="text-xs text-gray-500 mt-1" id="last-update">Last updated: Just now</p>
                    </div>
                    <div class="text-right">
                        <button id="enable-tracking-btn" onclick="showCodeModal()" class="mb-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            Enable Tracking
                        </button>
                        <p class="text-sm text-gray-500">Expires</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $routeShare->expires_at->format('M d, Y') }}</p>
                    </div>
                </div>
                
                @if($waypoints->count() > 0)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                    <p class="text-sm text-blue-700">
                        <strong data-stops-count>{{ $waypoints->count() }}</strong> stops on this route
                    </p>
                </div>
                @else
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded">
                    <p class="text-sm text-gray-700">No active deliveries at the moment.</p>
                </div>
                @endif
            </div>

            @if($waypoints->count() > 0)
            <!-- Route Map -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">📍 Route Map</h2>
                <div id="route-map" class="w-full h-96 rounded-lg border border-gray-300"></div>
            </div>

            <!-- Route Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">📋 Route Plan</h2>
                
                <!-- Starting Point -->
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                            S
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold text-gray-900">Starting Point</p>
                            <p class="text-gray-600">{{ $startingPoint }}</p>
                        </div>
                    </div>
                </div>

                <!-- Waypoints -->
                <div class="space-y-4">
                    @foreach($waypoints as $index => $waypoint)
                    <div class="flex items-start pb-4 {{ !$loop->last ? 'border-b border-gray-200' : '' }}" data-waypoint-id="{{ $waypoint['order_id'] }}">
                        <div class="flex-shrink-0 w-8 h-8 {{ $waypoint['type'] === 'pickup' ? 'bg-blue-500' : 'bg-purple-500' }} rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ $index + 1 }}
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center flex-wrap gap-2">
                                    <span class="text-lg">{{ $waypoint['type'] === 'pickup' ? '📦' : '🏠' }}</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $waypoint['type'] === 'pickup' ? 'PICKUP' : 'DROP OFF' }}
                                    </span>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                        Order #{{ $waypoint['order_number'] }}
                                    </span>
                                    <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                        ⏳ {{ ucwords(str_replace('_', ' ', $waypoint['status'] ?? 'pending')) }}
                                    </span>
                                </div>
                                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded whitespace-nowrap">
                                    ⏱️ {{ $waypoint['eta'] }}
                                </span>
                            </div>
                            
                            <div class="space-y-1 text-sm">
                                <p class="text-gray-700">
                                    <strong>{{ $waypoint['type'] === 'pickup' ? 'From:' : 'To:' }}</strong>
                                    {{ $waypoint['type'] === 'pickup' ? $waypoint['sender'] : $waypoint['receiver'] }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Phone:</strong> {{ $waypoint['phone'] }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Location:</strong> {{ $waypoint['address'] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1" data-time-to-stop>
                                    <strong>Time to reach:</strong> ~{{ $loop->first ? $waypoint['estimated_time'] : ($waypoint['estimated_time'] - $waypoints[$loop->index - 1]['estimated_time']) }} min
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Footer -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>This link will expire on {{ $routeShare->expires_at->format('F d, Y \a\t g:i A') }}</p>
                <p class="mt-1">Viewed {{ $routeShare->view_count }} time(s)</p>
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
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase text-center text-lg tracking-widest"
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
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
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

    // Real-time updates
    const routeToken = document.querySelector('meta[name="route-token"]').content;
    let riderMarker = null;
    let updateInterval = null;
    let isTrackingEnabled = false;
    let riderAccessCode = localStorage.getItem(`rider_code_${routeToken}`) || null;

    // Fetch and update route data
    async function fetchRouteData() {
        try {
            const response = await fetch(`/api/route-share/${routeToken}/data`);
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

    // Update last update timestamp
    function updateLastUpdateTime() {
        const lastUpdateEl = document.getElementById('last-update');
        if (lastUpdateEl) {
            lastUpdateEl.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
        }
        updateConnectionStatus(true);
    }

    // Update connection status indicator
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

    // Update route display with new data
    function updateRouteDisplay(data) {
        const waypointsContainer = document.querySelector('.space-y-4');
        if (!waypointsContainer) return;
        
        // Track existing waypoint IDs
        const existingIds = new Set();
        document.querySelectorAll('[data-waypoint-id]').forEach(el => {
            existingIds.add(el.getAttribute('data-waypoint-id'));
        });
        
        // Track new waypoint IDs from server
        const newIds = new Set(data.waypoints.map(wp => wp.order_id.toString()));
        
        // Check for removed waypoints (completed/delivered orders)
        const removedWaypoints = Array.from(existingIds).filter(id => !newIds.has(id));
        if (removedWaypoints.length > 0) {
            removedWaypoints.forEach(orderId => {
                const waypointEl = document.querySelector(`[data-waypoint-id="${orderId}"]`);
                if (waypointEl) {
                    // Fade out animation
                    waypointEl.style.transition = 'all 0.5s ease';
                    waypointEl.style.opacity = '0';
                    waypointEl.style.transform = 'translateX(-20px)';
                    setTimeout(() => waypointEl.remove(), 500);
                }
            });
            
            showNotification(`${removedWaypoints.length} order(s) completed!`, 'success');
        }
        
        // Check for new waypoints
        const newWaypoints = data.waypoints.filter(wp => !existingIds.has(wp.order_id.toString()));
        
        // Update existing waypoints
        data.waypoints.forEach((waypoint, index) => {
            const waypointEl = document.querySelector(`[data-waypoint-id="${waypoint.order_id}"]`);
            if (waypointEl) {
                // Update step number
                const stepNumber = waypointEl.querySelector('.rounded-full');
                if (stepNumber) {
                    stepNumber.textContent = index + 1;
                }
                
                // Update status badge with animation
                const statusBadge = waypointEl.querySelector('.status-badge');
                if (statusBadge) {
                    const oldStatus = statusBadge.textContent;
                    updateStatusBadge(statusBadge, waypoint.status);
                    if (oldStatus !== statusBadge.textContent) {
                        statusBadge.style.animation = 'pulse 0.5s ease';
                    }
                }
                
                // Update ETA
                const etaBadge = waypointEl.querySelector('.text-blue-600');
                if (etaBadge) {
                    etaBadge.innerHTML = `⏱️ ${waypoint.eta}`;
                }
                
                // Update time to reach (calculate from previous waypoint)
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
        
        // Add new waypoints dynamically
        if (newWaypoints.length > 0) {
            newWaypoints.forEach((waypoint, idx) => {
                const waypointIndex = data.waypoints.findIndex(wp => wp.order_id === waypoint.order_id);
                const waypointHtml = createWaypointElement(waypoint, waypointIndex, data.waypoints);
                waypointsContainer.insertAdjacentHTML('beforeend', waypointHtml);
                
                // Animate new waypoint
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
            
            // Show notification
            showNotification(`${newWaypoints.length} new order(s) added to your route!`, 'info');
            
            // Update route on map
            updateMapRoute(data.waypoints);
        }
        
        // Update total stops count with animation
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
    
    // Create waypoint HTML element
    function createWaypointElement(waypoint, index, allWaypoints) {
        const isPickup = waypoint.type === 'pickup';
        const bgColor = isPickup ? 'bg-blue-500' : 'bg-purple-500';
        const icon = isPickup ? '📦' : '🏠';
        const actionType = isPickup ? 'PICKUP' : 'DROP OFF';
        const contactLabel = isPickup ? 'From:' : 'To:';
        const contactName = isPickup ? waypoint.sender : waypoint.receiver;
        
        // Calculate time to reach this stop
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
                                ⏳ ${formatStatus(waypoint.status)}
                            </span>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded whitespace-nowrap">
                            ⏱️ ${waypoint.eta}
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
    
    // Format status for display
    function formatStatus(status) {
        return status.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    // Show notification
    function showNotification(message, type = 'info') {
        const colors = {
            info: 'bg-blue-600',
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
    
    // Update map route with new waypoints
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

    // Update status badge appearance
    function updateStatusBadge(badge, status) {
        badge.className = 'status-badge px-2 py-1 text-xs rounded';
        
        switch(status) {
            case 'delivered':
                badge.classList.add('bg-green-100', 'text-green-800');
                badge.textContent = '✓ Delivered';
                break;
            case 'in_transit':
                badge.classList.add('bg-blue-100', 'text-blue-800');
                badge.textContent = '🚚 In Transit';
                break;
            case 'confirmed':
                badge.classList.add('bg-yellow-100', 'text-yellow-800');
                badge.textContent = '⏳ Confirmed';
                break;
            default:
                badge.classList.add('bg-gray-100', 'text-gray-800');
                badge.textContent = '📋 Pending';
        }
    }

    // Update rider location on map
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
                    fillColor: '#3B82F6',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3
                },
                zIndex: 1000
            });
            
            // Add info window
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

    // Modal control functions
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

    // Validate access code
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
            const response = await fetch(`/api/route-share/${routeToken}/validate-code`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store code in localStorage
                localStorage.setItem(`rider_code_${routeToken}`, code);
                riderAccessCode = code;
                isTrackingEnabled = true;
                
                // Update UI
                updateTrackingStatus(true);
                hideCodeModal();
                
                // Start location tracking
                startLocationTracking();
                
                showNotification(`Welcome ${data.rider_name}! Location tracking enabled.`, 'success');
            } else {
                errorEl.textContent = data.message || 'Invalid code';
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

    // Update tracking status UI
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

    // Track rider location using geolocation
    function startLocationTracking() {
        if (!isTrackingEnabled) {
            console.log('Location tracking not enabled');
            return;
        }
        
        if ('geolocation' in navigator) {
            console.log('Starting location tracking');
            navigator.geolocation.watchPosition(
                async (position) => {
                    const { latitude, longitude } = position.coords;
                    
                    try {
                        await fetch(`/api/route-share/${routeToken}/location`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ latitude, longitude })
                        });
                        console.log('Location updated:', latitude, longitude);
                    } catch (error) {
                        console.error('Error updating location:', error);
                    }
                },
                (error) => {
                    console.error('Geolocation error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            console.log('Geolocation not supported');
        }
    }

    // Check if code is already stored and valid on page load
    if (riderAccessCode) {
        validateCode();
    }

    // Mark order as picked up or delivered
    async function updateOrderStatus(orderId, status) {
        try {
            const response = await fetch(`/api/route-share/${routeToken}/orders/${orderId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    [status === 'in_transit' ? 'pickup_date' : 'delivery_date']: new Date().toISOString()
                })
            });
            
            const data = await response.json();
            if (data.success) {
                // Refresh route data
                await fetchRouteData();
            }
        } catch (error) {
            console.error('Error updating order status:', error);
        }
    }

    // Start auto-refresh every 30 seconds
    updateInterval = setInterval(fetchRouteData, 30000);

    // Start location tracking if user allows
    startLocationTracking();

    // Initial fetch
    fetchRouteData();
    </script>
</body>
</html>
