<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCustomerMeta extends Model
{
    protected $table = 'client_customer_meta';

    protected $fillable = [
        'client_id',
        'receiver_phone',
        'starred',
        'tags',
        'notes',
        'addresses',
    ];

    protected $casts = [
        'starred'   => 'boolean',
        'tags'      => 'array',
        'addresses' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get meta for a specific client+phone, or return default shape.
     */
    public static function getFor(int $clientId, string $phone): array
    {
        $meta = static::where('client_id', $clientId)
            ->where('receiver_phone', $phone)
            ->first();

        return [
            'starred'   => $meta?->starred ?? false,
            'tags'      => $meta?->tags ?? [],
            'notes'     => $meta?->notes ?? '',
            'addresses' => $meta?->addresses ?? [],
        ];
    }

    /**
     * Upsert meta for a client+phone.
     */
    public static function upsertFor(int $clientId, string $phone, array $data): self
    {
        $meta = static::firstOrNew([
            'client_id'      => $clientId,
            'receiver_phone' => $phone,
        ]);

        if (array_key_exists('starred', $data))   $meta->starred   = (bool) $data['starred'];
        if (array_key_exists('tags', $data))       $meta->tags      = is_array($data['tags']) ? $data['tags'] : [];
        if (array_key_exists('notes', $data))      $meta->notes     = (string) $data['notes'];
        if (array_key_exists('addresses', $data))  $meta->addresses = is_array($data['addresses']) ? $data['addresses'] : [];

        $meta->save();
        return $meta;
    }
}
