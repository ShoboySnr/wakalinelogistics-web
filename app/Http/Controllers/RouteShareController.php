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
        $pendingOrders = $rider->orders()
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->latest()
            ->get();
        
        // Build route waypoints
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $waypoints = [];
        $cumulativeTime = 0; // in minutes
        
        foreach ($pendingOrders as $order) {
            // Add pickup location only if not yet picked up
            if ($order->pickup_address && !$order->pickup_date) {
                // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
                
                $waypoints[] = [
                    'address' => $order->pickup_address,
                    'type' => 'pickup',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'sender' => $order->sender_name ?? $order->customer_name,
                    'phone' => $order->sender_phone ?? $order->customer_phone,
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A')
                ];
            }
            
            // Add delivery location
            if ($order->delivery_address) {
                // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
                
                $waypoints[] = [
                    'address' => $order->delivery_address,
                    'type' => 'delivery',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'receiver' => $order->receiver_name ?? 'N/A',
                    'phone' => $order->receiver_phone ?? 'N/A',
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A')
                ];
            }
        }
        
        return view('route-share', compact('rider', 'waypoints', 'startingPoint', 'routeShare', 'dailyCode'));
    }
}
