<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCustomer extends Model
{
    protected $table = 'client_customers';

    protected $fillable = [
        'client_id',
        'receiver_name',
        'receiver_phone',
        'receiver_email',
        'delivery_address',
        'total_orders',
        'total_spend',
        'last_order_at',
    ];

    protected $casts = [
        'total_orders'  => 'integer',
        'total_spend'   => 'float',
        'last_order_at' => 'datetime',
    ];

    /**
     * Find an existing customer record for this client by phone or email.
     * Phone takes priority; email is the fallback when phone is absent.
     */
    public static function findForClient(int $clientId, ?string $phone, ?string $email): ?self
    {
        if ($phone) {
            $record = static::where('client_id', $clientId)
                ->where('receiver_phone', $phone)
                ->first();
            if ($record) return $record;
        }

        if ($email) {
            return static::where('client_id', $clientId)
                ->where('receiver_email', $email)
                ->first();
        }

        return null;
    }

    /**
     * Upsert a customer record after an order is placed.
     * Creates a new record if neither phone nor email matches an existing one.
     */
    public static function upsertFromOrder(
        int     $clientId,
        ?string $name,
        ?string $phone,
        ?string $email,
        ?string $deliveryAddress,
        float   $orderPrice,
        \Carbon\Carbon $orderedAt
    ): void {
        if (!$phone && !$email) {
            return;
        }

        $record = static::findForClient($clientId, $phone, $email);

        if ($record) {
            $updates = [
                'total_orders'  => $record->total_orders + 1,
                'total_spend'   => $record->total_spend + $orderPrice,
                'last_order_at' => $orderedAt,
            ];
            if ($name)            $updates['receiver_name']    = $name;
            if ($phone)           $updates['receiver_phone']   = $phone;
            if ($email)           $updates['receiver_email']   = $email;
            if ($deliveryAddress) $updates['delivery_address'] = $deliveryAddress;
            $record->update($updates);
        } else {
            static::create([
                'client_id'        => $clientId,
                'receiver_name'    => $name,
                'receiver_phone'   => $phone ?: null,
                'receiver_email'   => $email ?: null,
                'delivery_address' => $deliveryAddress,
                'total_orders'     => 1,
                'total_spend'      => $orderPrice,
                'last_order_at'    => $orderedAt,
            ]);
        }
    }
}
