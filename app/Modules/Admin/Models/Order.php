<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            
            $detector = app(\App\Services\ZoneBatchDiscountService::class);

            if (empty($order->pickup_zone) && ! empty($order->pickup_address)) {
                $order->pickup_zone = $detector->detectZone($order->pickup_address);
            }
            if (empty($order->delivery_zone) && ! empty($order->delivery_address)) {
                $order->delivery_zone = $detector->detectZone($order->delivery_address);
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'created_by',
        'rider_id',
        'client_id',
        'source',
        'source_contact',
        'source_notes',
        'customer_name',
        'customer_email',
        'customer_phone',
        'sender_name',
        'sender_phone',
        'sender_email',
        'pickup_address',
        'pickup_city',
        'delivery_address',
        'delivery_city',
        'receiver_name',
        'receiver_phone',
        'item_description',
        'item_size',
        'weight',
        'quantity',
        'distance',
        'price',
        'pickup_zone',
        'delivery_zone',
        'base_price',
        'zone_discount_percent',
        'zone_discount_amount',
        'zone_batch_size',
        'status',
        'payment_method',
        'paid_with_credits',
        'credits_used',
        'priority_level',
        'notes',
        'package_description',
        'package_quantity',
        'pickup_date',
        'delivery_date',
        'package_image_1',
        'package_image_2',
        'package_image_3',
        'package_image_4',
        'delivery_proof_image',
        'additional_file_1',
        'additional_file_2',
        'additional_file_3',
        'amount_received',
        'remitted_at',
        'is_failed_delivery',
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
        'remitted_at' => 'datetime',
        'price' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'weight' => 'decimal:2',
        'distance' => 'decimal:2',
        'is_failed_delivery' => 'boolean',
        'paid_with_credits' => 'boolean',
        'credits_used' => 'integer',
        'package_quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'WKL';
        $date = now()->format('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
}
