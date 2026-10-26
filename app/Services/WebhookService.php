<?php

namespace App\Services;

use App\Jobs\SendWebhookNotification;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use App\Modules\Admin\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function sendOrderEvent(Order $order, string $event, array $additionalData = []): void
    {
        if (!$order->client_id) {
            return;
        }

        $client = $order->client;
        if (!$client) {
            return;
        }

        $integration = ClientIntegration::where('client_id', $client->id)
            ->where('integration_type', 'webhook')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return;
        }

        $webhookUrl = $integration->credentials['webhook_url'] ?? null;
        if (!$webhookUrl) {
            return;
        }

        $payload = $this->buildPayload($order, $event, $additionalData);
        
        $this->sendWebhook($integration, $webhookUrl, $payload, $event);
    }

    private function buildPayload(Order $order, string $event, array $additionalData): array
    {
        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => array_merge([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                'client_id' => $order->client_id,
                'source' => $order->source,
                'sender' => [
                    'name' => $order->sender_name,
                    'phone' => $order->sender_phone,
                    'email' => $order->sender_email,
                ],
                'receiver' => [
                    'name' => $order->receiver_name,
                    'phone' => $order->receiver_phone,
                    'email' => $order->receiver_email,
                ],
                'pickup_address' => $order->pickup_address,
                'delivery_address' => $order->delivery_address,
                'pickup_zone' => $order->pickup_zone,
                'delivery_zone' => $order->delivery_zone,
                'item_description' => $order->item_description,
                'item_size' => $order->item_size,
                'weight' => $order->weight,
                'quantity' => $order->quantity,
                'distance' => $order->distance,
                'price' => $order->price,
                'currency' => 'NGN',
                'priority_level' => $order->priority_level,
                'pickup_date' => $order->pickup_date?->toIso8601String(),
                'delivery_date' => $order->delivery_date?->toIso8601String(),
                'notes' => $order->notes,
                'rider' => $order->rider ? [
                    'id' => $order->rider->id,
                    'name' => $order->rider->name,
                    'phone' => $order->rider->phone,
                ] : null,
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
            ], $additionalData),
        ];
    }

    private function sendWebhook(ClientIntegration $integration, string $url, array $payload, string $event): void
    {
        $secret = $integration->credentials['secret'] ?? null;
        
        SendWebhookNotification::dispatch(
            $integration->id,
            $url,
            $payload,
            $event,
            $secret
        );
    }

    private function generateSignature(array $payload, ?string $secret): string
    {
        if (!$secret) {
            return '';
        }

        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
