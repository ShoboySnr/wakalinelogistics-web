<?php

namespace App\Observers;

use App\Modules\Admin\Models\Order;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;

class OrderWebhookObserver
{
    private WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function created(Order $order): void
    {
        try {
            $this->webhookService->sendOrderEvent($order, 'order.created');
        } catch (\Exception $e) {
            Log::error('Webhook observer error on order created', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Order $order): void
    {
        try {
            if ($order->wasChanged('status')) {
                $newStatus = $order->status;
                $event = "order.{$newStatus}";
                
                $this->webhookService->sendOrderEvent($order, $event, [
                    'previous_status' => $order->getOriginal('status'),
                ]);
            }

            if ($order->wasChanged('rider_id') && $order->rider_id) {
                $this->webhookService->sendOrderEvent($order, 'order.rider_assigned', [
                    'rider' => [
                        'id' => $order->rider->id,
                        'name' => $order->rider->name,
                        'phone' => $order->rider->phone,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook observer error on order updated', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
