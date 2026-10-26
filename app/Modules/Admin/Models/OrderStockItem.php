<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStockItem extends Model
{
    protected $fillable = [
        'order_id',
        'client_stock_id',
        'product_name',
        'quantity',
        'deducted_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'deducted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function clientStock()
    {
        return $this->belongsTo(ClientStock::class);
    }
}
