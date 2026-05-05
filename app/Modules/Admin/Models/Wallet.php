<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'walletable_id',
        'walletable_type',
        'balance',
        'total_credited',
        'total_debited',
        'currency',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_credited' => 'decimal:2',
        'total_debited' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
    ];

    /**
     * Get the ownable model (Client or User)
     */
    public function walletable()
    {
        return $this->morphTo();
    }

    /**
     * Get the client that owns the wallet (backward compatibility)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'walletable_id')
            ->where('walletable_type', Client::class);
    }

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'walletable_id')
            ->where('walletable_type', \App\Models\User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Credit the wallet
     */
    public function credit(float $amount, string $description, array $metadata = []): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->total_credited += $amount;
        $this->last_transaction_at = now();
        $this->save();

        return $this->transactions()->create([
            'transactable_id' => $this->walletable_id,
            'transactable_type' => $this->walletable_type,
            'transaction_reference' => $this->generateTransactionReference(),
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Debit the wallet
     */
    public function debit(float $amount, string $description, array $metadata = []): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->total_debited += $amount;
        $this->last_transaction_at = now();
        $this->save();

        return $this->transactions()->create([
            'transactable_id' => $this->walletable_id,
            'transactable_type' => $this->walletable_type,
            'transaction_reference' => $this->generateTransactionReference(),
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Generate unique transaction reference
     */
    private function generateTransactionReference(): string
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '₦' . number_format($this->balance, 2);
    }
}
