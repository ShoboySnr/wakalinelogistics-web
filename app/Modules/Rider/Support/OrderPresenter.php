<?php

namespace App\Modules\Rider\Support;

use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\OrderStatusEvent;
use App\Modules\Admin\Models\Setting;
use App\Services\Orders\OrderStatusService;

class OrderPresenter
{
    public function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_shape' => $this->paymentShape($order),
            'pickup_address' => $order->pickup_address,
            'pickup_coordinate' => $this->coordinate($order->pickup_latitude, $order->pickup_longitude),
            'delivery_address' => $order->delivery_address,
            'delivery_coordinate' => $this->coordinate($order->delivery_latitude, $order->delivery_longitude),
            'recipient_name' => $order->receiver_name,
            'recipient_phone' => $order->receiver_phone,
            'item_description' => $order->item_description,
            'currency_code' => $this->currencyCode(),
            'price' => (float) $order->price,
            'goods_value' => null,
            'expected_collection' => null,
            'pickup_date' => $order->pickup_date?->toIso8601String(),
            'delivery_date' => $order->delivery_date?->toIso8601String(),
        ];
    }

    public function detail(Order $order): array
    {
        $status = $order->status;

        return $this->summary($order) + [
            'area' => $order->delivery_zone ?: $order->delivery_city,
            'notes' => $order->notes,
            'item_size' => $order->item_size,
            'quantity' => $order->quantity,
            'sender_name' => $order->sender_name,
            'sender_phone' => $order->sender_phone,
            'proof_image' => $order->delivery_proof_image,
            'next_status' => $this->nextStatus($status),
            'allowed_transitions' => app(OrderStatusService::class)->nextForRider($status),
            'timeline' => OrderStatusEvent::where('order_id', $order->id)
                ->orderBy('occurred_at')
                ->get()
                ->map(fn (OrderStatusEvent $e) => [
                    'from_status' => $e->from_status,
                    'to_status' => $e->to_status,
                    'occurred_at' => $e->occurred_at?->toIso8601String(),
                    'note' => $e->note,
                ])
                ->values(),
        ];
    }

    private function nextStatus(string $status): ?string
    {
        return match ($status) {
            OrderStatusService::CONFIRMED => OrderStatusService::ACCEPTED,
            OrderStatusService::ACCEPTED => OrderStatusService::ARRIVED_AT_PICKUP,
            OrderStatusService::ARRIVED_AT_PICKUP => OrderStatusService::PICKED_UP,
            OrderStatusService::PICKED_UP => OrderStatusService::IN_TRANSIT,
            OrderStatusService::IN_TRANSIT => OrderStatusService::ARRIVED_AT_DROPOFF,
            OrderStatusService::ARRIVED_AT_DROPOFF => OrderStatusService::DELIVERED,
            default => null,
        };
    }

    private function paymentShape(Order $order): string
    {
        return $order->payment_method === 'cash' ? 'merchant_cod' : 'prepaid';
    }

    private function currencyCode(): string
    {
        return Setting::get('default_currency', 'NGN');
    }

    private function coordinate($latitude, $longitude): ?array
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return ['latitude' => (float) $latitude, 'longitude' => (float) $longitude];
    }
}
