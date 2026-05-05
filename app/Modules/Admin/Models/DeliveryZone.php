<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'areas',
        'base_credits',
        'base_price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'areas' => 'array',
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    /**
     * Scope to get only active zones
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return $this->base_price ? '₦' . number_format($this->base_price, 2) : 'N/A';
    }

    /**
     * Check if an area is in this zone
     */
    public function hasArea(string $area): bool
    {
        if (!$this->areas) {
            return false;
        }

        return in_array(strtolower($area), array_map('strtolower', $this->areas));
    }

    /**
     * Find zone by area name
     */
    public static function findByArea(string $area): ?self
    {
        return static::active()
            ->get()
            ->first(function ($zone) use ($area) {
                return $zone->hasArea($area);
            });
    }
}
