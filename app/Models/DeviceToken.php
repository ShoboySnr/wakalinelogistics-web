<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'token',
        'platform',
        'app',
        'device_name',
        'app_version',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function register(Model $owner, array $attributes): self
    {
        return static::updateOrCreate(
            ['token' => $attributes['token']],
            array_merge($attributes, [
                'tokenable_type' => $owner->getMorphClass(),
                'tokenable_id' => $owner->getKey(),
                'last_used_at' => now(),
            ])
        );
    }
}
