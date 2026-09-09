<?php

namespace App\Modules\Rider\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Rider\Support\OrderPresenter;
use App\Services\Orders\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrdersController extends Controller
{
    public function __construct(
        private readonly OrderStatusService $statuses,
        private readonly OrderPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rider = $request->user();

        $orders = Order::where('rider_id', $rider->id)
            ->whereIn('status', OrderStatusService::OPEN)
            ->orderByRaw('COALESCE(pickup_date, created_at) asc')
            ->get();

        $grouped = $orders
            ->groupBy(fn (Order $order) => $order->delivery_zone ?: ($order->delivery_city ?: 'Other'))
            ->map(fn ($group, $area) => [
                'area' => $area,
                'count' => $group->count(),
                'orders' => $group->map(fn (Order $o) => $this->presenter->summary($o))->values(),
            ])
            ->values();

        return response()->json([
            'orders' => $orders->map(fn (Order $o) => $this->presenter->summary($o))->values(),
            'areas' => $grouped,
            'counts' => [
                'total' => $orders->count(),
                'to_collect' => $orders->where('payment_method', 'cash')->count(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['order' => $this->presenter->detail($this->findOrder($request, $id))]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', OrderStatusService::ALL),
            'client_event_id' => 'required|string|max:64',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'occurred_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $order = $this->findOrder($request, $id);

        if ($order->status !== $data['status']
            && ! $this->statuses->riderMayTransition($order->status, $data['status'])) {
            return response()->json([
                'message' => "You cannot move this delivery from {$order->status} to {$data['status']}.",
                'allowed' => $this->statuses->nextForRider($order->status),
            ], 422);
        }

        $this->statuses->apply($order, $data['status'], $request->user(), $data);

        return response()->json(['order' => $this->presenter->detail($order->fresh())]);
    }

    public function fail(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|in:recipient_refused,recipient_unreachable,address_wrong,rescheduled,returned_to_sender,rider_unable_to_complete',
            'client_event_id' => 'required|string|max:64',
            'note' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $order = $this->findOrder($request, $id);

        if (! $this->statuses->riderMayTransition($order->status, OrderStatusService::FAILED)) {
            return response()->json([
                'message' => 'This delivery can no longer be marked as undelivered.',
            ], 422);
        }

        $this->statuses->apply($order, OrderStatusService::FAILED, $request->user(), [
            'client_event_id' => $data['client_event_id'],
            'note' => trim($data['reason'].' '.($data['note'] ?? '')),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return response()->json(['order' => $this->presenter->detail($order->fresh())]);
    }

    public function uploadProof(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'photo' => 'required|image|max:8192',
            'recipient_name' => 'nullable|string|max:120',
        ]);

        $order = $this->findOrder($request, $id);

        $path = $data['photo']->store('delivery-proofs', 'public');

        $order->delivery_proof_image = Storage::url($path);

        if (! empty($data['recipient_name'])) {
            $order->receiver_name = $data['recipient_name'];
        }

        $order->save();

        return response()->json(['order' => $this->presenter->detail($order->fresh())]);
    }

    private function findOrder(Request $request, int $id): Order
    {
        return Order::where('rider_id', $request->user()->id)->findOrFail($id);
    }
}
