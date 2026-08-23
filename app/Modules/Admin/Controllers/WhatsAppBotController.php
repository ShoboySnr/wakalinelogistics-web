<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\JobApplication;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WhatsAppBotController extends Controller
{
    private DeliveryPriceService $pricer;

    public function __construct(DeliveryPriceService $pricer)
    {
        $this->pricer = $pricer;
    }

    public function listOrders(Request $request): JsonResponse
    {
        $request->validate([
            'status'       => 'nullable|in:pending,confirmed,in_transit,delivered,cancelled',
            'client_id'    => 'nullable|integer|exists:clients,id',
            'client_phone' => 'nullable|string|max:20',
            'sender_phone' => 'nullable|string|max:20',
            'rider_id'     => 'nullable|integer|exists:riders,id',
            'date'         => 'nullable|in:today,yesterday,week,month',
            'search'       => 'nullable|string|max:100',
            'limit'        => 'nullable|integer|min:1|max:50',
            'page'         => 'nullable|integer|min:1',
        ]);

        $query = Order::with('rider:id,name,phone', 'client:id,name,company_name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->integer('rider_id'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        } elseif ($request->filled('client_phone')) {
            $client = $this->resolveClient($request->client_phone, null);
            if ($client) {
                $query->where('client_id', $client->id);
            }
        }

        if ($request->filled('sender_phone')) {
            $sp         = $request->sender_phone;
            $normalised = $this->normalisePhone($sp);
            $query->where(function ($q) use ($sp, $normalised) {
                $q->where('sender_phone', $sp)
                  ->orWhere('sender_phone', $normalised)
                  ->orWhere('customer_phone', $sp)
                  ->orWhere('customer_phone', $normalised);
            });
        }

        if ($request->filled('date')) {
            match ($request->date) {
                'today'     => $query->whereDate('created_at', today()),
                'yesterday' => $query->whereDate('created_at', today()->subDay()),
                'week'      => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month'     => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            };
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhere('receiver_name', 'like', "%{$s}%")
                  ->orWhere('delivery_address', 'like', "%{$s}%");
                if (is_numeric($s)) {
                    $q->orWhere('id', (int) $s);
                }
            });
        }

        $limit  = $request->integer('limit', 10);
        $page   = max(1, $request->integer('page', 1));
        $total  = $query->count();
        $orders = $query->latest()->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'success'    => true,
            'count'      => $orders->count(),
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $limit,
            'total_pages'=> (int) ceil($total / $limit),
            'orders'     => $orders->map(fn ($o) => $this->orderSummary($o)),
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $request->validate([
            'client_phone'     => 'nullable|string|max:20',
            'client_id'        => 'nullable|integer|exists:clients,id',
            'pickup_address'   => 'nullable|string',
            'delivery_address' => 'required|string',
        ]);

        $client        = $this->resolveClient($request->client_phone, $request->client_id);
        $pickupAddress = $request->pickup_address ?? $client?->pickup_address;

        if (!$pickupAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup address is required when the client is not registered.',
                'error'   => 'PICKUP_ADDRESS_REQUIRED',
            ], 422);
        }

        $calc = $this->pricer->processDeliveryCalculation($pickupAddress, $request->delivery_address);

        if (isset($calc['error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not calculate delivery price: ' . $calc['error'],
                'error'   => 'PRICE_CALCULATION_FAILED',
            ], 422);
        }

        return response()->json([
            'success'          => true,
            'client'           => $client ? [
                'id'             => $client->id,
                'name'           => $client->name,
                'company_name'   => $client->company_name,
                'phone'          => $client->phone,
                'pickup_address' => $client->pickup_address,
            ] : null,
            'pickup_address'   => $calc['pickup'],
            'delivery_address' => $calc['delivery'],
            'distance_km'      => $calc['distance_km'],
            'pickup_zone'      => $calc['pickup_zone'],
            'delivery_zone'    => $calc['delivery_zone'],
            'delivery_fee'     => $calc['delivery_fee'],
            'price_breakdown'  => $calc['price_breakdown'],
            'currency'         => 'NGN',
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'client_phone'     => 'nullable|string|max:20',
            'client_id'        => 'nullable|integer|exists:clients,id',
            'sender_name'      => 'nullable|string|max:255',
            'sender_phone'     => 'nullable|string|max:20',
            'sender_email'     => 'nullable|email|max:255',
            'pickup_address'   => 'nullable|string',
            'delivery_address' => 'required|string',
            'receiver_name'    => 'required|string|max:255',
            'receiver_phone'   => 'required|string|max:20',
            'item_description' => 'required|string',
            'price'            => 'nullable|numeric|min:0',
            'amount_received'  => 'nullable|numeric|min:0',
            'payment_method'   => 'nullable|in:cash,cod,credits,card,subscription',
            'notes'            => 'nullable|string',
            'source_contact'   => 'nullable|string|max:255',
            'priority_level'   => 'nullable|in:normal,high,urgent',
            'force'            => 'nullable|boolean',
        ]);

        $client        = $this->resolveClient($request->client_phone, $request->client_id);
        $senderName    = $client?->name    ?? $request->sender_name;
        $senderPhone   = $client?->phone   ?? $request->sender_phone;
        $senderEmail   = $client?->email   ?? $request->sender_email;
        $pickupAddress = $request->pickup_address ?? $client?->pickup_address;

        if (!$senderName || !$senderPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Could not resolve sender. Provide client_phone, client_id, or sender_name + sender_phone.',
                'error'   => 'SENDER_REQUIRED',
            ], 422);
        }

        if (!$pickupAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup address is required when the client has none on record.',
                'error'   => 'PICKUP_ADDRESS_REQUIRED',
            ], 422);
        }

        if (!$request->boolean('force')) {
            $dupQuery = Order::where('created_at', '>=', now()->subHours(24))
                ->whereNotIn('status', ['cancelled', 'delivered'])
                ->where('delivery_address', $request->delivery_address)
                ->where('receiver_phone', $request->receiver_phone)
                ->where('item_description', $request->item_description);

            if ($client) {
                $dupQuery->where('client_id', $client->id);
            } elseif ($senderPhone) {
                $dupQuery->where('sender_phone', $senderPhone);
            }

            $duplicate = $dupQuery->latest()->first(['id', 'order_number', 'status', 'created_at', 'delivery_address', 'receiver_name', 'receiver_phone', 'item_description']);

            if ($duplicate) {
                return response()->json([
                    'success'        => false,
                    'message'        => "A similar order was created recently (#{$duplicate->order_number}). Pass force=true to create anyway.",
                    'error'          => 'POSSIBLE_DUPLICATE',
                    'existing_order' => [
                        'order_number'     => $duplicate->order_number,
                        'status'           => $duplicate->status,
                        'created_at'       => $duplicate->created_at->toISOString(),
                        'delivery_address' => $duplicate->delivery_address,
                        'receiver_name'    => $duplicate->receiver_name,
                        'receiver_phone'   => $duplicate->receiver_phone,
                        'item_description' => $duplicate->item_description,
                    ],
                ], 409);
            }
        }

        $price    = null;
        $distance = null;
        $calcData = null;

        if ($request->filled('price')) {
            $price = (float) $request->price;
        } else {
            $calc = $this->pricer->processDeliveryCalculation($pickupAddress, $request->delivery_address);
            if (isset($calc['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not calculate delivery price: ' . $calc['error'],
                    'error'   => 'PRICE_CALCULATION_FAILED',
                ], 422);
            }
            $price    = (float) $calc['delivery_fee'];
            $distance = $calc['distance_km'];
            $calcData = $calc;
        }

        $user = null;
        if ($senderPhone) {
            $normalised = $this->normalisePhone($senderPhone);
            $user = User::where('phone', $senderPhone)
                        ->orWhere('phone', $normalised)
                        ->first();
        }
        if (!$user && $senderEmail) {
            $user = User::where('email', $senderEmail)->first();
        }
        if (!$user && $senderEmail) {
            $user = User::create([
                'name'     => $senderName,
                'email'    => $senderEmail,
                'phone'    => $this->normalisePhone($senderPhone ?? ''),
                'password' => bcrypt(Str::random(20)),
                'provider' => 'whatsapp_bot',
            ]);
        }

        $order = Order::create([
            'user_id'          => $user?->id,
            'client_id'        => $client?->id,
            'source'           => 'whatsapp',
            'source_contact'   => $request->source_contact ?? $request->client_phone ?? $senderPhone,
            'sender_name'      => $senderName,
            'sender_phone'     => $senderPhone,
            'sender_email'     => $senderEmail,
            'customer_name'    => $senderName,
            'customer_phone'   => $senderPhone,
            'customer_email'   => $senderEmail,
            'pickup_address'   => $pickupAddress,
            'receiver_name'    => $request->receiver_name,
            'receiver_phone'   => $request->receiver_phone,
            'delivery_address' => $request->delivery_address,
            'item_description' => $request->item_description,
            'price'            => $price,
            'amount_received'  => $request->filled('amount_received') ? (float) $request->amount_received : null,
            'distance'         => $distance,
            'payment_method'   => $request->payment_method ?? 'cash',
            'notes'            => $request->notes,
            'status'           => 'pending',
            'priority_level'   => $request->priority_level ?? 'normal',
        ]);

        ActivityLog::log('order_created', "WhatsApp bot created order #{$order->order_number}", $order, [
            'order_number' => $order->order_number,
            'source'       => 'whatsapp_bot',
            'client_id'    => $client?->id,
        ]);

        $response = [
            'success'          => true,
            'message'          => 'Order created successfully.',
            'order_number'     => $order->order_number,
            'order_id'         => $order->id,
            'status'           => $order->status,
            'price'            => $price,
            'currency'         => 'NGN',
            'client'           => $client ? ['id' => $client->id, 'name' => $client->name, 'company_name' => $client->company_name] : null,
            'pickup_address'   => $pickupAddress,
            'delivery_address' => $order->delivery_address,
        ];

        if ($calcData) {
            $response['distance_km']     = $calcData['distance_km'];
            $response['pickup_zone']     = $calcData['pickup_zone'];
            $response['delivery_zone']   = $calcData['delivery_zone'];
            $response['price_breakdown'] = $calcData['price_breakdown'];
        }

        return response()->json($response, 201);
    }

    public function getOrder(string $orderNumber): JsonResponse
    {
        $order = $this->resolveOrder($orderNumber);
        if ($order) $order->load('rider:id,name,phone', 'client:id,name,company_name,phone');

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        return response()->json(['success' => true, 'order' => $this->orderDetail($order)]);
    }

    public function updateOrder(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'receiver_name'    => 'nullable|string|max:255',
            'receiver_phone'   => 'nullable|string|max:20',
            'customer_name'    => 'nullable|string|max:255',
            'customer_phone'   => 'nullable|string|max:20',
            'sender_name'      => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string',
            'pickup_address'   => 'nullable|string',
            'item_description' => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'amount_received'  => 'nullable|numeric|min:0',
            'payment_method'   => 'nullable|in:cash,cod,credits,card,subscription',
            'notes'            => 'nullable|string',
            'priority_level'   => 'nullable|in:normal,high,urgent',
            'recalculate_price' => 'nullable|boolean',
        ]);

        $order = $this->resolveOrder($orderNumber);

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        $fields = [
            'receiver_name', 'receiver_phone', 'customer_name', 'customer_phone',
            'sender_name', 'delivery_address', 'pickup_address', 'item_description',
            'price', 'amount_received', 'payment_method', 'notes', 'priority_level',
        ];

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $order->$field = $request->$field;
            }
        }

        if ($request->boolean('recalculate_price')) {
            $calc = $this->pricer->processDeliveryCalculation($order->pickup_address, $order->delivery_address);
            if (!isset($calc['error'])) {
                $order->price    = $calc['delivery_fee'];
                $order->distance = $calc['distance_km'];
            }
        }

        $order->save();

        ActivityLog::log('order_updated', "WhatsApp bot updated order #{$order->order_number}", $order, ['source' => 'whatsapp_bot']);

        return response()->json(['success' => true, 'message' => 'Order updated.', 'order' => $this->orderDetail($order->fresh(['rider', 'client']))]);
    }

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'status'          => 'required_without:failed_delivery|nullable|in:confirmed,in_transit,delivered,cancelled',
            'failed_delivery' => 'nullable|boolean',
            'notes'           => 'nullable|string',
        ]);

        $order = $this->resolveOrder($orderNumber);

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        $oldStatus = $order->status;
        $changes   = [];

        if ($request->boolean('failed_delivery')) {
            $order->is_failed_delivery = true;
            $changes[] = 'marked as failed delivery';
        }

        if ($request->filled('status')) {
            $order->status = $request->status;

            if ($request->status === 'in_transit' && !$order->pickup_date) {
                $order->pickup_date = now();
                $changes[] = 'pickup date recorded';
            }

            if ($request->status === 'delivered' && !$order->delivery_date) {
                $order->delivery_date = now();
                $changes[] = 'delivery date recorded';
            }
        }

        if ($request->filled('notes')) {
            $order->notes = $order->notes
                ? $order->notes . "\n[Bot] " . $request->notes
                : '[Bot] ' . $request->notes;
        }

        $order->save();

        ActivityLog::log('order_status_updated', "WhatsApp bot updated order #{$order->order_number} status: {$oldStatus} → {$order->status}", $order, [
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'is_failed'  => $order->is_failed_delivery,
            'source'     => 'whatsapp_bot',
        ]);

        return response()->json([
            'success'            => true,
            'order_number'       => $order->order_number,
            'previous_status'    => $oldStatus,
            'status'             => $order->status,
            'is_failed_delivery' => $order->is_failed_delivery,
            'pickup_date'        => $order->pickup_date?->toISOString(),
            'delivery_date'      => $order->delivery_date?->toISOString(),
            'changes'            => $changes,
        ]);
    }

    public function assignRider(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'rider_id' => 'nullable|integer|exists:riders,id',
        ]);

        $order = $this->resolveOrder($orderNumber);

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        $oldRider     = $order->rider_id ? Rider::find($order->rider_id) : null;
        $newRider     = $request->filled('rider_id') ? Rider::find($request->rider_id) : null;
        $order->rider_id = $newRider?->id;
        $order->save();

        ActivityLog::log('order_rider_assigned', "WhatsApp bot assigned order #{$order->order_number} to rider: " . ($newRider?->name ?? 'None'), $order, [
            'old_rider' => $oldRider?->name ?? 'None',
            'new_rider' => $newRider?->name ?? 'None',
            'source'    => 'whatsapp_bot',
        ]);

        if ($newRider && $order->client_id) {
            try {
                $client = Client::find($order->client_id);
                if ($client?->email) {
                    Mail::send('emails.rider_assigned', ['order' => $order, 'rider' => $newRider, 'clientName' => $client->name],
                        fn ($m) => $m->to($client->email)->subject("Rider assigned to Order #{$order->order_number} — Waka Line Logistics")
                    );
                }
            } catch (\Exception $e) {
                Log::error('Bot rider-assigned email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'rider'        => $newRider ? ['id' => $newRider->id, 'name' => $newRider->name, 'phone' => $newRider->phone] : null,
            'message'      => $newRider ? "Rider {$newRider->name} assigned to order #{$order->order_number}." : "Rider unassigned from order #{$order->order_number}.",
        ]);
    }

    public function remitSummary(Request $request): JsonResponse
    {
        $request->validate([
            'client_phone' => 'nullable|string|max:20',
            'client_id'    => 'nullable|integer|exists:clients,id',
        ]);

        $client = $this->resolveClient($request->client_phone, $request->client_id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found.', 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        $orders = Order::where('client_id', $client->id)
            ->where('payment_method', 'cash')
            ->whereNull('remitted_at')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', 'delivered')->whereNotNull('amount_received');
                })->orWhere('is_failed_delivery', true);
            })
            ->latest()
            ->get(['id', 'order_number', 'delivery_address', 'price', 'amount_received', 'delivery_date', 'is_failed_delivery', 'rider_id']);

        $rows = $orders->map(fn ($o) => [
            'order_number'     => $o->order_number,
            'delivery_address' => $o->delivery_address,
            'date'             => $o->delivery_date?->format('M d, Y') ?? '—',
            'amount_received'  => $o->is_failed_delivery ? 0.0 : (float) $o->amount_received,
            'delivery_fee'     => (float) $o->price,
            'to_remit'         => $o->is_failed_delivery ? -(float) $o->price : max(0.0, (float) $o->amount_received - (float) $o->price),
            'is_failed'        => (bool) $o->is_failed_delivery,
        ]);

        return response()->json([
            'success'           => true,
            'client_name'       => $client->name . ($client->company_name ? ' (' . $client->company_name . ')' : ''),
            'client_id'         => $client->id,
            'orders'            => $rows,
            'total_to_remit'    => $rows->sum('to_remit'),
            'count'             => $rows->count(),
        ]);
    }

    public function bulkRemit(Request $request): JsonResponse
    {
        $request->validate([
            'client_phone' => 'nullable|string|max:20',
            'client_id'    => 'nullable|integer|exists:clients,id',
        ]);

        $client = $this->resolveClient($request->client_phone, $request->client_id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found.', 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        $updated = Order::where('client_id', $client->id)
            ->where('payment_method', 'cash')
            ->whereNull('remitted_at')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', 'delivered')->whereNotNull('amount_received');
                })->orWhere('is_failed_delivery', true);
            })
            ->get(['id', 'order_number', 'price', 'amount_received', 'is_failed_delivery']);

        $totalToRemit = $updated->sum(fn ($o) => $o->is_failed_delivery
            ? -(float) $o->price
            : max(0.0, (float) $o->amount_received - (float) $o->price)
        );

        $count = Order::whereIn('id', $updated->pluck('id'))
            ->update(['remitted_at' => now()]);

        ActivityLog::log(
            'orders_bulk_remitted',
            "WhatsApp bot bulk-remitted {$count} orders for client: {$client->name}",
            $client,
            ['source' => 'whatsapp_bot', 'client_id' => $client->id, 'count' => $count]
        );

        return response()->json([
            'success'        => true,
            'client_name'    => $client->name . ($client->company_name ? ' (' . $client->company_name . ')' : ''),
            'count'          => $count,
            'total_remitted' => $totalToRemit,
            'message'        => "{$count} order(s) marked as remitted for {$client->name}.",
        ]);
    }

    public function remitOrder(string $orderNumber): JsonResponse
    {
        $order = $this->resolveOrder($orderNumber);
        if ($order) $order->load('rider:id,name,phone');

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        $wasRemitted = $order->remitted_at !== null;

        if ($wasRemitted) {
            $order->remitted_at = null;
        } else {
            $order->remitted_at = now();
        }

        $order->save();

        $toRemit = null;
        if ($order->amount_received !== null) {
            $toRemit = (float) $order->amount_received - (float) $order->price;
        } elseif ($order->is_failed_delivery) {
            $toRemit = -(float) $order->price;
        }

        $action = $wasRemitted ? 'unremitted' : 'remitted';

        ActivityLog::log(
            'order_remitted',
            "WhatsApp bot {$action} order #{$order->order_number}",
            $order,
            ['source' => 'whatsapp_bot', 'action' => $action]
        );

        return response()->json([
            'success'         => true,
            'order_number'    => $order->order_number,
            'remitted'        => $order->remitted_at !== null,
            'remitted_at'     => $order->remitted_at?->toISOString(),
            'to_remit'        => $toRemit,
            'price'           => (float) $order->price,
            'amount_received' => $order->amount_received !== null ? (float) $order->amount_received : null,
            'payment_method'  => $order->payment_method,
            'is_failed'       => $order->is_failed_delivery,
            'rider'           => $order->rider ? ['id' => $order->rider->id, 'name' => $order->rider->name, 'phone' => $order->rider->phone] : null,
            'message'         => $wasRemitted
                ? "Order #{$orderNumber} marked as NOT remitted."
                : "Order #{$orderNumber} marked as remitted.",
        ]);
    }

    public function deleteOrder(string $orderNumber): JsonResponse
    {
        $order = $this->resolveOrder($orderNumber);

        if (!$order) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        ActivityLog::log('order_deleted', "WhatsApp bot deleted order #{$order->order_number}", null, [
            'order_number' => $order->order_number,
            'source'       => 'whatsapp_bot',
        ]);

        $order->delete();

        return response()->json(['success' => true, 'message' => "Order #{$orderNumber} deleted successfully."]);
    }

    public function listRiders(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:active,inactive,suspended',
            'search' => 'nullable|string|max:100',
        ]);

        $riders = Rider::when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($inner) use ($s) {
                    $inner->where('name', 'like', "%{$s}%")
                          ->orWhere('phone', 'like', "%{$s}%")
                          ->orWhere('vehicle_type', 'like', "%{$s}%")
                          ->orWhere('email', 'like', "%{$s}%");
                    if (is_numeric($s)) {
                        $inner->orWhere('id', (int) $s);
                    }
                });
            })
            ->withCount(['orders as active_orders_count' => fn ($q) => $q->whereIn('status', ['confirmed', 'in_transit'])])
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'status', 'vehicle_type']);

        return response()->json([
            'success' => true,
            'count'   => $riders->count(),
            'riders'  => $riders->map(fn ($r) => [
                'id'                  => $r->id,
                'name'                => $r->name,
                'phone'               => $r->phone,
                'email'               => $r->email,
                'status'              => $r->status,
                'vehicle_type'        => $r->vehicle_type,
                'active_orders_count' => $r->active_orders_count,
            ]),
        ]);
    }

    public function updateClient(Request $request, string $ref): JsonResponse
    {
        $request->validate([
            'name'            => 'nullable|string|max:255',
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'pickup_address'  => 'nullable|string',
        ]);

        $client = is_numeric($ref)
            ? Client::where('id', $ref)->where('is_active', true)->first()
            : Client::where(function ($q) use ($ref) {
                $q->where('phone', $ref)->orWhere('alternate_phone', $ref);
              })->where('is_active', true)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => "Client '{$ref}' not found.", 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        foreach (['name', 'company_name', 'phone', 'alternate_phone', 'email', 'pickup_address'] as $field) {
            if ($request->filled($field)) {
                $client->$field = $request->$field;
            }
        }

        $client->save();

        ActivityLog::log('client_updated', "WhatsApp bot updated client #{$client->id}: {$client->name}", $client, ['source' => 'whatsapp_bot']);

        return response()->json([
            'success' => true,
            'message' => "Client {$client->name} updated.",
            'client'  => ['id' => $client->id, 'name' => $client->name, 'company_name' => $client->company_name, 'phone' => $client->phone, 'pickup_address' => $client->pickup_address],
        ]);
    }

    public function deactivateClient(string $ref): JsonResponse
    {
        $client = is_numeric($ref)
            ? Client::where('id', $ref)->first()
            : Client::where(function ($q) use ($ref) {
                $q->where('phone', $ref)->orWhere('alternate_phone', $ref);
              })->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => "Client '{$ref}' not found.", 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        $client->is_active = false;
        $client->save();

        ActivityLog::log('client_deactivated', "WhatsApp bot deactivated client #{$client->id}: {$client->name}", $client, ['source' => 'whatsapp_bot']);

        return response()->json(['success' => true, 'message' => "Client {$client->name} has been deactivated."]);
    }

    public function listClients(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $clients = Client::where('is_active', true)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($inner) use ($s) {
                    $inner->where('name', 'like', "%{$s}%")
                          ->orWhere('company_name', 'like', "%{$s}%")
                          ->orWhere('phone', 'like', "%{$s}%")
                          ->orWhere('alternate_phone', 'like', "%{$s}%")
                          ->orWhere('email', 'like', "%{$s}%")
                          ->orWhere('pickup_address', 'like', "%{$s}%");
                    if (is_numeric($s)) {
                        $inner->orWhere('id', (int) $s);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'phone', 'alternate_phone', 'email', 'pickup_address']);

        return response()->json([
            'success' => true,
            'count'   => $clients->count(),
            'clients' => $clients->map(fn ($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'company_name'    => $c->company_name,
                'phone'           => $c->phone,
                'alternate_phone' => $c->alternate_phone,
                'email'           => $c->email,
                'pickup_address'  => $c->pickup_address,
            ]),
        ]);
    }

    public function lookupClient(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $client = Client::where('phone', $request->phone)
            ->orWhere('alternate_phone', $request->phone)
            ->where('is_active', true)
            ->first(['id', 'name', 'company_name', 'phone', 'pickup_address', 'pod_remittance_enabled']);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'No active client found with that phone number.', 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        return response()->json([
            'success'                => true,
            'client_id'              => $client->id,
            'name'                   => $client->name,
            'company_name'           => $client->company_name,
            'phone'                  => $client->phone,
            'pickup_address'         => $client->pickup_address,
            'pod_remittance_enabled' => (bool) $client->pod_remittance_enabled,
        ]);
    }

    public function getClient(string $ref): JsonResponse
    {
        $cols = ['id', 'name', 'company_name', 'phone', 'pickup_address', 'pod_remittance_enabled'];
        $client = is_numeric($ref)
            ? Client::where('id', $ref)->where('is_active', true)->first($cols)
            : Client::where(function ($q) use ($ref) {
                $q->where('phone', $ref)->orWhere('alternate_phone', $ref);
              })->where('is_active', true)->first($cols);

        if (!$client) {
            return response()->json(['success' => false, 'message' => "Client '{$ref}' not found.", 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        return response()->json([
            'success' => true,
            'client'  => [
                'id'                     => $client->id,
                'name'                   => $client->name,
                'company_name'           => $client->company_name,
                'phone'                  => $client->phone,
                'pickup_address'         => $client->pickup_address,
                'pod_remittance_enabled' => (bool) $client->pod_remittance_enabled,
            ],
        ]);
    }

    public function lookupUser(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);
        $normalised = $this->normalisePhone($request->phone);

        $user = User::where('phone', $request->phone)
                    ->orWhere('phone', $normalised)
                    ->first(['id', 'name', 'email']);

        if (!$user) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists'  => true,
            'user_id' => $user->id,
            'name'    => $user->name,
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);

        $revenue = Order::whereBetween('delivery_date', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->sum('price');

        $expenses = \App\Modules\Admin\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $orderCount = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $deliveredCount = Order::whereBetween('delivery_date', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->count();
        $pendingCount  = Order::where('status', 'pending')->count();

        return response()->json([
            'success'         => true,
            'period'          => $label,
            'revenue'         => (float) $revenue,
            'expenses'        => (float) $expenses,
            'profit'          => (float) $revenue - (float) $expenses,
            'orders_total'    => $orderCount,
            'orders_delivered'=> $deliveredCount,
            'orders_pending'  => $pendingCount,
            'currency'        => 'NGN',
        ]);
    }

    public function listExpenses(Request $request): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'category'   => 'nullable|string|max:100',
            'limit'      => 'nullable|integer|min:1|max:100',
        ]);

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);

        $query = \App\Modules\Admin\Models\Expense::whereBetween('expense_date', [$startDate, $endDate]);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $expenses = $query->latest('expense_date')->limit($request->integer('limit', 20))->get();

        return response()->json([
            'success'  => true,
            'period'   => $label,
            'count'    => $expenses->count(),
            'total'    => (float) $expenses->sum('amount'),
            'expenses' => $expenses->map(fn ($e) => [
                'id'             => $e->id,
                'date'           => $e->expense_date instanceof \Carbon\Carbon
                    ? $e->expense_date->format('M d, Y')
                    : \Carbon\Carbon::parse($e->expense_date)->format('M d, Y'),
                'category'       => $e->category,
                'description'    => $e->description,
                'amount'         => (float) $e->amount,
                'vendor_name'    => $e->vendor_name,
                'payment_method' => $e->payment_method,
            ]),
        ]);
    }

    public function addExpense(Request $request): JsonResponse
    {
        $request->validate([
            'category'       => 'required|string|max:255',
            'description'    => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'nullable|date',
            'payment_method' => 'nullable|string|max:100',
            'vendor_name'    => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $expense = \App\Modules\Admin\Models\Expense::create([
            'category'       => $request->category,
            'description'    => $request->description,
            'amount'         => $request->amount,
            'expense_date'   => $request->expense_date ?? today(),
            'payment_method' => $request->payment_method,
            'vendor_name'    => $request->vendor_name,
            'notes'          => $request->notes,
        ]);

        ActivityLog::log('expense_created', "WhatsApp bot added expense: {$expense->description} — ₦" . number_format($expense->amount, 2), $expense, ['source' => 'whatsapp_bot']);

        return response()->json([
            'success' => true,
            'message' => "Expense recorded.",
            'expense' => ['id' => $expense->id, 'category' => $expense->category, 'description' => $expense->description, 'amount' => (float) $expense->amount],
        ], 201);
    }

    public function deleteExpense(int $id): JsonResponse
    {
        $expense = \App\Modules\Admin\Models\Expense::find($id);

        if (!$expense) {
            return response()->json(['success' => false, 'message' => "Expense #{$id} not found.", 'error' => 'NOT_FOUND'], 404);
        }

        $desc = $expense->description;
        $amt  = $expense->amount;
        $expense->delete();

        ActivityLog::log('expense_deleted', "WhatsApp bot deleted expense: {$desc} — ₦" . number_format($amt, 2), null, ['source' => 'whatsapp_bot']);

        return response()->json(['success' => true, 'message' => "Expense #{$id} ({$desc}) deleted."]);
    }

    private function resolvePeriod(string $period, ?string $startRaw, ?string $endRaw): array
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay(), 'Today (' . now()->format('M d, Y') . ')'];
            case 'yesterday':
                $d = now()->subDay();
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay(), 'Yesterday (' . $d->format('M d, Y') . ')'];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek(), 'This Week'];
            case 'last_week':
                $lw = now()->subWeek();
                return [$lw->copy()->startOfWeek(), $lw->copy()->endOfWeek(), 'Last Week'];
            case 'last_month':
                $lm = now()->subMonth();
                return [$lm->copy()->startOfMonth(), $lm->copy()->endOfMonth(), $lm->format('F Y')];
            case 'all_time':
                return [\Carbon\Carbon::parse('2000-01-01'), now()->endOfDay(), 'All Time'];
            case 'custom':
                $s = $startRaw ? \Carbon\Carbon::parse($startRaw)->startOfDay() : now()->startOfMonth();
                $e = $endRaw   ? \Carbon\Carbon::parse($endRaw)->endOfDay()     : now()->endOfDay();
                return [$s, $e, $s->format('M d') . ' – ' . $e->format('M d, Y')];
            default: // month
                return [now()->startOfMonth(), now()->endOfMonth(), now()->format('F Y')];
        }
    }

    public function submitRiderApplication(Request $request): JsonResponse
    {
        $request->validate([
            'full_name'       => 'required|string|min:2|max:255',
            'phone'           => 'required|string|min:10|max:20',
            'email'           => 'nullable|email|max:255',
            'home_address'    => 'required|string|min:5|max:500',
            'age'             => 'required|integer|min:18|max:70',
            'license_number'  => 'required|string|max:100',
            'experience'      => 'required|string|max:50',
            'prev_experience' => 'nullable|string|max:2000',
            'start_date'      => 'required|string|max:100',
            'motivation'      => 'nullable|string|max:2000',
        ]);

        try {
            $application = JobApplication::create([
                'job_type'         => 'dispatch_rider',
                'status'           => 'pending',
                'full_name'        => $request->full_name,
                'phone'            => $request->phone,
                'email'            => $request->email,
                'address'          => $request->home_address,
                'age'              => $request->age,
                'license_number'   => $request->license_number,
                'experience_years' => $request->experience,
                'previous_work'    => $request->prev_experience,
                'availability'     => $request->start_date,
                'why_join'         => $request->motivation,
            ]);

            ActivityLog::log(
                'rider_application_submitted',
                "WhatsApp bot: new dispatch rider application from {$request->full_name} ({$request->phone})",
                $application,
                ['source' => 'whatsapp_bot', 'phone' => $request->phone]
            );

            return response()->json([
                'success'        => true,
                'message'        => 'Application submitted successfully.',
                'application_id' => $application->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Bot rider application failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application. Please try again later.',
            ], 500);
        }
    }

    public function clientOrders(Request $request, string $ref): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_transit,delivered,cancelled',
            'limit'  => 'nullable|integer|min:1|max:50',
            'page'   => 'nullable|integer|min:1',
        ]);

        $client = is_numeric($ref)
            ? Client::where('id', $ref)->first()
            : Client::where('phone', $ref)->orWhere('alternate_phone', $ref)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => "Client '{$ref}' not found.", 'error' => 'CLIENT_NOT_FOUND'], 404);
        }

        $query = Order::with('rider:id,name,phone')->where('client_id', $client->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $limit       = $request->integer('limit', 10);
        $page        = max(1, $request->integer('page', 1));
        $total       = $query->count();
        $orders      = $query->latest()->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'success'     => true,
            'client'      => ['id' => $client->id, 'name' => $client->name, 'phone' => $client->phone],
            'total'       => $total,
            'page'        => $page,
            'total_pages' => (int) ceil($total / $limit),
            'orders'      => $orders->map(fn ($o) => $this->orderSummary($o)),
        ]);
    }

    public function cloneOrder(string $orderNumber): JsonResponse
    {
        $original = $this->resolveOrder($orderNumber);

        if (!$original) {
            return response()->json(['success' => false, 'message' => "Order #{$orderNumber} not found.", 'error' => 'ORDER_NOT_FOUND'], 404);
        }

        $clone = Order::create([
            'user_id'          => $original->user_id,
            'client_id'        => $original->client_id,
            'source'           => $original->source,
            'source_contact'   => $original->source_contact,
            'sender_name'      => $original->sender_name,
            'sender_phone'     => $original->sender_phone,
            'sender_email'     => $original->sender_email,
            'customer_name'    => $original->customer_name,
            'customer_phone'   => $original->customer_phone,
            'customer_email'   => $original->customer_email,
            'pickup_address'   => $original->pickup_address,
            'receiver_name'    => $original->receiver_name,
            'receiver_phone'   => $original->receiver_phone,
            'delivery_address' => $original->delivery_address,
            'item_description' => $original->item_description,
            'price'            => $original->price,
            'distance'         => $original->distance,
            'payment_method'   => $original->payment_method,
            'notes'            => $original->notes,
            'priority_level'   => $original->priority_level,
            'status'           => 'pending',
        ]);

        ActivityLog::log('order_cloned', "WhatsApp bot cloned order #{$original->order_number} → #{$clone->order_number}", $clone, ['source' => 'whatsapp_bot', 'cloned_from' => $original->order_number]);

        return response()->json([
            'success'      => true,
            'message'      => "Order cloned from #{$original->order_number}.",
            'order_number' => $clone->order_number,
            'order_id'     => $clone->id,
            'original'     => $original->order_number,
        ], 201);
    }

    public function riderReport(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $rider = Rider::find($id);
        if (!$rider) {
            return response()->json(['success' => false, 'message' => "Rider #{$id} not found.", 'error' => 'RIDER_NOT_FOUND'], 404);
        }

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);

        $orders = Order::where('rider_id', $id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'status', 'price', 'delivery_date', 'is_failed_delivery']);

        $delivered   = $orders->where('status', 'delivered')->count();
        $inTransit   = $orders->where('status', 'in_transit')->count();
        $cancelled   = $orders->where('status', 'cancelled')->count();
        $failed      = $orders->where('is_failed_delivery', true)->count();
        $total       = $orders->count();
        $earnings    = $orders->where('status', 'delivered')->sum('price');
        $successRate = $total > 0 ? round(($delivered / $total) * 100, 1) : null;

        return response()->json([
            'success'      => true,
            'period'       => $label,
            'rider'        => ['id' => $rider->id, 'name' => $rider->name, 'phone' => $rider->phone, 'status' => $rider->status, 'vehicle_type' => $rider->vehicle_type],
            'orders_total' => $total,
            'delivered'    => $delivered,
            'in_transit'   => $inTransit,
            'cancelled'    => $cancelled,
            'failed'       => $failed,
            'success_rate' => $successRate,
            'earnings'     => (float) $earnings,
            'currency'     => 'NGN',
        ]);
    }

    public function setRiderStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $rider = Rider::find($id);
        if (!$rider) {
            return response()->json(['success' => false, 'message' => "Rider #{$id} not found.", 'error' => 'RIDER_NOT_FOUND'], 404);
        }

        $old = $rider->status;
        $rider->status = $request->status;
        $rider->save();

        ActivityLog::log('rider_status_updated', "WhatsApp bot set rider #{$id} ({$rider->name}) status: {$old} → {$rider->status}", $rider, ['source' => 'whatsapp_bot']);

        return response()->json([
            'success'  => true,
            'message'  => "Rider {$rider->name} is now {$rider->status}.",
            'rider_id' => $rider->id,
            'name'     => $rider->name,
            'status'   => $rider->status,
        ]);
    }

    public function expenseBreakdown(Request $request): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);

        $rows = \App\Modules\Admin\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('category, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return response()->json([
            'success'     => true,
            'period'      => $label,
            'grand_total' => (float) $grandTotal,
            'breakdown'   => $rows->map(fn ($r) => [
                'category' => $r->category,
                'count'    => (int) $r->count,
                'total'    => (float) $r->total,
                'pct'      => $grandTotal > 0 ? round(($r->total / $grandTotal) * 100, 1) : 0,
            ]),
        ]);
    }

    public function topClients(Request $request): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'limit'      => 'nullable|integer|min:1|max:20',
        ]);

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);
        $limit = $request->integer('limit', 10);

        $rows = Order::where('status', 'delivered')
            ->whereNotNull('client_id')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->selectRaw('client_id, COUNT(*) as deliveries, SUM(price) as revenue')
            ->groupBy('client_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->with('client:id,name,company_name,phone')
            ->get();

        return response()->json([
            'success'    => true,
            'period'     => $label,
            'top_clients' => $rows->map(fn ($r) => [
                'rank'         => null,
                'client_id'    => $r->client_id,
                'name'         => $r->client?->name ?? '—',
                'company_name' => $r->client?->company_name,
                'phone'        => $r->client?->phone,
                'deliveries'   => (int) $r->deliveries,
                'revenue'      => (float) $r->revenue,
            ])->values()->map(function ($item, $i) {
                $item['rank'] = $i + 1;
                return $item;
            }),
        ]);
    }

    public function riderEarnings(Request $request): JsonResponse
    {
        $request->validate([
            'period'     => 'nullable|in:today,yesterday,week,month,all_time,last_week,last_month,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $period = $request->get('period', 'month');
        [$startDate, $endDate, $label] = $this->resolvePeriod($period, $request->start_date, $request->end_date);

        $rows = Order::where('status', 'delivered')
            ->whereNotNull('rider_id')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->selectRaw('rider_id, COUNT(*) as deliveries, SUM(price) as earnings')
            ->groupBy('rider_id')
            ->orderByDesc('earnings')
            ->with('rider:id,name,phone,vehicle_type')
            ->get();

        return response()->json([
            'success'  => true,
            'period'   => $label,
            'earnings' => $rows->map(fn ($r) => [
                'rider_id'     => $r->rider_id,
                'name'         => $r->rider?->name ?? '—',
                'phone'        => $r->rider?->phone,
                'vehicle_type' => $r->rider?->vehicle_type,
                'deliveries'   => (int) $r->deliveries,
                'earnings'     => (float) $r->earnings,
            ]),
        ]);
    }

    public function createClient(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:20',
            'company_name'    => 'nullable|string|max:255',
            'alternate_phone' => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'pickup_address'  => 'nullable|string',
        ]);

        $existing = Client::where('phone', $request->phone)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "A client with phone {$request->phone} already exists (ID: {$existing->id}).",
                'error'   => 'DUPLICATE_PHONE',
                'client'  => ['id' => $existing->id, 'name' => $existing->name],
            ], 409);
        }

        $client = Client::create([
            'name'            => $request->name,
            'phone'           => $request->phone,
            'company_name'    => $request->company_name,
            'alternate_phone' => $request->alternate_phone,
            'email'           => $request->email,
            'pickup_address'  => $request->pickup_address,
            'is_active'       => true,
        ]);

        ActivityLog::log('client_created', "WhatsApp bot created client #{$client->id}: {$client->name}", $client, ['source' => 'whatsapp_bot']);

        return response()->json([
            'success'   => true,
            'message'   => "Client {$client->name} created.",
            'client_id' => $client->id,
            'client'    => ['id' => $client->id, 'name' => $client->name, 'phone' => $client->phone, 'company_name' => $client->company_name, 'pickup_address' => $client->pickup_address],
        ], 201);
    }

    public function universalSearch(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $s = $request->q;

        $orders = Order::with('rider:id,name', 'client:id,name')
            ->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('receiver_name', 'like', "%{$s}%")
                  ->orWhere('sender_name', 'like', "%{$s}%")
                  ->orWhere('delivery_address', 'like', "%{$s}%")
                  ->orWhere('receiver_phone', 'like', "%{$s}%");
                if (is_numeric($s)) {
                    $q->orWhere('id', (int) $s);
                }
            })
            ->latest()->limit(5)->get();

        $clients = Client::where('is_active', true)
            ->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
                if (is_numeric($s)) {
                    $q->orWhere('id', (int) $s);
                }
            })
            ->limit(5)->get(['id', 'name', 'company_name', 'phone', 'pickup_address']);

        $riders = Rider::where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
                if (is_numeric($s)) {
                    $q->orWhere('id', (int) $s);
                }
            })
            ->limit(5)->get(['id', 'name', 'phone', 'status', 'vehicle_type']);

        return response()->json([
            'success' => true,
            'query'   => $s,
            'orders'  => $orders->map(fn ($o) => array_merge($this->orderSummary($o), ['_type' => 'order'])),
            'clients' => $clients->map(fn ($c) => ['_type' => 'client', 'id' => $c->id, 'name' => $c->name, 'company_name' => $c->company_name, 'phone' => $c->phone, 'pickup_address' => $c->pickup_address]),
            'riders'  => $riders->map(fn ($r) => ['_type' => 'rider', 'id' => $r->id, 'name' => $r->name, 'phone' => $r->phone, 'status' => $r->status, 'vehicle_type' => $r->vehicle_type]),
            'total'   => $orders->count() + $clients->count() + $riders->count(),
        ]);
    }

    private function resolveOrder(string $ref): ?Order
    {
        return is_numeric($ref)
            ? Order::find((int) $ref)
            : Order::where('order_number', $ref)->first();
    }

    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 13 && str_starts_with($digits, '234')) {
            return '0' . substr($digits, 3);
        }
        return $digits ?: $phone;
    }

    private function resolveClient(?string $phone, ?int $clientId): ?Client
    {
        if ($clientId) {
            return Client::where('id', $clientId)->where('is_active', true)->first();
        }
        if ($phone) {
            return Client::where('phone', $phone)->orWhere('alternate_phone', $phone)->where('is_active', true)->first();
        }
        return null;
    }

    private function orderSummary(Order $order): array
    {
        return [
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'status'           => $order->status,
            'is_failed'        => $order->is_failed_delivery,
            'client'           => $order->client ? $order->client->name : null,
            'sender_name'      => $order->sender_name,
            'sender_phone'     => $order->sender_phone,
            'pickup_address'   => $order->pickup_address,
            'receiver_name'    => $order->receiver_name,
            'receiver_phone'   => $order->receiver_phone,
            'delivery_address' => $order->delivery_address,
            'item_description' => $order->item_description,
            'price'            => (float) $order->price,
            'payment_method'   => $order->payment_method,
            'rider'            => $order->rider ? ['id' => $order->rider->id, 'name' => $order->rider->name, 'phone' => $order->rider->phone] : null,
            'created_at'       => $order->created_at->toISOString(),
        ];
    }

    private function orderDetail(Order $order): array
    {
        return [
            'id'                 => $order->id,
            'order_number'       => $order->order_number,
            'status'             => $order->status,
            'priority_level'     => $order->priority_level,
            'is_failed_delivery' => $order->is_failed_delivery,
            'client'             => $order->client ? ['id' => $order->client->id, 'name' => $order->client->name, 'company_name' => $order->client->company_name, 'phone' => $order->client->phone] : null,
            'sender_name'        => $order->sender_name,
            'sender_phone'       => $order->sender_phone,
            'pickup_address'     => $order->pickup_address,
            'receiver_name'      => $order->receiver_name,
            'receiver_phone'     => $order->receiver_phone,
            'delivery_address'   => $order->delivery_address,
            'item_description'   => $order->item_description,
            'price'              => (float) $order->price,
            'distance_km'        => $order->distance ? (float) $order->distance : null,
            'payment_method'     => $order->payment_method,
            'amount_received'    => $order->amount_received ? (float) $order->amount_received : null,
            'notes'              => $order->notes,
            'rider'              => $order->rider ? ['id' => $order->rider->id, 'name' => $order->rider->name, 'phone' => $order->rider->phone] : null,
            'pickup_date'        => $order->pickup_date?->toISOString(),
            'delivery_date'      => $order->delivery_date?->toISOString(),
            'created_at'         => $order->created_at->toISOString(),
        ];
    }
}
