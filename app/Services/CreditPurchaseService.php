<?php

namespace App\Services;

use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use App\Modules\Admin\Models\ClientCredit;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\ClientSubscription;
use App\Modules\Admin\Models\CreditSetting;
use Illuminate\Support\Facades\DB;
use Exception;

class CreditPurchaseService
{
    /**
     * Purchase a subscription plan
     */
    public function purchaseSubscriptionPlan(
        Client $client,
        SubscriptionPlan $plan,
        string $paymentMethod = 'wallet',
        ?string $paymentReference = null
    ) {
        DB::beginTransaction();
        try {
            // Check if using wallet payment
            if ($paymentMethod === 'wallet') {
                $wallet = $client->wallet;
                
                if (!$wallet || $wallet->balance < $plan->price) {
                    throw new Exception('Insufficient wallet balance');
                }

                // Deduct from wallet
                $wallet->balance -= $plan->price;
                $wallet->total_debited += $plan->price;
                $wallet->last_transaction_at = now();
                $wallet->save();

                // Create wallet transaction
                \App\Modules\Admin\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_reference' => 'WKL-' . strtoupper(uniqid()) . '-' . time(),
                    'payment_reference' => $paymentReference ?? 'SUBSCRIPTION-' . $plan->id . '-' . time(),
                    'type' => 'debit',
                    'amount' => $plan->price,
                    'balance_before' => $wallet->balance + $plan->price,
                    'balance_after' => $wallet->balance,
                    'payment_method' => 'wallet',
                    'status' => 'completed',
                    'description' => "Subscription purchase: {$plan->name}",
                    'completed_at' => now(),
                    'transactable_type' => SubscriptionPlan::class,
                    'transactable_id' => $plan->id,
                    'metadata' => [
                        'subscription_plan_id' => $plan->id,
                        'subscription_plan_name' => $plan->name,
                        'credits_purchased' => $plan->credits,
                    ],
                ]);
            }

            // Calculate subscription dates
            $startsAt = now();
            $expiresAt = null;
            
            if ($plan->validity_days) {
                $expiresAt = now()->addDays($plan->validity_days);
            } elseif ($plan->billing_cycle !== 'one-time') {
                switch ($plan->billing_cycle) {
                    case 'daily':
                        $expiresAt = now()->addDay();
                        break;
                    case 'weekly':
                        $expiresAt = now()->addWeek();
                        break;
                    case 'monthly':
                        $expiresAt = now()->addMonth();
                        break;
                    case 'quarterly':
                        $expiresAt = now()->addMonths(3);
                        break;
                    case 'yearly':
                        $expiresAt = now()->addYear();
                        break;
                }
            }

            // Create subscription
            $subscription = ClientSubscription::create([
                'client_id' => $client->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'credits_allocated' => $plan->credits,
                'credits_used' => 0,
                'amount_paid' => $plan->price,
                'payment_reference' => $paymentReference ?? 'SUB-' . time(),
                'auto_renew' => false,
            ]);

            // Add credits to client account
            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;
            
            $clientCredit->addCredits($plan->credits);

