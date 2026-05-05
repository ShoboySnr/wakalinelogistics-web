<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Wallet;
use App\Modules\Admin\Models\WalletTransaction;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected PaystackService $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Get wallet details
     */
    public function getWallet(Request $request)
    {
        try {
            $client = $request->user();
            $wallet = $client->getOrCreateWallet();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'formatted_balance' => $wallet->formatted_balance,
                    'total_credited' => $wallet->total_credited,
                    'total_debited' => $wallet->total_debited,
                    'currency' => $wallet->currency,
                    'is_active' => $wallet->is_active,
                    'last_transaction_at' => $wallet->last_transaction_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get wallet error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet details',
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);
            $type = $request->input('type'); // credit, debit
            $status = $request->input('status'); // pending, completed, failed

            // Get wallet transactions
            $walletTransactions = WalletTransaction::where('transactable_id', $user->id)
                ->where('transactable_type', get_class($user))
                ->orderBy('created_at', 'desc');

            if ($type) {
                $walletTransactions->where('type', $type);
            }

            if ($status) {
                $walletTransactions->where('status', $status);
            }

            $walletTransactions = $walletTransactions->get();

            // Get credit transactions
            $creditTransactions = \App\Modules\Admin\Models\CreditTransaction::where('client_id', $user->id)
                ->with(['subscriptionPlan', 'creditPackage'])
                ->orderBy('created_at', 'desc');

            if ($type) {
                // Map credit transaction types to wallet transaction types
                $creditTransactions->where(function($q) use ($type) {
                    if ($type === 'credit') {
                        $q->whereIn('type', ['refund', 'adjustment']);
                    } elseif ($type === 'debit') {
                        $q->whereIn('type', ['purchase', 'usage']);
                    }
                });
            }

            if ($status) {
                $creditTransactions->where('status', $status);
            }

            $creditTransactions = $creditTransactions->get();

            // Merge and format all transactions
            $allTransactions = collect();

            // Format wallet transactions
            foreach ($walletTransactions as $tx) {
                $allTransactions->push([
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'status' => $tx->status,
                    'description' => $tx->description,
                    'transaction_type' => 'wallet',
                    'reference' => $tx->payment_reference,
                    'created_at' => $tx->created_at,
                ]);
            }

            // Format credit transactions
            foreach ($creditTransactions as $tx) {
                // Purchase and usage transactions are debits (money/credits spent)
                $txType = 'debit';

                // Build description with plan/package name
                $description = 'Credit Transaction';
                if ($tx->type === 'purchase' && $tx->subscriptionPlan) {
                    $description = "Subscription: {$tx->subscriptionPlan->name}";
                } elseif ($tx->type === 'purchase' && $tx->creditPackage) {
                    $description = "Credit Package: {$tx->creditPackage->name}";
                } elseif ($tx->type === 'usage') {
                    $description = 'Delivery Credit Usage';
                } elseif ($tx->description) {
                    $description = $tx->description;
                }

                // Use amount_paid from transaction
                $amount = $tx->amount_paid ?? 0;

                $allTransactions->push([
                    'id' => $tx->id,
                    'type' => $txType,
                    'amount' => $amount,
                    'status' => 'completed',
                    'description' => $description,
                    'transaction_type' => 'credit',
                    'reference' => $tx->payment_reference,
                    'created_at' => $tx->created_at,
                ]);
            }

            // Sort by date descending
            $allTransactions = $allTransactions->sortByDesc('created_at')->values();

            // Manual pagination
            $total = $allTransactions->count();
            $currentPage = $request->input('page', 1);
            $lastPage = (int) ceil($total / $perPage);
            
            $paginated = $allTransactions->forPage($currentPage, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated->values(),
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get transactions error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
            ], 500);
        }
    }

    /**
     * Initialize wallet funding with Paystack
     */
    public function initializeFunding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100', // Minimum ₦100
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $wallet = $user->getOrCreateWallet();
            $amount = $request->input('amount');

            // Generate unique reference
            $reference = $this->paystackService->generateReference();

            // Create pending transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transactable_id' => $user->id,
                'transactable_type' => get_class($user),
                'transaction_reference' => $reference,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance, // Will be updated on verification
                'payment_method' => 'paystack',
                'payment_reference' => $reference,
                'status' => 'pending',
                'description' => 'Wallet funding via Paystack',
                'metadata' => [
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'user_type' => get_class($user),
                ],
            ]);

            // Initialize Paystack transaction
            $paystackResponse = $this->paystackService->initializeTransaction([
                'email' => $user->email,
                'amount' => $amount,
                'reference' => $reference,
                'callback_url' => config('services.frontend.wallet_callback_url'),
                'metadata' => [
                    'user_id' => $user->id,
                    'user_type' => get_class($user),
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'custom_fields' => [
                        [
                            'display_name' => 'User Name',
                            'variable_name' => 'user_name',
                            'value' => $user->name,
                        ],
                        [
                            'display_name' => 'Purpose',
                            'variable_name' => 'purpose',
                            'value' => 'Wallet Funding',
                        ],
                    ],
                ],
            ]);

            if (!$paystackResponse['success']) {
                $transaction->update(['status' => 'failed']);
                
                return response()->json([
                    'success' => false,
                    'message' => $paystackResponse['message'],
                ], 400);
            }

            // Update transaction with Paystack data
            $transaction->update([
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paystack_data' => $paystackResponse['data'],
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'reference' => $reference,
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code' => $paystackResponse['data']['access_code'],
                    'amount' => $amount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Initialize funding error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment',
            ], 500);
        }
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Reference is required',
            ], 422);
        }

        try {
            $reference = $request->input('reference');
            
            // Find the transaction
            $transaction = WalletTransaction::where('payment_reference', $reference)
                ->where('status', 'pending')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already processed',
                ], 404);
            }

            // Verify with Paystack
            $paystackResponse = $this->paystackService->verifyTransaction($reference);

            if (!$paystackResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                ], 400);
            }

            $paymentData = $paystackResponse['data'];

            // Check if payment was successful
            if ($paymentData['status'] !== 'success') {
                $transaction->update(['status' => 'failed']);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful',
                ], 400);
            }

            // Process the payment
            DB::beginTransaction();
            try {
                $wallet = $transaction->wallet;
                
                // Update wallet balance
                $wallet->balance += $transaction->amount;
                $wallet->total_credited += $transaction->amount;
                $wallet->last_transaction_at = now();
                $wallet->save();

                // Update transaction
                $transaction->update([
                    'status' => 'completed',
                    'balance_after' => $wallet->balance,
                    'completed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'paystack_verification' => $paymentData,
                    ]),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified and wallet credited successfully',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'new_balance' => $wallet->balance,
                        'formatted_balance' => $wallet->formatted_balance,
                    ],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Verify payment error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
            ], 500);
        }
    }

    /**
     * Paystack webhook handler
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Verify webhook signature
            $signature = $request->header('x-paystack-signature');
            $payload = $request->getContent();

            if (!$this->paystackService->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Invalid Paystack webhook signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $event = $request->input('event');
            $data = $request->input('data');

            // Handle charge.success event
            if ($event === 'charge.success') {
                $reference = $data['reference'];
                
                $transaction = WalletTransaction::where('payment_reference', $reference)
                    ->where('status', 'pending')
                    ->first();

                if ($transaction && $data['status'] === 'success') {
                    DB::beginTransaction();
                    try {
                        $wallet = $transaction->wallet;
                        
                        $wallet->balance += $transaction->amount;
                        $wallet->total_credited += $transaction->amount;
                        $wallet->last_transaction_at = now();
                        $wallet->save();

                        $transaction->update([
                            'status' => 'completed',
                            'balance_after' => $wallet->balance,
                            'completed_at' => now(),
                            'metadata' => array_merge($transaction->metadata ?? [], [
                                'webhook_data' => $data,
                            ]),
                        ]);

                        DB::commit();
                        
                        Log::info('Wallet funded via webhook', [
                            'transaction_id' => $transaction->id,
                            'amount' => $transaction->amount,
                        ]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Webhook processing error', ['error' => $e->getMessage()]);
                    }
                }
            }

            return response()->json(['message' => 'Webhook received'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook handler error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get Paystack public key
     */
    public function getPaystackPublicKey()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => $this->paystackService->getPublicKey(),
            ],
        ]);
    }
}
