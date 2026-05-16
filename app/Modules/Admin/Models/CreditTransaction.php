<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'transaction_reference',
        'type',
        'status',
        'credits',
        'balance_before',
        'balance_after',
        'subscription_plan_id',
        'credit_package_id',
        'order_id',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'description',
        'metadata',
        'processed_by',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the client that owns the transaction
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the subscription plan
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the credit package
     */
    public function creditPackage()
    {
        return $this->belongsTo(CreditPackage::class);
    }

    /**
     * Get the related order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who processed this transaction
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for purchase transactions
     */
    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    /**
     * Scope for usage transactions
     */
    public function scopeUsages($query)
    {
        return $query->where('type', 'usage');
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        return 'CRD-' . strtoupper(uniqid()) . '-' . time();
    }
}
