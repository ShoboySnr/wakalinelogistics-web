<?php

namespace App\Http\Controllers;

use App\Modules\Admin\Models\RouteShare;
use Illuminate\Http\Request;

class RouteShareController extends Controller
{
    public function show($token)
    {
        $routeShare = RouteShare::where('token', $token)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            abort(404, 'This route link has expired or is no longer valid.');
        }
        
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
        
        return view('route-share', compact('rider', 'waypoints', 'startingPoint', 'routeShare', 'dailyCode', 'googleMapsUrl'));
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
     * Estimate distance between two addresses using Google Maps Distance Matrix API
     */
    private function estimateDistance($address1, $address2)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        // If API key is available, use Google Maps Distance Matrix API
        if ($apiKey) {
            try {
                $distance = $this->getGoogleMapsDistance($address1, $address2, $apiKey);
                if ($distance !== null) {
                    return $distance;
                }
            } catch (\Exception $e) {
                \Log::warning('Google Maps API failed, using fallback', [
                    'error' => $e->getMessage(),
                    'address1' => $address1,
                    'address2' => $address2
                ]);
            }
        }
        
        // Fallback to area-based estimation
        return $this->estimateDistanceByArea($address1, $address2);
    }
    
    /**
     * Get actual distance using Google Maps Distance Matrix API with caching
     */
    private function getGoogleMapsDistance($origin, $destination, $apiKey)
    {
        // Create cache key based on normalized addresses
        $cacheKey = 'gmaps_distance_' . md5(strtolower(trim($origin)) . '|' . strtolower(trim($destination)));
        
        // Check if we have cached result (valid for 7 days)
        $cachedDistance = \Cache::get($cacheKey);
        if ($cachedDistance !== null) {
            return $cachedDistance;
        }
        
        $origin = urlencode($origin);
        $destination = urlencode($destination);
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&key={$apiKey}&mode=driving";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            return null;
        }
        
        if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
            // If Google can't find the address, try with simplified address (city/area only)
            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'NOT_FOUND') {
                $simplifiedOrigin = $this->extractCityArea($origin);
                $simplifiedDestination = $this->extractCityArea($destination);
                
                if ($simplifiedOrigin !== $origin || $simplifiedDestination !== $destination) {
                    // Try again with simplified addresses
                    return $this->getGoogleMapsDistance($simplifiedOrigin, $simplifiedDestination, $apiKey);
                }
            }
            return null;
        }
        
        // Distance is in meters, convert to kilometers
        $distanceInMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distanceInKm = $distanceInMeters / 1000;
        
        // Cache the result for 7 days (604800 seconds)
        \Cache::put($cacheKey, $distanceInKm, 604800);
        
        return $distanceInKm;
    }
    
    /**
     * Extract city/area from full address for fallback when Google can't find address
     * Example: "21b Fatal Idowu Arobieke Lekki Phase 1 Lagos" -> "Lekki Phase 1 Lagos"
     */
    private function extractCityArea($address)
    {
        // Common Lagos areas and landmarks
        $areas = [
            'Lekki Phase 1', 'Lekki Phase 2', 'Lekki', 'Ajah', 'Victoria Island', 'VI', 'Ikoyi',
            'Ikeja', 'Surulere', 'Yaba', 'Gbagada', 'Maryland', 'Ojota', 'Ketu', 'Ikorodu',
            'Festac', 'Apapa', 'Isolo', 'Oshodi', 'Mushin', 'Bariga', 'Somolu', 'Agege',
            'Egbeda', 'Alimosho', 'Ifako', 'Abule Egba', 'Iyana Ipaja', 'Badagry', 'Epe',
            'Magodo', 'Omole', 'Berger', 'Ojodu', 'Ogba', 'Anthony', 'Obanikoro',
            'Palmgrove', 'Onipanu', 'Fadeyi', 'Jibowu', 'Baruwa', 'Igando', 'Isheri',
            'Iju Ishaga', 'Iju', 'Ishaga', 'Ejigbo', 'Idimu', 'Ikotun', 'Akowonjo'
        ];
        
        $addressLower = strtolower($address);
        
        // Try to find area in address
        foreach ($areas as $area) {
            if (stripos($addressLower, strtolower($area)) !== false) {
                // Extract from area onwards, include "Lagos" if present
                $pattern = '/' . preg_quote($area, '/') . '.*?Lagos/i';
                if (preg_match($pattern, $address, $matches)) {
                    return trim($matches[0]);
                }
                // If no "Lagos" found, just return area + Lagos
                return $area . ' Lagos';
            }
        }
        
        // If no specific area found, try to extract last 2-3 words (usually area + city)
        $words = explode(' ', trim($address));
        if (count($words) >= 2) {
            return implode(' ', array_slice($words, -2));
        }
        
        return $address; // Return original if can't simplify
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
