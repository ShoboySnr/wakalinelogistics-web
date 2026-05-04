<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientOrderController extends Controller
{
    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_email' => 'nullable|email|max:255',
            'delivery_address' => 'required|string',
            'package_description' => 'required|string',
            'package_quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate unique order number
        $orderNumber = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));

        // Calculate price and distance using Metter API
        $priceData = $this->calculatePrice($request->pickup_address, $request->delivery_address);

        $order = Order::create([
            'order_number' => $orderNumber,
            'client_id' => $user->id,
            'sender_name' => $request->sender_name,
            'sender_phone' => $request->sender_phone,
            'sender_email' => $request->sender_email,
            'pickup_address' => $request->pickup_address,
            'receiver_name' => $request->receiver_name,
            'receiver_phone' => $request->receiver_phone,
            'receiver_email' => $request->receiver_email,
            'delivery_address' => $request->delivery_address,
            'package_description' => $request->package_description,
            'package_size' => 'medium', // Default value
            'package_weight' => '0', // Default value
            'package_quantity' => $request->package_quantity,
            'pickup_date' => now()->addDay()->format('Y-m-d'), // Next day
            'delivery_date' => now()->addDays(2)->format('Y-m-d'), // 2 days from now
            'distance' => $priceData['distance'] ?? 0,
            'price' => $priceData['price'] ?? 0,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    /**
     * Calculate price using Metter API
     */
    private function calculatePrice($pickupAddress, $deliveryAddress)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(env('NEXT_PUBLIC_METTER_BASE_URL') . '/calculate-delivery', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . env('NEXT_PUBLIC_API_TOKEN'),
                ],
                'json' => [
                    'pickup_address' => $pickupAddress,
                    'delivery_address' => $deliveryAddress,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if ($data['success']) {
                return [
                    'price' => $data['data']['price'],
                    'distance' => $data['data']['distance'],
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Price calculation error: ' . $e->getMessage());
        }

        return ['price' => 0, 'distance' => 0];
    }

    /**
     * Get authenticated user's orders
     */
    public function myOrders(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user is a Client (from clients table)
        $isClient = $user instanceof Client;
        
        // Get orders for this user/client
        if ($isClient) {
            // For clients, get orders associated with their client_id
            $orders = Order::where('client_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // For regular users, get orders by email
            $orders = Order::where('sender_email', $user->email)
                ->orWhere('receiver_email', $user->email)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Calculate stats
        $stats = [
            'total' => $orders->count(),
            'pending' => $orders->where('status', 'pending')->count(),
            'confirmed' => $orders->where('status', 'confirmed')->count(),
            'in_transit' => $orders->whereIn('status', ['picked_up', 'in_transit'])->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => [
                'orders' => $orders,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get single order detail
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user is a Client
        $isClient = $user instanceof Client;
        
        $order = Order::where('id', $id)
            ->where(function($query) use ($user, $isClient) {
                if ($isClient) {
                    $query->where('client_id', $user->id);
                } else {
                    $query->where('sender_email', $user->email)
                        ->orWhere('receiver_email', $user->email);
                }
            })
            ->with('rider')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Get today's orders + unfulfilled orders from previous days
     */
    public function todayOrders(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $isClient = $user instanceof Client;

        // Get orders: today's orders + unfulfilled orders from previous days
        $orders = Order::where(function($query) use ($user, $isClient) {
                if ($isClient) {
                    $query->where('client_id', $user->id);
                } else {
                    $query->where('sender_email', $user->email)
                        ->orWhere('receiver_email', $user->email);
                }
            })
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

        // Calculate stats
        $stats = [
            'total' => $orders->count(),
            'pending' => $orders->whereIn('status', ['pending', 'confirmed'])->count(),
            'in_transit' => $orders->whereIn('status', ['in_transit', 'picked_up'])->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Today\'s orders retrieved successfully',
            'data' => [
                'orders' => $orders,
                'stats' => $stats,
            ]
        ], 200);
    }
}
