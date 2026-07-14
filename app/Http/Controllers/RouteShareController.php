<?php

namespace App\Http\Controllers;

use App\Modules\Admin\Models\RouteShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteShareController extends Controller
{
    public function show($riderId)
    {
        // Find route share by rider ID
        $routeShare = RouteShare::where('rider_id', $riderId)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            abort(404, 'This route link has expired or is no longer valid.');
        }
        
        // Check if user is authenticated as admin
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Increment view count
        $routeShare->incrementViewCount();
        
        // Load rider with pending orders
        $rider = $routeShare->rider;
        
        // Ensure rider has a daily code
        $dailyCode = $rider->getDailyCode();
        // Get orders for route planning: assigned to rider but not yet delivered
        // Includes: 1) Orders not yet picked up (no pickup_date), 2) Orders in transit (no delivery_date)
        $pendingOrders = $rider->orders()
            ->where(function($query) {
                $query->whereNull('pickup_date') // Not yet picked up
                      ->orWhere(function($q) {
                          $q->whereNotNull('pickup_date') // Already picked up
                            ->whereNull('delivery_date'); // But not yet delivered
                      });
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();
        
        // Build route waypoints with proximity-based smart routing
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $allStops = [];
        
        // Collect all stops (pickups and drop-offs)
        foreach ($pendingOrders as $order) {
            // For orders not yet picked up: add both pickup AND dropoff
            if (!$order->pickup_date) {
                // Add pickup stop
                if ($order->pickup_address) {
                    $allStops[] = [
                        'address' => $order->pickup_address,
                        'type' => 'pickup',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'sender' => $order->sender_name ?? $order->customer_name,
                        'phone' => $order->sender_phone ?? $order->customer_phone,
                        'priority' => 1,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
                
                // Add dropoff stop (will be done after pickup due to constraint)
                if ($order->delivery_address) {
                    $allStops[] = [
                        'address' => $order->delivery_address,
                        'type' => 'dropoff',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'receiver' => $order->receiver_name ?? 'N/A',
                        'phone' => $order->receiver_phone ?? 'N/A',
                        'priority' => 2,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
            }
            // For orders already picked up: add only dropoff
            elseif ($order->pickup_date && !$order->delivery_date) {
                if ($order->delivery_address) {
                    $allStops[] = [
                        'address' => $order->delivery_address,
                        'type' => 'dropoff',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'receiver' => $order->receiver_name ?? 'N/A',
                        'phone' => $order->receiver_phone ?? 'N/A',
                        'priority' => 2,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
            }
        }
        
        // Optimize route using nearest neighbor algorithm with constraints
        $waypoints = $this->optimizeRoute($allStops, $startingPoint);
        
        // Calculate cumulative time and ETA for each stop
        $cumulativeTime = 0;
        foreach ($waypoints as &$waypoint) {
            // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
            $estimatedTravelTime = 15; // Default estimate
            $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
            
            $waypoint['estimated_time'] = $cumulativeTime;
            $waypoint['eta'] = now()->addMinutes($cumulativeTime)->format('g:i A');
        }
        
        // Generate Google Maps URL
        $googleMapsUrl = $this->generateGoogleMapsUrl($startingPoint, $waypoints);
        
        // Group orders by pickup address for better display
        $groupedOrders = $this->groupOrdersByPickup($waypoints);
        
        return view('route-share', compact('rider', 'waypoints', 'startingPoint', 'routeShare', 'dailyCode', 'googleMapsUrl', 'groupedOrders', 'isAdmin'));
    }
    
    public function prepareRouteData($rider)
    {
        $pendingOrders = $rider->orders()
            ->where(function($query) {
                $query->whereNull('pickup_date')
                      ->orWhere(function($q) {
                          $q->whereNotNull('pickup_date')
                            ->whereNull('delivery_date');
                      });
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();
        
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $allStops = [];
        
        foreach ($pendingOrders as $order) {
            // Always show pickup if not delivered yet
            if (!$order->delivery_date && $order->pickup_address) {
                $allStops[] = [
                    'address' => $order->pickup_address,
                    'type' => 'pickup',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'sender' => $order->sender_name ?? $order->customer_name,
                    'phone' => $order->sender_phone ?? $order->customer_phone,
                    'priority' => 1,
                    'paired_order_id' => $order->id,
                    'status' => $order->status,
                    'priority_level' => $order->priority_level ?? 'normal',
                    'item_description' => $order->item_description ?? 'N/A',
                    'is_picked_up' => !empty($order->pickup_date),
                ];
            }
            
            // Show delivery if not delivered yet
            if (!$order->delivery_date && $order->delivery_address) {
                $allStops[] = [
                    'address' => $order->delivery_address,
                    'type' => 'dropoff',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'receiver' => $order->receiver_name ?? 'N/A',
                    'phone' => $order->receiver_phone ?? 'N/A',
                    'priority' => 2,
                    'paired_order_id' => $order->id,
                    'status' => $order->status,
                    'priority_level' => $order->priority_level ?? 'normal',
                    'item_description' => $order->item_description ?? 'N/A',
                    'is_picked_up' => !empty($order->pickup_date),
                ];
            }
        }
        
        $waypoints = $this->optimizeRoute($allStops, $startingPoint);
        
        $cumulativeTime = 0;
        foreach ($waypoints as &$waypoint) {
            $estimatedTravelTime = 15;
            $cumulativeTime += $estimatedTravelTime + 5;
            
            $waypoint['estimated_time'] = $cumulativeTime;
            $waypoint['eta'] = now()->addMinutes($cumulativeTime)->format('g:i A');
        }
        
        $googleMapsUrl = $this->generateGoogleMapsUrl($startingPoint, $waypoints);
        $groupedOrders = $this->groupOrdersByPickup($waypoints);
        
        return compact('waypoints', 'startingPoint', 'googleMapsUrl', 'groupedOrders');
    }
    
    /**
     * Optimize route using smart hybrid approach with priority handling:
     * - Urgent and high-priority orders are visited first
     * - Dropoffs for already-picked-up orders can be delivered anytime (most flexible)
     * - New pickups must happen before their corresponding dropoffs
     * - Uses nearest neighbor with these constraints
     */
    private function optimizeRoute($stops, $startingPoint)
    {
        if (empty($stops)) {
            return [];
        }
        
        // Identify which orders are already picked up (from previous routes)
        $pickups = array_filter($stops, function($s) { return $s['type'] === 'pickup'; });
        $dropoffs = array_filter($stops, function($s) { return $s['type'] === 'dropoff'; });
        
        $alreadyPickedUpOrders = [];
        foreach ($dropoffs as $dropoff) {
            $hasPickupInRoute = false;
            foreach ($pickups as $pickup) {
                if ($pickup['paired_order_id'] === $dropoff['paired_order_id']) {
                    $hasPickupInRoute = true;
                    break;
                }
            }
            if (!$hasPickupInRoute) {
                $alreadyPickedUpOrders[] = $dropoff['paired_order_id'];
            }
        }
        
        // Separate stops by priority level
        $urgentStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'urgent'; });
        $highPriorityStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'high'; });
        $normalStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'normal'; });
        
        // Combine in priority order: urgent first, then high, then normal
        $prioritizedStops = array_merge(array_values($urgentStops), array_values($highPriorityStops), array_values($normalStops));
        
        $optimizedRoute = [];
        $remainingStops = $prioritizedStops;
        $currentLocation = $startingPoint;
        $pickedUpOrders = $alreadyPickedUpOrders; // Orders already in vehicle
        
        // Use nearest neighbor with smart constraints and priority
        while (!empty($remainingStops)) {
            $nearestStop = null;
            $nearestDistance = PHP_FLOAT_MAX;
            $nearestIndex = -1;
            $currentPriorityLevel = null;
            
            // First pass: find the highest priority level among valid stops
            foreach ($remainingStops as $stop) {
                if ($stop['type'] === 'dropoff' && !in_array($stop['paired_order_id'], $pickedUpOrders)) {
                    continue;
                }
                $priorityLevel = $stop['priority_level'] ?? 'normal';
                if ($currentPriorityLevel === null || $this->getPriorityWeight($priorityLevel) > $this->getPriorityWeight($currentPriorityLevel)) {
                    $currentPriorityLevel = $priorityLevel;
                }
            }
            
            // Second pass: find nearest stop among highest priority stops
            foreach ($remainingStops as $index => $stop) {
                // Constraint: Can only drop-off if item has been picked up
                if ($stop['type'] === 'dropoff' && !in_array($stop['paired_order_id'], $pickedUpOrders)) {
                    continue; // Skip this drop-off, item not picked up yet
                }
                
                // Only consider stops with current highest priority level
                if (($stop['priority_level'] ?? 'normal') !== $currentPriorityLevel) {
                    continue;
                }
                
                $distance = $this->estimateDistance($currentLocation, $stop['address']);
                
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestStop = $stop;
                    $nearestIndex = $index;
                }
            }
            
            if ($nearestStop === null) {
                // No valid stops found (shouldn't happen, but safety check)
                break;
            }
            
            // Add to optimized route
            $optimizedRoute[] = $nearestStop;
            
            // Track pickups
            if ($nearestStop['type'] === 'pickup') {
                $pickedUpOrders[] = $nearestStop['paired_order_id'];
            }
            
            // Update current location
            $currentLocation = $nearestStop['address'];
            
            // Remove from remaining stops
            array_splice($remainingStops, $nearestIndex, 1);
        }
        
        return $optimizedRoute;
    }
    
    /**
     * Get priority weight for sorting (higher = more urgent)
     */
    private function getPriorityWeight($priorityLevel)
    {
        return match($priorityLevel) {
            'urgent' => 3,
            'high' => 2,
            'normal' => 1,
            default => 1,
        };
    }
    
    /**
     * Group orders by pickup phone to show multiple deliveries from same sender
     */
    private function groupOrdersByPickup($waypoints)
    {
        $grouped = [];
        $pickupPhones = [];
        
        // First, collect all pickups with their phone numbers
        foreach ($waypoints as $waypoint) {
            if ($waypoint['type'] === 'pickup') {
                $pickupKey = md5(strtolower(trim($waypoint['phone'])));
                
                if (!isset($pickupPhones[$pickupKey])) {
                    $pickupPhones[$pickupKey] = [
                        'address' => $waypoint['address'],
                        'sender' => $waypoint['sender'],
                        'phone' => $waypoint['phone'],
                        'pickup_orders' => [],
                        'dropoffs' => []
                    ];
                }
                
                $pickupPhones[$pickupKey]['pickup_orders'][] = $waypoint;
            }
        }
        
        // Then, associate dropoffs with their pickups
        foreach ($waypoints as $waypoint) {
            if ($waypoint['type'] === 'dropoff') {
                // Find the corresponding pickup for this dropoff
                $orderId = $waypoint['paired_order_id'];
                
                // Search for the pickup with this order ID
                foreach ($waypoints as $pickup) {
                    if ($pickup['type'] === 'pickup' && $pickup['paired_order_id'] === $orderId) {
                        $pickupKey = md5(strtolower(trim($pickup['phone'])));
                        
                        if (isset($pickupPhones[$pickupKey])) {
                            $pickupPhones[$pickupKey]['dropoffs'][] = $waypoint;
                        }
                        break;
                    }
                }
            }
        }
        
        // Filter to only show pickups with multiple dropoffs
        foreach ($pickupPhones as $key => $data) {
            if (count($data['dropoffs']) > 1) {
                $grouped[] = $data;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Generate Google Maps URL with route waypoints
     */
    private function generateGoogleMapsUrl($startingPoint, $waypoints)
    {
        if (empty($waypoints)) {
            return null;
        }
        
        // Google Maps Directions URL format:
        // https://www.google.com/maps/dir/?api=1&origin=START&destination=END&waypoints=WAYPOINT1|WAYPOINT2|...
        
        $origin = urlencode($startingPoint);
        $destination = urlencode($waypoints[count($waypoints) - 1]['address']); // Last stop
        
        // Build waypoints string (all stops except the last one)
        $waypointAddresses = [];
        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $waypointAddresses[] = urlencode($waypoints[$i]['address']);
        }
        $waypointsParam = implode('|', $waypointAddresses);
        
        // Construct Google Maps URL
        $url = "https://www.google.com/maps/dir/?api=1&origin={$origin}&destination={$destination}";
        
        if (!empty($waypointsParam)) {
            $url .= "&waypoints={$waypointsParam}";
        }
        
        // Add travel mode (driving)
        $url .= "&travelmode=driving";
        
        return $url;
    }
    
    /**
     * Estimate distance between two addresses using Google Geocoding + Haversine formula
     */
    private function estimateDistance($address1, $address2)
    {
        try {
            $geo1 = $this->geocodeWithGoogle($address1);
            $geo2 = $this->geocodeWithGoogle($address2);

            if ($geo1 && $geo2) {
                return $this->haversineDistance($geo1['lat'], $geo1['lon'], $geo2['lat'], $geo2['lon']);
            }
        } catch (\Exception $e) {
            // Fall through to area-based estimation
        }

        return $this->estimateDistanceByArea($address1, $address2);
    }

    /**
     * Geocode an address using Google Maps Geocoding API, cached for 7 days
     */
    private function geocodeWithGoogle(string $address): ?array
    {
        $cacheKey = 'google_geo_' . md5(strtolower(trim($address)));

        return Cache::remember($cacheKey, 604800, function () use ($address) {
            $lower = strtolower($address);
            $query = (str_contains($lower, 'lagos') || str_contains($lower, 'ogun') || str_contains($lower, 'nigeria'))
                ? $address
                : $address . ', Nigeria';

            $response = Http::timeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address'    => $query,
                    'key'        => config('services.google_maps.api_key'),
                    'region'     => 'ng',
                    'components' => 'country:NG',
                ]);

            $data = $response->json();
            if (($data['status'] ?? '') === 'OK' && !empty($data['results'])) {
                $loc = $data['results'][0]['geometry']['location'];
                return ['lat' => (float) $loc['lat'], 'lon' => (float) $loc['lng']];
            }

            return null;
        });
    }

    /**
     * Haversine great-circle distance × 1.35 road factor, returns km
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)) * 1.35, 2);
    }
    
    /**
     * Fallback: Estimate distance using area/location matching
     */
    private function estimateDistanceByArea($address1, $address2)
    {
        // Extract area names from addresses (common Lagos areas)
        $lagosAreas = [
            'ikeja', 'lekki', 'vi', 'victoria island', 'ikoyi', 'surulere', 'yaba', 
            'gbagada', 'maryland', 'ojota', 'ketu', 'mile 12', 'ikorodu', 'ajah',
            'festac', 'apapa', 'isolo', 'oshodi', 'mushin', 'bariga', 'somolu',
            'agege', 'egbeda', 'alimosho', 'ifako', 'abule egba', 'iyana ipaja',
            'badagry', 'epe', 'magodo', 'omole', 'berger', 'ojodu', 'ogba',
            'anthony', 'obanikoro', 'palmgrove', 'onipanu', 'fadeyi', 'jibowu'
        ];
        
        $addr1Lower = strtolower($address1);
        $addr2Lower = strtolower($address2);
        
        // Find areas in both addresses
        $area1 = null;
        $area2 = null;
        
        foreach ($lagosAreas as $area) {
            if (strpos($addr1Lower, $area) !== false) {
                $area1 = $area;
                break;
            }
        }
        
        foreach ($lagosAreas as $area) {
            if (strpos($addr2Lower, $area) !== false) {
                $area2 = $area;
                break;
            }
        }
        
        // If same area, very close
        if ($area1 && $area2 && $area1 === $area2) {
            return 1 + (rand(0, 5) / 10); // 1-1.5 km
        }
        
        // If both areas found but different, use predefined distances
        if ($area1 && $area2) {
            return $this->getAreaDistance($area1, $area2);
        }
        
        // Fallback to string similarity
        similar_text($addr1Lower, $addr2Lower, $percent);
        $estimatedDistance = 100 - $percent;
        
        return max(5, $estimatedDistance); // Minimum 5km if no area match
    }
    
    /**
     * Get approximate distance between two Lagos areas
     */
    private function getAreaDistance($area1, $area2)
    {
        // Simplified distance matrix for major Lagos areas (in km)
        $distances = [
            'ikeja' => ['lekki' => 25, 'vi' => 15, 'yaba' => 10, 'surulere' => 8, 'ikorodu' => 30],
            'lekki' => ['ikeja' => 25, 'vi' => 8, 'yaba' => 20, 'ajah' => 10, 'ikorodu' => 35],
            'vi' => ['ikeja' => 15, 'lekki' => 8, 'yaba' => 12, 'ikoyi' => 3],
            'yaba' => ['ikeja' => 10, 'lekki' => 20, 'vi' => 12, 'surulere' => 5, 'gbagada' => 8],
            'surulere' => ['ikeja' => 8, 'yaba' => 5, 'isolo' => 6, 'oshodi' => 7],
            'ikorodu' => ['ikeja' => 30, 'lekki' => 35, 'gbagada' => 20, 'ketu' => 15],
        ];
        
        // Check if we have a predefined distance
        if (isset($distances[$area1][$area2])) {
            return $distances[$area1][$area2] + (rand(0, 10) / 10);
        }
        
        if (isset($distances[$area2][$area1])) {
            return $distances[$area2][$area1] + (rand(0, 10) / 10);
        }
        
        // Default distance if not in matrix
        return 15 + (rand(0, 20) / 10); // 15-17 km average
    }
}
