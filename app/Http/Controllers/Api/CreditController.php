<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use App\Services\CreditPurchaseService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    protected CreditPurchaseService $creditPurchaseService;
    protected PaystackService $paystackService;

    public function __construct(CreditPurchaseService $creditPurchaseService, PaystackService $paystackService)
    {
        $this->creditPurchaseService = $creditPurchaseService;
        $this->paystackService = $paystackService;
    }

    /**
     * Get client's credit balance
     */
    public function getBalance(Request $request)
    {
        try {
            $client = $request->user();
            $credits = $client->getOrCreateCredits();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_credits' => $credits->total_credits,
                    'used_credits' => $credits->used_credits,
                    'available_credits' => $credits->available_credits,
                    'balance_percentage' => $credits->balance_percentage,
                    'is_low' => $credits->isLowCredit(),
                    'last_purchase_at' => $credits->last_purchase_at,
                    'last_used_at' => $credits->last_used_at,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit balance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available subscription plans
     */
    public function getPlans()
    {
        try {
            $plans = SubscriptionPlan::active()
                ->orderBy('is_featured', 'desc')
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plans,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plans',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available credit packages
     */
    public function getPackages()
    {
        try {
            $packages = CreditPackage::active()
                ->orderBy('is_popular', 'desc')
                ->orderBy('sort_order')
                ->orderBy('credits')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $packages,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit packages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Purchase a subscription plan
     */
    public function purchasePlan(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|in:wallet,card,paystack',
        ]);

        try {
            $client = $request->user();
            $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

            if (!$plan->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription plan is no longer available',
                ], 400);
            }

            // Convert 'card' to 'paystack' for processing
            if ($validated['payment_method'] === 'card') {
                $validated['payment_method'] = 'paystack';
            }

            // Handle Paystack payment
            if ($validated['payment_method'] === 'paystack') {
                $reference = $this->paystackService->generateReference();

                // Create pending credit transaction
                $creditTransaction = \App\Modules\Admin\Models\CreditTransaction::create([
                    'client_id' => $client->id,
                    'transaction_reference' => \App\Modules\Admin\Models\CreditTransaction::generateReference(),
                    'type' => 'purchase',
                    'credits' => $plan->credits,
                    'balance_before' => $client->getOrCreateCredits()->balance,
                    'balance_after' => $client->getOrCreateCredits()->balance,
                    'subscription_plan_id' => $plan->id,
                    'amount_paid' => $plan->price,
                    'payment_method' => 'paystack',
                    'payment_reference' => $reference,
                    'description' => "Subscription Plan: {$plan->name}",
                    'metadata' => [
                        'subscription_plan_id' => $plan->id,
                        'subscription_plan_name' => $plan->name,
                        'billing_cycle' => $plan->billing_cycle,
                    ],
                ]);

                // Initialize Paystack transaction
                $paystackResponse = $this->paystackService->initializeTransaction([
                    'amount' => $plan->price * 100, // Paystack expects amount in kobo
                    'email' => $client->email,
                    'reference' => $reference,
                    'metadata' => [
                        'transaction_type' => 'subscription',
                        'plan_id' => $plan->id,
                        'client_id' => $client->id,
                        'credit_transaction_id' => $creditTransaction->id,
                    ],
                    'callback_url' => config('app.frontend_url') . '/dashboard/client/subscriptions/verify?reference=' . $reference,
                ]);

                if (!$paystackResponse['success']) {
                    $creditTransaction->status = 'failed';
                    $creditTransaction->save();

                    return response()->json([
                        'success' => false,
                        'message' => $paystackResponse['message'],
                    ], 400);
                }

                // Update transaction with Paystack data
                $creditTransaction->payment_reference = $reference;
                $creditTransaction->metadata = array_merge($creditTransaction->metadata ?? [], [
                    'paystack_data' => $paystackResponse['data'],
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                ]);
                $creditTransaction->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'authorization_url' => $paystackResponse['data']['authorization_url'],
                        'access_code' => $paystackResponse['data']['access_code'],
                        'reference' => $reference,
                        'payment_method' => 'paystack',
                    ],
                ]);
            }

            // Handle wallet payment
            $result = $this->creditPurchaseService->purchaseSubscriptionPlan(
                $client,
                $plan,
                'wallet'
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription purchased successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Purchase a credit package
     */
    public function purchasePackage(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:credit_packages,id',
            'payment_method' => 'required|in:wallet,card,paystack',
        ]);

        try {
            $client = $request->user();
            $package = CreditPackage::findOrFail($validated['package_id']);

            if (!$package->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This credit package is no longer available',
                ], 400);
            }

            // Convert 'card' to 'paystack' for processing
            if ($validated['payment_method'] === 'card') {
                $validated['payment_method'] = 'paystack';
            }

            // Handle Paystack payment
            if ($validated['payment_method'] === 'paystack') {
                $reference = $this->paystackService->generateReference();

                // Create pending credit transaction
                $creditTransaction = \App\Modules\Admin\Models\CreditTransaction::create([
                    'client_id' => $client->id,
                    'transaction_reference' => \App\Modules\Admin\Models\CreditTransaction::generateReference(),
                    'type' => 'purchase',
                    'credits' => $package->credits + $package->bonus_credits,
                    'balance_before' => $client->getOrCreateCredits()->balance,
                    'balance_after' => $client->getOrCreateCredits()->balance,
                    'credit_package_id' => $package->id,
                    'amount_paid' => $package->price,
                    'payment_method' => 'paystack',
                    'payment_reference' => $reference,
                    'description' => "Credit Package: {$package->name}",
                    'metadata' => [
                        'package_id' => $package->id,
                        'package_name' => $package->name,
                        'credits' => $package->credits,
                        'bonus_credits' => $package->bonus_credits,
                    ],
                ]);

                // Initialize Paystack transaction
                $paystackResponse = $this->paystackService->initializeTransaction([
                    'amount' => $package->price * 100, // Paystack expects amount in kobo
                    'email' => $client->email,
                    'reference' => $reference,
                    'metadata' => [
                        'transaction_type' => 'credit_package',
                        'package_id' => $package->id,
                        'client_id' => $client->id,
                        'credit_transaction_id' => $creditTransaction->id,
                    ],
                    'callback_url' => config('app.frontend_url') . '/dashboard/client/subscriptions/verify?reference=' . $reference,
                ]);

                if (!$paystackResponse['success']) {
                    $creditTransaction->status = 'failed';
                    $creditTransaction->save();

                    return response()->json([
                        'success' => false,
                        'message' => $paystackResponse['message'],
                    ], 400);
                }

                // Update transaction with Paystack data
                $creditTransaction->payment_reference = $reference;
                $creditTransaction->metadata = array_merge($creditTransaction->metadata ?? [], [
                    'paystack_data' => $paystackResponse['data'],
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                ]);
                $creditTransaction->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'authorization_url' => $paystackResponse['data']['authorization_url'],
                        'access_code' => $paystackResponse['data']['access_code'],
                        'reference' => $reference,
                        'payment_method' => 'paystack',
                    ],
                ]);
            }

            // Handle wallet payment
            $result = $this->creditPurchaseService->purchaseCreditPackage(
                $client,
                $package,
                'wallet'
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit package purchased successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify Paystack payment for credit purchase
     */
    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            $client = $request->user();

            // Find the credit transaction
            $creditTransaction = \App\Modules\Admin\Models\CreditTransaction::where('reference', $validated['reference'])
                ->where('client_id', $client->id)
                ->first();

            if (!$creditTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            // Verify with Paystack
            $paystackResponse = $this->paystackService->verifyTransaction($validated['reference']);

            if (!$paystackResponse['success']) {
                $creditTransaction->status = 'failed';
                $creditTransaction->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                ], 400);
            }

            $paymentData = $paystackResponse['data'];

            // Check if payment was successful
            if ($paymentData['status'] !== 'success') {
                $creditTransaction->status = 'failed';
                $creditTransaction->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful',
                ], 400);
            }

            // Update transaction with verification data
            $creditTransaction->status = 'completed';
            $creditTransaction->metadata = array_merge($creditTransaction->metadata ?? [], [
                'paystack_verification' => $paymentData,
                'verified_at' => now()->toIso8601String(),
            ]);
            $creditTransaction->save();

            // Complete the purchase based on transaction type
            if ($creditTransaction->transaction_type === 'subscription') {
                $plan = SubscriptionPlan::find($creditTransaction->transactable_id);
                if ($plan) {
                    $this->creditPurchaseService->purchaseSubscriptionPlan(
                        $client,
                        $plan,
                        'paystack',
                        $validated['reference']
                    );
                }
            } elseif ($creditTransaction->transaction_type === 'purchase') {
                $package = CreditPackage::find($creditTransaction->transactable_id);
                if ($package) {
                    $this->creditPurchaseService->purchaseCreditPackage(
                        $client,
                        $package,
                        'paystack',
                        $validated['reference']
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'transaction_id' => $creditTransaction->id,
                    'status' => $creditTransaction->status,
                    'amount' => $creditTransaction->amount,
                    'credits' => $creditTransaction->credits,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Credit payment verification error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get credit transaction history
     */
    public function getTransactions(Request $request)
    {
        try {
            $client = $request->user();
            
            $query = CreditTransaction::where('client_id', $client->id)
                ->with(['subscriptionPlan', 'creditPackage', 'order'])
                ->orderBy('created_at', 'desc');

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            $transactions = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active subscriptions
     */
    public function getSubscriptions(Request $request)
    {
        try {
            $client = $request->user();
            
            $subscriptions = $client->subscriptions()
                ->with('plan')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate credits required for a delivery
     */
    public function calculateDeliveryCredits(Request $request)
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string',
            'dropoff_address' => 'required|string',
            'distance' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
        ]);

        try {
            $client = $request->user();
            
            $validation = $this->creditCalculationService->validateCreditsForDelivery(
                $client,
                $validated['pickup_address'],
                $validated['dropoff_address'],
                $validated['distance'] ?? null,
                $validated['weight'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $validation,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate credits',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all delivery zones and their pricing
     */
    public function getDeliveryZones()
    {
        try {
            $zones = $this->creditCalculationService->getAllZonePricing();

            return response()->json([
                'success' => true,
                'data' => $zones,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery zones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
