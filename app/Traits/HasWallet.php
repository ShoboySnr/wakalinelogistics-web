<?php

namespace App\Traits;

use App\Modules\Admin\Models\Wallet;
use App\Modules\Admin\Models\WalletTransaction;

trait HasWallet
{
    /**
     * Get the model's wallet
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    /**
     * Get the model's wallet transactions
     */
    public function walletTransactions()
    {
        return $this->morphMany(WalletTransaction::class, 'transactable');
    }

    /**
     * Get or create wallet for the model
     */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet()->firstOrCreate(
            [
                'walletable_id' => $this->id,
                'walletable_type' => self::class,
            ],
            [
                'balance' => 0,
                'currency' => 'NGN',
                'is_active' => true,
            ]
        );
    }

    /**
     * Credit the wallet
     */
    public function creditWallet(float $amount, string $description, array $metadata = []): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->credit($amount, $description, $metadata);
    }

    /**
     * Debit the wallet
     */
    public function debitWallet(float $amount, string $description, array $metadata = []): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->debit($amount, $description, $metadata);
    }

    /**
     * Get wallet balance
     */
    public function getWalletBalance(): float
    {
        $wallet = $this->wallet;
        return $wallet ? $wallet->balance : 0.00;
    }

    /**
     * Check if has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        $wallet = $this->wallet;
        return $wallet ? $wallet->hasSufficientBalance($amount) : false;
    }
}
