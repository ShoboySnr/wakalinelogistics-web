<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'total_credits',
        'used_credits',
        'available_credits',
        'last_purchase_at',
        'last_used_at',
    ];

    protected $casts = [
        'last_purchase_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the client that owns the credits
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Add credits to the client's account
     */
    public function addCredits(int $credits)
    {
        $this->total_credits += $credits;
        $this->available_credits += $credits;
        $this->last_purchase_at = now();
        $this->save();
    }

    /**
     * Deduct credits from the client's account
     */
    public function deductCredits(int $credits)
    {
        if ($this->available_credits < $credits) {
            throw new \Exception('Insufficient credits');
        }

        $this->used_credits += $credits;
        $this->available_credits -= $credits;
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Check if client has enough credits
     */
    public function hasEnoughCredits(int $required): bool
    {
        return $this->available_credits >= $required;
    }

    /**
     * Get credit balance percentage
     */
    public function getBalancePercentageAttribute()
    {
        if ($this->total_credits == 0) {
            return 0;
        }
        return round(($this->available_credits / $this->total_credits) * 100);
    }

    /**
     * Check if credits are low (less than 20% of total or below 5 credits)
     */
    public function isLowCredit(int $threshold = null): bool
    {
        if ($threshold !== null) {
            return $this->available_credits <= $threshold;
        }
        
        // Consider low if less than 20% of total credits OR below 5 credits absolute
        $percentageThreshold = $this->total_credits * 0.2;
        $absoluteThreshold = 5;
        
        return $this->available_credits <= max($percentageThreshold, $absoluteThreshold) && 
               $this->total_credits > 0;
    }
}
