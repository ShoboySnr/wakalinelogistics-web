<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientCustomerController extends Controller
{
    /**
     * List all unique customers (receivers) derived from the client's order history.
     * Grouped by receiver_phone, enriched with stats.
     * Supports ?search= query param.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $search = trim($request->query('search', ''));

        $query = Order::where('client_id', $user->id)
            ->whereNotNull('receiver_phone')
            ->where('receiver_phone', '!=', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('receiver_name',  'LIKE', "%{$search}%")
                  ->orWhere('receiver_phone', 'LIKE', "%{$search}%")
                  ->orWhere('receiver_email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query
            ->select(
                'receiver_phone',
                DB::raw('MAX(receiver_name)     AS receiver_name'),
                DB::raw('MAX(receiver_email)    AS receiver_email'),
                DB::raw('MAX(delivery_address)  AS delivery_address'),
                DB::raw('COUNT(*)               AS total_orders'),
                DB::raw('SUM(price)             AS total_spend'),
                DB::raw('MAX(created_at)        AS last_order_at')
            )
            ->groupBy('receiver_phone')
            ->orderByDesc('last_order_at')
            ->get()
            ->map(function ($c) {
                return [
                    'receiver_phone'    => $c->receiver_phone,
                    'receiver_name'     => $c->receiver_name,
                    'receiver_email'    => $c->receiver_email,
                    'delivery_address'  => $c->delivery_address,
                    'total_orders'      => (int) $c->total_orders,
                    'total_spend'       => (float) $c->total_spend,
                    'last_order_at'     => $c->last_order_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $customers,
            'meta'    => ['total' => $customers->count()],
        ]);
    }

    /**
     * Get a single customer's profile and full order history.
     * {phone} is URL-encoded so + characters survive routing.
     */
    public function show(string $phone)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Decode in case the phone contains + or spaces
        $phone = urldecode($phone);

        $orders = Order::where('client_id', $user->id)
            ->where('receiver_phone', $phone)
            ->orderByDesc('created_at')
            ->get([
                'id', 'order_number', 'status', 'pickup_address',
                'delivery_address', 'package_description',
                'receiver_name', 'receiver_email', 'receiver_phone',
                'price', 'payment_method', 'created_at',
            ]);

        if ($orders->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        // Build profile from the most recent order
        $latest = $orders->first();

        $profile = [
            'receiver_phone'   => $phone,
            'receiver_name'    => $latest->receiver_name ?? $orders->whereNotNull('receiver_name')->first()?->receiver_name,
            'receiver_email'   => $latest->receiver_email ?? $orders->whereNotNull('receiver_email')->first()?->receiver_email,
            'delivery_address' => $latest->delivery_address,
            'total_orders'     => $orders->count(),
            'total_spend'      => (float) $orders->sum('price'),
            'last_order_at'    => $latest->created_at,
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'profile' => $profile,
                'orders'  => $orders,
            ],
        ]);
    }
}
