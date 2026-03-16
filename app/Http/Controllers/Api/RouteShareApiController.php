<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\RouteShare;
use Illuminate\Http\Request;

class RouteShareApiController extends Controller
{
    public function getRouteData($token)
    {
        $routeShare = RouteShare::where('token', $token)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            return response()->json(['error' => 'Invalid or expired route link'], 404);
        }
        
        // Increment view count
        $routeShare->incrementViewCount();
        
        // Load rider with pending orders
        $rider = $routeShare->rider;
        $pendingOrders = $rider->orders()
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->latest()
            ->get();
        
        // Build route waypoints
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $waypoints = [];
        $cumulativeTime = 0;
        
        foreach ($pendingOrders as $order) {
            // Add pickup location only if not yet picked up
            if ($order->pickup_address && !$order->pickup_date) {
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5;
                
                $waypoints[] = [
                    'address' => $order->pickup_address,
                    'type' => 'pickup',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'sender' => $order->sender_name ?? $order->customer_name,
                    'phone' => $order->sender_phone ?? $order->customer_phone,
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A'),
                    'status' => $order->status,
                    'pickup_date' => $order->pickup_date
                ];
            }
            
            // Add delivery location
            if ($order->delivery_address) {
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5;
                
                $waypoints[] = [
                    'address' => $order->delivery_address,
                    'type' => 'delivery',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'receiver' => $order->receiver_name ?? 'N/A',
                    'phone' => $order->receiver_phone ?? 'N/A',
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A'),
                    'status' => $order->status,
                    'delivery_date' => $order->delivery_date
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'rider' => [
                'id' => $rider->id,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'vehicle_type' => $rider->vehicle_type,
                'vehicle_number' => $rider->vehicle_number,
                'status' => $rider->status,
                'current_latitude' => $rider->current_latitude ?? null,
                'current_longitude' => $rider->current_longitude ?? null,
                'last_location_update' => $rider->last_location_update ?? null
            ],
            'waypoints' => $waypoints,
            'starting_point' => $startingPoint,
            'total_stops' => count($waypoints),
            'total_estimated_time' => $cumulativeTime,
            'last_updated' => now()->toIso8601String()
        ]);
    }
    
    public function updateRiderLocation(Request $request, $token)
    {
        $routeShare = RouteShare::where('token', $token)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            return response()->json(['error' => 'Invalid or expired route link'], 404);
        }
        
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);
        
        $rider = $routeShare->rider;
        $rider->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'last_location_update' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'latitude' => $rider->current_latitude,
            'longitude' => $rider->current_longitude,
            'updated_at' => $rider->last_location_update
        ]);
    }
    
    public function updateOrderStatus(Request $request, $token, $orderId)
    {
        $routeShare = RouteShare::where('token', $token)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            return response()->json(['error' => 'Invalid or expired route link'], 404);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);
        
        $rider = $routeShare->rider;
        $order = $rider->orders()->findOrFail($orderId);
        
        $order->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'pickup_date' => $order->pickup_date,
                'delivery_date' => $order->delivery_date
            ]
        ]);
    }

    public function validateDailyCode(Request $request, $token)
    {
        $routeShare = RouteShare::where('token', $token)->firstOrFail();
        
        if (!$routeShare->isValid()) {
            return response()->json(['error' => 'Invalid or expired route link'], 404);
        }
        
        $request->validate([
            'code' => 'required|string|size:6'
        ]);
        
        $rider = $routeShare->rider;
        $isValid = $rider->validateDailyCode($request->code);
        
        if ($isValid) {
            return response()->json([
                'success' => true,
                'message' => 'Code validated successfully',
                'rider_name' => $rider->name
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Invalid code. Please check and try again.'
        ], 401);
    }
}
