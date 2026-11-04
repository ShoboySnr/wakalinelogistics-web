<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Resources\OrderResource;
use App\Modules\Admin\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->get('per_page', 20)));
        $orders = $this->orders->listQuery($request)
            ->with(['rider:id,name,phone', 'client:id,name,company_name,pod_remittance_enabled'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'stats' => $this->orders->listStats(),
        ]);
    }

    public function calculatePrice(Request $request, \App\Modules\DeliveryCalculator\Services\DeliveryPriceService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
        ]);

        $result = $pricing->processDeliveryCalculation($validated['pickup_address'], $validated['dropoff_address']);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_fee' => $result['delivery_fee'],
                'distance_km' => $result['distance_km'],
                'pickup_zone' => $result['pickup_zone'],
                'delivery_zone' => $result['delivery_zone'],
                'pickup_formatted' => $result['pickup_formatted'] ?? null,
                'delivery_formatted' => $result['delivery_formatted'] ?? null,
                'breakdown' => $result['price_breakdown'] ?? null,
            ],
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'clients' => Client::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'company_name', 'phone', 'email', 'pickup_address', 'pod_remittance_enabled']),
                'riders' => Rider::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']),
                'stock_clients' => $this->orders->stockClients(),
                'remittance_clients' => $this->orders->remittanceClients(),
                'statuses' => OrderService::STATUSES,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $order = $this->orders->create($request->validate($this->orders->rules(true)), $request);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $this->resource($order),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->resource(Order::findOrFail($id)),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->orders->update($order, $request->validate($this->orders->rules(false)), $request);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $this->resource($order),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:' . implode(',', OrderService::ROUTE_STATUSES)]);

        $order = Order::findOrFail($id);
        $this->orders->updateStatus($order, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $this->resource($order),
        ]);
    }

    public function assignRider(Request $request, int $id): JsonResponse
    {
        $request->validate(['rider_id' => 'nullable|exists:riders,id']);

        $order = Order::findOrFail($id);
        $this->orders->assignRider($order, $request->rider_id);

        return response()->json([
            'success' => true,
            'message' => 'Rider assigned successfully',
            'data' => $this->resource($order),
        ]);
    }

    public function paymentQueue(Request $request): JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->get('per_page', 20)));
        $orders = Order::with(['rider:id,name,phone', 'client:id,name,company_name,pod_remittance_enabled'])
            ->whereNotNull('payment_collected_at')
            ->whereNull('payment_confirmed_at')
            ->orderBy('payment_collected_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'stats' => [
                'awaiting_confirmation' => Order::whereNotNull('payment_collected_at')->whereNull('payment_confirmed_at')->count(),
            ],
        ]);
    }

    public function markPaymentCollected(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->orders->markPaymentCollected($order);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as collected',
            'data' => $this->resource($order->fresh()),
        ]);
    }

    public function confirmPayment(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->orders->confirmPayment($order);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed',
            'data' => $this->resource($order->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $orderNumber = $order->order_number;
        $order->delete();

        ActivityLog::log('order_deleted', "Deleted order #{$orderNumber}");

        return response()->json(['success' => true, 'message' => 'Order deleted successfully']);
    }

    public function remittanceData(Request $request): JsonResponse
    {
        $request->validate(['client_id' => 'required|integer|exists:clients,id']);

        $client = Client::findOrFail($request->client_id);

        if (!$client->pod_remittance_enabled) {
            return response()->json(['success' => false, 'message' => 'POD remittance is not enabled for this client.'], 403);
        }

        return response()->json(['success' => true, 'data' => $this->orders->podRemittanceData($client)]);
    }

    public function markRemitted(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $count = $this->orders->markRemitted((int) $request->client_id, $request->order_ids);

        return response()->json([
            'success' => true,
            'message' => "{$count} order(s) marked as remitted.",
        ]);
    }

    private function resource(Order $order): OrderResource
    {
        return new OrderResource($order->fresh(['rider', 'client', 'creator', 'stockItems']));
    }
}
