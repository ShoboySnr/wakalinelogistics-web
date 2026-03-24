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
        'delivery_address',
        'receiver_name',
        'receiver_phone',
        'item_description',
        'item_size',
        'weight',
        'quantity',
        'distance',
        'price',
        'status',
        'priority_level',
        'notes',
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
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'distance' => 'decimal:2',
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
