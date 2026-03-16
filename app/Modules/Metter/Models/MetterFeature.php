<?php

namespace App\Modules\Metter\Models;

use Illuminate\Database\Eloquent\Model;

class MetterFeature extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_enabled',
        'is_premium',
        'sort_order',
        'settings',
        'price',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_premium' => 'boolean',
        'settings' => 'array',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function isPremium(): bool
    {
        return $this->is_premium;
    }
}
