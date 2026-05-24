<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientCustomer;
use App\Modules\Admin\Models\ClientCustomerMeta;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientCustomerController extends Controller
{
    // ── Default customer settings shape ──────────────────────────────────────

    private function defaultSettings(): array
    {
        return [
            'default_payment_method'      => 'credits',
            'default_package_description' => '',
            'default_package_quantity'    => 1,
            'auto_add_to_address_book'    => false,
            'default_sort'                => 'last_order_at',
            'notify_email_delivered'      => true,
            'notify_email_picked_up'      => false,
            'notify_whatsapp_confirm'     => false,
            'notify_whatsapp_delivered'   => false,
        ];
    }

    // ── List customers ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $search = trim($request->query('search', ''));

        $query = ClientCustomer::where('client_id', $user->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('receiver_name',  'LIKE', "%{$search}%")
                  ->orWhere('receiver_phone', 'LIKE', "%{$search}%")
                  ->orWhere('receiver_email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('last_order_at')->get();

        // Load all meta for this client in one query
        $metaMap = ClientCustomerMeta::where('client_id', $user->id)
            ->get()
            ->keyBy('receiver_phone');

        $result = $customers->map(function ($c) use ($metaMap) {
            $meta = $metaMap->get($c->receiver_phone);
            
            // Calculate customer segment
            $segment = $this->calculateCustomerSegment($c);
            
            // Calculate days since last order
            $daysSinceLastOrder = $c->last_order_at 
                ? now()->diffInDays($c->last_order_at) 
                : null;
            
            return [
                'receiver_phone'   => $c->receiver_phone,
                'receiver_name'    => $c->receiver_name,
                'receiver_email'   => $c->receiver_email,
                'delivery_address' => $c->delivery_address,
                'total_orders'     => $c->total_orders,
                'total_spend'      => $c->total_spend,
                'last_order_at'    => $c->last_order_at,
                'segment'          => $segment,
                'days_since_last_order' => $daysSinceLastOrder,
                'is_at_risk'       => $daysSinceLastOrder && $daysSinceLastOrder > 30,
                'meta' => [
                    'starred'   => $meta?->starred ?? false,
                    'tags'      => $meta?->tags ?? [],
                    'notes'     => $meta?->notes ?? '',
                    'addresses' => $meta?->addresses ?? [],
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $result,
            'meta'    => ['total' => $result->count()],
        ]);
    }

    // ── Single customer detail ────────────────────────────────────────────────

    public function show(string $phone)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $phone = urldecode($phone);

        $customer = ClientCustomer::where('client_id', $user->id)
            ->where('receiver_phone', $phone)
            ->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $orders = Order::where('client_id', $user->id)
            ->where('receiver_phone', $phone)
            ->orderByDesc('created_at')
            ->get([
                'id', 'order_number', 'status', 'pickup_address',
                'delivery_address', 'package_description',
                'receiver_name', 'receiver_phone',
                'price', 'payment_method', 'created_at', 'updated_at',
            ]);

        // Calculate analytics
        $analytics = $this->calculateCustomerAnalytics($user, $orders, $customer);

        $profile = [
            'receiver_phone'   => $customer->receiver_phone,
            'receiver_name'    => $customer->receiver_name,
            'receiver_email'   => $customer->receiver_email,
            'delivery_address' => $customer->delivery_address,
            'total_orders'     => $customer->total_orders,
            'total_spend'      => $customer->total_spend,
            'last_order_at'    => $customer->last_order_at,
            'meta'             => ClientCustomerMeta::getFor($user->id, $phone),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'profile'   => $profile,
                'orders'    => $orders,
                'analytics' => $analytics,
            ],
        ]);
    }

    // ── Update customer meta (starred / tags / notes / addresses) ─────────────

    public function updateMeta(Request $request, string $phone)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $phone = urldecode($phone);

        // Ensure the customer actually belongs to this client
        $exists = ClientCustomer::where('client_id', $user->id)
            ->where('receiver_phone', $phone)
            ->exists();

        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'starred'            => 'sometimes|boolean',
            'tags'               => 'sometimes|array',
            'tags.*'             => 'string|max:50',
            'notes'              => 'sometimes|nullable|string|max:1000',
            'addresses'          => 'sometimes|array',
            'addresses.*.label'  => 'required_with:addresses|string|max:100',
            'addresses.*.address'=> 'required_with:addresses|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $meta = ClientCustomerMeta::upsertFor($user->id, $phone, $request->only([
            'starred', 'tags', 'notes', 'addresses',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Customer updated',
            'data'    => [
                'starred'   => $meta->starred,
                'tags'      => $meta->tags ?? [],
                'notes'     => $meta->notes ?? '',
                'addresses' => $meta->addresses ?? [],
            ],
        ]);
    }

    // ── Customer management settings ──────────────────────────────────────────

    public function getSettings()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $saved    = is_array($user->customer_settings) ? $user->customer_settings : [];
        $settings = array_merge($this->defaultSettings(), $saved);

        // Also include pickup defaults from the client profile
        $settings['default_pickup_address']       = $user->default_pickup_address ?? '';
        $settings['default_pickup_contact_name']  = $user->default_pickup_contact_name ?? '';
        $settings['default_pickup_contact_phone'] = $user->default_pickup_contact_phone ?? '';

        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            // Pickup profile fields (stored on clients table)
            'default_pickup_address'       => 'sometimes|nullable|string|max:500',
            'default_pickup_contact_name'  => 'sometimes|nullable|string|max:255',
            'default_pickup_contact_phone' => 'sometimes|nullable|string|max:20',

            // Customer settings (stored in customer_settings JSON)
            'default_payment_method'      => 'sometimes|in:credits,cash,card',
            'default_package_description' => 'sometimes|nullable|string|max:500',
            'default_package_quantity'    => 'sometimes|integer|min:1|max:999',
            'auto_add_to_address_book'    => 'sometimes|boolean',
            'default_sort'                => 'sometimes|in:last_order_at,total_orders,total_spend',
            'notify_email_delivered'      => 'sometimes|boolean',
            'notify_email_picked_up'      => 'sometimes|boolean',
            'notify_whatsapp_confirm'     => 'sometimes|boolean',
            'notify_whatsapp_delivered'   => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Split: profile fields go to clients table
        $profileKeys = ['default_pickup_address', 'default_pickup_contact_name', 'default_pickup_contact_phone'];
        $profileData = array_filter($request->only($profileKeys), fn($v) => $v !== null);

        // Settings keys go to customer_settings JSON
        $settingsKeys = array_keys($this->defaultSettings());
        $incoming     = $request->only($settingsKeys);

        $existing = is_array($user->customer_settings) ? $user->customer_settings : [];
        $merged   = array_merge($existing, $incoming);

        $updates = array_merge($profileData, ['customer_settings' => $merged]);
        $user->update($updates);

        // Return the full merged settings (same shape as getSettings)
        $full = array_merge($this->defaultSettings(), $merged);
        $full['default_pickup_address']       = $user->fresh()->default_pickup_address ?? '';
        $full['default_pickup_contact_name']  = $user->fresh()->default_pickup_contact_name ?? '';
        $full['default_pickup_contact_phone'] = $user->fresh()->default_pickup_contact_phone ?? '';

        return response()->json(['success' => true, 'message' => 'Settings saved', 'data' => $full]);
    }

    // ── Calculate Customer Segment ────────────────────────────────────────────

    private function calculateCustomerSegment($customer): string
    {
        $totalOrders = $customer->total_orders;
        $totalSpend = $customer->total_spend;
        $daysSinceLastOrder = $customer->last_order_at 
            ? now()->diffInDays($customer->last_order_at) 
            : 999;

        // VIP: High spend OR many orders
        if ($totalSpend >= 50000 || $totalOrders >= 20) {
            return 'VIP';
        }

        // At Risk: Haven't ordered in 30+ days but has history
        if ($daysSinceLastOrder > 30 && $totalOrders > 0) {
            return 'At Risk';
        }

        // New: Less than 3 orders
        if ($totalOrders <= 2) {
            return 'New';
        }

        // Regular: Everything else
        return 'Regular';
    }

    // ── Calculate Customer Analytics ──────────────────────────────────────────

    private function calculateCustomerAnalytics($user, $orders, $customer): array
    {
        if ($orders->isEmpty()) {
            return [
                'order_patterns' => null,
                'revenue_contribution' => 0,
                'top_delivery_locations' => [],
                'average_delivery_time' => null,
                'order_frequency' => null,
            ];
        }

        // Get total business revenue for percentage calculation
        $totalBusinessRevenue = Order::where('client_id', $user->id)->sum('price');
        $revenueContribution = $totalBusinessRevenue > 0 
            ? round(($customer->total_spend / $totalBusinessRevenue) * 100, 2) 
            : 0;

        // Analyze order patterns (day of week)
        $dayOfWeekCounts = [];
        foreach ($orders as $order) {
            $dayName = \Carbon\Carbon::parse($order->created_at)->format('l');
            $dayOfWeekCounts[$dayName] = ($dayOfWeekCounts[$dayName] ?? 0) + 1;
        }
        arsort($dayOfWeekCounts);
        $mostCommonDay = !empty($dayOfWeekCounts) ? array_key_first($dayOfWeekCounts) : null;

        // Top delivery locations
        $locationCounts = [];
        foreach ($orders as $order) {
            $location = $order->delivery_address;
            $locationCounts[$location] = ($locationCounts[$location] ?? 0) + 1;
        }
        arsort($locationCounts);
        $topLocations = array_slice($locationCounts, 0, 3, true);

        // Average delivery time (for delivered orders)
        $deliveredOrders = $orders->where('status', 'delivered');
        $avgDeliveryTime = null;
        if ($deliveredOrders->count() > 0) {
            $totalHours = 0;
            $count = 0;
            foreach ($deliveredOrders as $order) {
                if ($order->updated_at && $order->created_at) {
                    $hours = \Carbon\Carbon::parse($order->created_at)
                        ->diffInHours(\Carbon\Carbon::parse($order->updated_at));
                    $totalHours += $hours;
                    $count++;
                }
            }
            $avgDeliveryTime = $count > 0 ? round($totalHours / $count, 1) : null;
        }

        // Order frequency (days between orders)
        $orderFrequency = null;
        if ($orders->count() > 1) {
            $dates = $orders->pluck('created_at')->map(fn($d) => \Carbon\Carbon::parse($d))->sortBy(fn($d) => $d);
            $intervals = [];
            $prevDate = null;
            foreach ($dates as $date) {
                if ($prevDate) {
                    $intervals[] = $prevDate->diffInDays($date);
                }
                $prevDate = $date;
            }
            $orderFrequency = !empty($intervals) ? round(array_sum($intervals) / count($intervals), 1) : null;
        }

        return [
            'order_patterns' => $mostCommonDay ? "Usually orders on {$mostCommonDay}s" : null,
            'revenue_contribution' => $revenueContribution,
            'top_delivery_locations' => array_map(fn($addr, $count) => [
                'address' => $addr,
                'count' => $count,
            ], array_keys($topLocations), array_values($topLocations)),
            'average_delivery_time' => $avgDeliveryTime,
            'order_frequency' => $orderFrequency,
        ];
    }
}
