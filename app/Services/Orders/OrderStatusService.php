<?php

namespace App\Services\Orders;

use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\OrderStatusEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const ACCEPTED = 'accepted';
    public const ARRIVED_AT_PICKUP = 'arrived_at_pickup';
    public const PICKED_UP = 'picked_up';
    public const IN_TRANSIT = 'in_transit';
    public const ARRIVED_AT_DROPOFF = 'arrived_at_dropoff';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    public const ALL = [
        self::PENDING, self::CONFIRMED, self::ACCEPTED, self::ARRIVED_AT_PICKUP,
        self::PICKED_UP, self::IN_TRANSIT, self::ARRIVED_AT_DROPOFF,
        self::DELIVERED, self::FAILED, self::CANCELLED,
    ];

    public const OPEN = [
        self::PENDING, self::CONFIRMED, self::ACCEPTED, self::ARRIVED_AT_PICKUP,
        self::PICKED_UP, self::IN_TRANSIT, self::ARRIVED_AT_DROPOFF,
    ];

    public const TERMINAL = [self::DELIVERED, self::FAILED, self::CANCELLED];

    private const RIDER_TRANSITIONS = [
        self::CONFIRMED => [self::ACCEPTED, self::FAILED],
        self::ACCEPTED => [self::ARRIVED_AT_PICKUP, self::FAILED],
        self::ARRIVED_AT_PICKUP => [self::PICKED_UP, self::FAILED],
        self::PICKED_UP => [self::IN_TRANSIT, self::ARRIVED_AT_DROPOFF, self::DELIVERED, self::FAILED],
        self::IN_TRANSIT => [self::ARRIVED_AT_DROPOFF, self::DELIVERED, self::FAILED],
        self::ARRIVED_AT_DROPOFF => [self::DELIVERED, self::FAILED],
    ];

    public function riderMayTransition(string $from, string $to): bool
    {
        return in_array($to, self::RIDER_TRANSITIONS[$from] ?? [], true);
    }

    public function nextForRider(string $from): array
    {
        return self::RIDER_TRANSITIONS[$from] ?? [];
    }

    public function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }

    /**
     * Applies a status change and records the event. Returns the event, or the
     * event already recorded under the same clientEventId when a client retries.
     */
    public function apply(
        Order $order,
        string $to,
        ?Model $actor = null,
        array $context = []
    ): OrderStatusEvent {
        if (! in_array($to, self::ALL, true)) {
            throw new \InvalidArgumentException("Unknown order status [{$to}].");
        }

        $clientEventId = $context['client_event_id'] ?? null;

        if ($clientEventId) {
            $existing = OrderStatusEvent::where('client_event_id', $clientEventId)->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($order, $to, $actor, $context, $clientEventId) {
            $order->refresh();
            $from = $order->status;

            $event = OrderStatusEvent::create([
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'latitude' => $context['latitude'] ?? null,
                'longitude' => $context['longitude'] ?? null,
                'client_event_id' => $clientEventId,
                'note' => $context['note'] ?? null,
                'occurred_at' => $context['occurred_at'] ?? now(),
            ]);

            if ($from !== $to) {
                $order->status = $to;

                if ($to === self::DELIVERED && empty($order->delivery_date)) {
                    $order->delivery_date = $event->occurred_at;
                }

                if ($to === self::PICKED_UP && empty($order->pickup_date)) {
                    $order->pickup_date = $event->occurred_at;
                }

                if ($to === self::FAILED) {
                    $order->is_failed_delivery = true;
                }

                $order->save();
            }

            return $event;
        });
    }
}
