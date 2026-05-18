<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use App\Services\CreditCalculationService;
use App\Services\CreditPurchaseService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    protected CreditPurchaseService $creditPurchaseService;
    protected PaystackService $paystackService;
    protected CreditCalculationService $creditCalculationService;

    public function __construct(
        CreditPurchaseService $creditPurchaseService,
        PaystackService $paystackService,
        CreditCalculationService $creditCalculationService
    ) {
        $this->creditPurchaseService = $creditPurchaseService;
        $this->paystackService = $paystackService;
        $this->creditCalculationService = $creditCalculationService;
    }

    /**
     * Get live platform stats publicly (no auth required)
     */
    public function getPublicStats()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'deliveries_completed' => \App\Modules\Admin\Models\Order::where('status', 'delivered')->count(),
                    'active_clients'       => \App\Modules\Admin\Models\Client::where('is_active', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch stats'], 500);
        }
    }

    /**
     * Get active subscription plans publicly (no auth required)
     */
    public function getPublicPlans()
    {
        try {
            $plans = SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get(['id', 'name', 'slug', 'description', 'credits', 'price', 'billing_cycle', 'validity_days', 'features', 'is_featured']);

            return response()->json(['success' => true, 'data' => $plans]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch plans'], 500);
        }
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
            'payment_method' => 'required|in:card,paystack',
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
                $currentCredits = $client->getOrCreateCredits();

                // Create pending credit transaction
                $creditTransaction = CreditTransaction::create([
                    'client_id' => $client->id,
                    'transaction_reference' => CreditTransaction::generateReference(),
                    'type' => 'purchase',
                    'credits' => $plan->credits,
                    'balance_before' => $currentCredits->available_credits,
                    'balance_after' => $currentCredits->available_credits,
                    'subscription_plan_id' => $plan->id,
                    'amount_paid' => $plan->price,
                    'payment_method' => 'paystack',
                    'payment_reference' => $reference,
                    'status' => 'pending',
                    'description' => "Subscription Plan: {$plan->name}",
                    'metadata' => [
                        'subscription_plan_id' => $plan->id,
                        'subscription_plan_name' => $plan->name,
                        'billing_cycle' => $plan->billing_cycle,
                    ],
                ]);

                // Initialize Paystack transaction
                // NOTE: PaystackService::initializeTransaction() converts to kobo internally — do NOT pre-multiply
                $paystackResponse = $this->paystackService->initializeTransaction([
                    'amount' => $plan->price,
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

            return response()->json(['success' => false, 'message' => 'Invalid payment method'], 400);
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
            'payment_method' => 'required|in:card,paystack',
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
                $currentCredits = $client->getOrCreateCredits();

                // Create pending credit transaction
                $creditTransaction = CreditTransaction::create([
                    'client_id' => $client->id,
                    'transaction_reference' => CreditTransaction::generateReference(),
                    'type' => 'purchase',
                    'credits' => $package->credits + $package->bonus_credits,
                    'balance_before' => $currentCredits->available_credits,
                    'balance_after' => $currentCredits->available_credits,
                    'credit_package_id' => $package->id,
                    'amount_paid' => $package->price,
                    'payment_method' => 'paystack',
                    'payment_reference' => $reference,
                    'status' => 'pending',
                    'description' => "Credit Package: {$package->name}",
                    'metadata' => [
                        'package_id' => $package->id,
                        'package_name' => $package->name,
                        'credits' => $package->credits,
                        'bonus_credits' => $package->bonus_credits,
                    ],
                ]);

                // Initialize Paystack transaction
                // NOTE: PaystackService::initializeTransaction() converts to kobo internally — do NOT pre-multiply
                $paystackResponse = $this->paystackService->initializeTransaction([
                    'amount' => $package->price,
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

            return response()->json(['success' => false, 'message' => 'Invalid payment method'], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Purchase custom amount of credits
     */
    public function purchaseCustomAmount(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:5000',
            'payment_method' => 'required|in:card,paystack',
        ]);

        try {
            $client = $request->user();
            $amount = $validated['amount'];
            $credits = $amount; // 1:1 ratio for custom purchases

            // Convert 'card' to 'paystack' for processing
            if ($validated['payment_method'] === 'card') {
                $validated['payment_method'] = 'paystack';
            }

            // Handle Paystack payment
            if ($validated['payment_method'] === 'paystack') {
                $reference = $this->paystackService->generateReference();
                $currentCredits = $client->getOrCreateCredits();

                // Create pending credit transaction
                $creditTransaction = CreditTransaction::create([
                    'client_id' => $client->id,
                    'transaction_reference' => CreditTransaction::generateReference(),
                    'type' => 'purchase',
                    'credits' => $credits,
                    'balance_before' => $currentCredits->available_credits,
                    'balance_after' => $currentCredits->available_credits,
                    'amount_paid' => $amount,
                    'payment_method' => 'paystack',
                    'payment_reference' => $reference,
                    'status' => 'pending',
                    'description' => "Custom credit purchase: ₦" . number_format($amount),
                    'metadata' => [
                        'purchase_type' => 'custom',
                        'amount' => $amount,
                        'credits' => $credits,
                    ],
                ]);

                // Initialize Paystack transaction
                // NOTE: PaystackService::initializeTransaction() converts to kobo internally — do NOT pre-multiply
                $paystackResponse = $this->paystackService->initializeTransaction([
                    'amount' => $amount,
                    'email' => $client->email,
                    'reference' => $reference,
                    'metadata' => [
                        'transaction_type' => 'custom_credits',
                        'client_id' => $client->id,
                        'credit_transaction_id' => $creditTransaction->id,
                        'credits' => $credits,
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
                        'amount' => $amount,
                        'credits' => $credits,
                    ],
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid payment method'], 400);
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

            // Find the pending credit transaction by payment reference
            $creditTransaction = CreditTransaction::where('payment_reference', $validated['reference'])
                ->where('client_id', $client->id)
                ->where('status', 'pending')
                ->first();

            if (!$creditTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already processed',
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

            if ($paymentData['status'] !== 'success') {
                $creditTransaction->status = 'failed';
                $creditTransaction->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful',
                ], 400);
            }

            // Mark transaction as completed
            $creditTransaction->status = 'completed';
            $creditTransaction->metadata = array_merge($creditTransaction->metadata ?? [], [
                'paystack_verification' => $paymentData,
                'verified_at' => now()->toIso8601String(),
            ]);
            $creditTransaction->save();

            // Complete the purchase based on what the pending transaction references
            if ($creditTransaction->subscription_plan_id) {
                $plan = SubscriptionPlan::find($creditTransaction->subscription_plan_id);
                if ($plan) {
                    $this->creditPurchaseService->purchaseSubscriptionPlan(
                        $client,
                        $plan,
                        'paystack',
                        $validated['reference']
                    );
                }
            } elseif ($creditTransaction->credit_package_id) {
                $package = CreditPackage::find($creditTransaction->credit_package_id);
                if ($package) {
                    $this->creditPurchaseService->purchaseCreditPackage(
                        $client,
                        $package,
                        'paystack',
                        $validated['reference']
                    );
                }
            } else {
                // Custom amount — add credits directly
                $clientCredit = $client->getOrCreateCredits();
                $clientCredit->addCredits($creditTransaction->credits);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'transaction_id' => $creditTransaction->id,
                    'status' => $creditTransaction->status,
                    'amount' => $creditTransaction->amount_paid,
                    'credits' => $creditTransaction->credits,
                    'credits_added' => $creditTransaction->credits,
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

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            $transactions = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
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
                ->with(['plan' => fn($q) => $q->withTrashed()])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery zones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
