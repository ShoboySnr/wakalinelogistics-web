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
        $request->validate(['status' => 'required|in:' . implode(',', OrderService::STATUSES)]);

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
