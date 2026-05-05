<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'transactable_id',
        'transactable_type',
        'transaction_reference',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'payment_method',
        'payment_reference',
        'status',
        'description',
        'metadata',
        'order_id',
        'processed_by',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the ownable model (Client or User)
     */
    public function transactable()
    {
        return $this->morphTo();
    }

    /**
     * Get the client that owns the transaction (backward compatibility)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'transactable_id')
            ->where('transactable_type', Client::class);
    }

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transactable_id')
            ->where('transactable_type', User::class);
    }

    /**
     * Get the order associated with the transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who processed the transaction
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get transaction type badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return $this->type === 'credit' ? 'green' : 'red';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'reversed' => 'gray',
            default => 'gray',
        };
    }
}
