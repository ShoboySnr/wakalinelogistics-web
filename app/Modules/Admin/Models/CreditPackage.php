<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'credits',
        'price',
        'price_per_credit',
        'validity_days',
        'bonus_credits',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'price' => 'decimal:2',
        'price_per_credit' => 'decimal:2',
    ];

    /**
     * Boot method to calculate price per credit
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($package) {
            if ($package->credits > 0) {
                $package->price_per_credit = $package->price / $package->credits;
            }
        });
    }

    /**
     * Get credit transactions for this package
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Scope to get only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get popular packages
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }

    /**
     * Get total credits (including bonus)
     */
    public function getTotalCreditsAttribute()
    {
        return $this->credits + $this->bonus_credits;
    }

    /**
     * Get savings percentage compared to base price
     */
    public function getSavingsPercentageAttribute()
    {
        // Assuming base price per credit is ₦100
        $basePrice = 100;
        if ($this->price_per_credit < $basePrice) {
            return round((($basePrice - $this->price_per_credit) / $basePrice) * 100);
        }
        return 0;
    }
}