            // Create credit transaction
            $creditExpiry = $expiresAt ?? ($plan->validity_days ? now()->addDays($plan->validity_days) : null);
            
            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'purchase',
                'credits' => $plan->credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'subscription_plan_id' => $plan->id,
                'amount_paid' => $plan->price,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference ?? 'SUB-' . time(),
                'description' => "Purchased subscription: {$plan->name} ({$plan->credits} credits)",
                'expires_at' => $creditExpiry,
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'billing_cycle' => $plan->billing_cycle,
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'subscription' => $subscription,
                'credits_added' => $plan->credits,
                'new_balance' => $clientCredit->available_credits,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Purchase a credit package
     */
    public function purchaseCreditPackage(
        Client $client,
        CreditPackage $package,
        string $paymentMethod = 'wallet',
        ?string $paymentReference = null
    ) {
        DB::beginTransaction();
        try {
            // Check if using wallet payment
            if ($paymentMethod === 'wallet') {
                $wallet = $client->wallet;
                
                if (!$wallet || $wallet->balance < $package->price) {
                    throw new Exception('Insufficient wallet balance');
                }

                // Deduct from wallet
                $wallet->balance -= $package->price;
                $wallet->total_debited += $package->price;
                $wallet->last_transaction_at = now();
                $wallet->save();

                // Create wallet transaction
                \App\Modules\Admin\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_reference' => 'WKL-' . strtoupper(uniqid()) . '-' . time(),
                    'payment_reference' => $paymentReference ?? 'CREDITS-' . $package->id . '-' . time(),
                    'type' => 'debit',
                    'amount' => $package->price,
                    'balance_before' => $wallet->balance + $package->price,
                    'balance_after' => $wallet->balance,
                    'payment_method' => 'wallet',
                    'status' => 'completed',
                    'description' => "Credit package purchase: {$package->name}",
                    'completed_at' => now(),
                    'transactable_type' => CreditPackage::class,
                    'transactable_id' => $package->id,
                    'metadata' => [
                        'credit_package_id' => $package->id,
                        'credit_package_name' => $package->name,
                        'credits_purchased' => $package->total_credits,
                    ],
                ]);
            }

            // Add credits to client account
            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;
            
            $totalCredits = $package->credits + $package->bonus_credits;
            $clientCredit->addCredits($totalCredits);

            // Create credit transaction
            $creditExpiry = $package->validity_days ? now()->addDays($package->validity_days) : null;
            
            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'purchase',
                'credits' => $totalCredits,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'credit_package_id' => $package->id,
                'amount_paid' => $package->price,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference ?? 'PKG-' . time(),
                'description' => "Purchased credit package: {$package->name} ({$package->credits} credits" . 
                    ($package->bonus_credits > 0 ? " + {$package->bonus_credits} bonus" : "") . ")",
                'expires_at' => $creditExpiry,
                'metadata' => [
                    'base_credits' => $package->credits,
                    'bonus_credits' => $package->bonus_credits,
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'credits_added' => $totalCredits,
                'base_credits' => $package->credits,
                'bonus_credits' => $package->bonus_credits,
                'new_balance' => $clientCredit->available_credits,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deduct credits for an order
     */
    public function deductCreditsForOrder(Client $client, int $orderId, int $creditsRequired = null)
    {
        $creditsRequired = $creditsRequired ?? CreditSetting::get('credits_per_delivery', 1);
        
        DB::beginTransaction();
        try {
            $clientCredit = $client->getOrCreateCredits();
            
            if (!$clientCredit->hasEnoughCredits($creditsRequired)) {
                throw new Exception('Insufficient credits. Please purchase more credits to continue.');
            }

            $balanceBefore = $clientCredit->available_credits;
            $clientCredit->deductCredits($creditsRequired);

            // Create credit transaction
            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'usage',
                'credits' => -$creditsRequired,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'order_id' => $orderId,
                'description' => "Credits used for order #{$orderId}",
                'metadata' => [
                    'order_id' => $orderId,
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'credits_deducted' => $creditsRequired,
                'remaining_balance' => $clientCredit->available_credits,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Refund credits for a cancelled order
     */
    public function refundCreditsForOrder(Client $client, int $orderId, int $creditsToRefund)
    {
        DB::beginTransaction();
        try {
            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;
            
            $clientCredit->addCredits($creditsToRefund);

            // Create credit transaction
            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'refund',
                'credits' => $creditsToRefund,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'order_id' => $orderId,
                'description' => "Credits refunded for cancelled order #{$orderId}",
                'metadata' => [
                    'order_id' => $orderId,
                    'reason' => 'order_cancelled',
                ],
            ]);

            DB::commit();

            return [
                'success' => true,
                'credits_refunded' => $creditsToRefund,
                'new_balance' => $clientCredit->available_credits,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
