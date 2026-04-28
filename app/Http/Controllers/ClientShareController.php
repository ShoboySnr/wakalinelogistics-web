<?php

namespace App\Http\Controllers;

use App\Models\ClientShare;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\Request;

class ClientShareController extends Controller
{
    public function show($token)
    {
        $clientShare = ClientShare::where('token', $token)
            ->with('client')
            ->firstOrFail();

        if (!$clientShare->isValid()) {
            abort(403, 'This link has expired or been disabled.');
        }

        $client = $clientShare->client;
        
        // Get orders: today's orders + unfulfilled orders from previous days
        $orders = $client->orders()
            ->with('rider')
            ->where(function($query) {
                // Today's orders (all statuses)
                $query->whereDate('created_at', today())
                    // OR unfulfilled orders from previous days
                    ->orWhere(function($q) {
                        $q->whereDate('created_at', '<', today())
                          ->whereNotIn('status', ['delivered', 'cancelled']);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $orders->count(),
            'pending' => $orders->whereIn('status', ['pending', 'confirmed'])->count(),
            'in_transit' => $orders->whereIn('status', ['in_transit', 'picked_up'])->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
        ];

        return view('client-share', compact('client', 'orders', 'stats', 'token'));
    }

    public function getOrders($token)
    {
        $clientShare = ClientShare::where('token', $token)
            ->with('client')
            ->firstOrFail();

        if (!$clientShare->isValid()) {
            return response()->json(['success' => false, 'message' => 'Link expired'], 403);
        }

        $client = $clientShare->client;
        
        // Get orders: today's orders + unfulfilled orders from previous days
        $orders = $client->orders()
            ->with('rider')
            ->where(function($query) {
                // Today's orders (all statuses)
                $query->whereDate('created_at', today())
                    // OR unfulfilled orders from previous days
                    ->orWhere(function($q) {
                        $q->whereDate('created_at', '<', today())
                          ->whereNotIn('status', ['delivered', 'cancelled']);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $orders->count(),
            'pending' => $orders->whereIn('status', ['pending', 'confirmed'])->count(),
            'in_transit' => $orders->whereIn('status', ['in_transit', 'picked_up'])->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
        ];

        $ordersHtml = view('partials.client-orders', compact('orders'))->render();

        return response()->json([
            'success' => true,
            'html' => $ordersHtml,
            'stats' => $stats,
        ]);
    }
}
