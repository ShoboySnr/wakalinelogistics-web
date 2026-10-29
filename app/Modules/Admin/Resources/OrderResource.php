<?php

namespace App\Modules\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderResource extends JsonResource
{
    private const FILE_FIELDS = [
        'package_image_1', 'package_image_2', 'package_image_3', 'package_image_4',
        'additional_file_1', 'additional_file_2', 'additional_file_3', 'delivery_proof_image',
    ];

    public function toArray(Request $request): array
    {
        $files = [];
        foreach (self::FILE_FIELDS as $field) {
            $files[$field] = $this->{$field} ? Storage::disk('public')->url($this->{$field}) : null;
        }

        $isFailed = (bool) $this->is_failed_delivery;
        $toRemit = $this->amount_received !== null
            ? ($isFailed ? -(float) $this->price : max(0, (float) $this->amount_received - (float) $this->price))
            : null;

        return array_merge([
            'id' => $this->id,
            'order_number' => $this->order_number,
            'client_id' => $this->client_id,
            'rider_id' => $this->rider_id,
            'source' => $this->source,
            'source_contact' => $this->source_contact,
            'source_notes' => $this->source_notes,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'sender_name' => $this->sender_name,
            'sender_phone' => $this->sender_phone,
            'sender_email' => $this->sender_email,
            'pickup_address' => $this->pickup_address,
            'pickup_city' => $this->pickup_city,
            'delivery_address' => $this->delivery_address,
            'delivery_city' => $this->delivery_city,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'item_description' => $this->item_description,
            'item_size' => $this->item_size,
            'weight' => $this->weight,
            'quantity' => $this->quantity,
            'distance' => $this->distance,
            'price' => (float) $this->price,
            'pickup_zone' => $this->pickup_zone,
            'delivery_zone' => $this->delivery_zone,
            'status' => $this->status,
            'priority_level' => $this->priority_level,
            'payment_method' => $this->payment_method,
            'amount_received' => $this->amount_received !== null ? (float) $this->amount_received : null,
            'to_remit' => $toRemit,
            'remitted_at' => $this->remitted_at,
            'is_failed_delivery' => $isFailed,
            'notes' => $this->notes,
            'package_description' => $this->package_description,
            'package_quantity' => $this->package_quantity,
            'pickup_date' => $this->pickup_date,
            'delivery_date' => $this->delivery_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'rider' => $this->whenLoaded('rider', fn () => $this->rider ? [
                'id' => $this->rider->id,
                'name' => $this->rider->name,
                'phone' => $this->rider->phone,
            ] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'company_name' => $this->client->company_name,
                'pod_remittance_enabled' => (bool) $this->client->pod_remittance_enabled,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'stock_items' => $this->whenLoaded('stockItems', fn () => $this->stockItems->map(fn ($item) => [
                'id' => $item->id,
                'client_stock_id' => $item->client_stock_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'deducted_at' => $item->deducted_at,
            ])->values()),
        ], ['files' => $files]);
    }
}
