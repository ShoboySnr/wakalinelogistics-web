<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class ClientStock extends Model
{
    protected $fillable = [
        'client_id',
        'product_name',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
