<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Client;
use App\Services\CreditPurchaseService;
use App\Services\PaystackService;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientOrderController extends Controller
{
    protected CreditPurchaseService $creditPurchaseService;
    protected DeliveryPriceService $deliveryPriceService;
    protected PaystackService $paystackService;

    public function __construct(
        CreditPurchaseService $creditPurchaseService,
        DeliveryPriceService $deliveryPriceService,
        PaystackService $paystackService
    ) {
        $this->creditPurchaseService = $creditPurchaseService;
        $this->deliveryPriceService = $deliveryPriceService;
        $this->paystackService = $paystackService;
    }

    /**
     * Create a new order (Credits, Wallet, or Cash payment)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'sender_name'         => 'required|string|max:255',
            'sender_phone'        => 'required|string|max:20',
            'sender_email'        => 'nullable|email|max:255',
            'pickup_address'      => 'required|string',
            'receiver_name'       => 'required|string|max:255',
            'receiver_phone'      => 'required|string|max:20',
            'receiver_email'      => 'nullable|email|max:255',
            'delivery_address'    => 'required|string',
            'package_description' => 'required|string',
            'package_quantity'    => 'required|integer|min:1',
            'payment_method'      => 'nullable|in:credits,cash,wallet',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $orderNumber   = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $paymentMethod = $request->input('payment_method', 'cash');

            $priceData = $this->deliveryPriceService->processDeliveryCalculation(
                $request->pickup_address,
                $request->delivery_address
            );

            if (isset($priceData['error'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $priceData['error']], 400);
            }

            $deliveryFee = $priceData['delivery_fee'];
            $distance    = $priceData['distance_km'];

            // --- Credits payment: validate before creating the order ---
            if ($paymentMethod === 'credits') {
                $clientCredit = $user->getOrCreateCredits();
                if (!$clientCredit->hasEnoughCredits($deliveryFee)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient credits',
                        'data'    => [
                            'required_credits'  => $deliveryFee,
                            'available_credits' => $clientCredit->available_credits,
                            'shortfall'         => $deliveryFee - $clientCredit->available_credits,
                        ],
                    ], 400);
                }
            }

            // --- Wallet payment: validate before creating the order ---
            if ($paymentMethod === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                if (!$wallet->hasSufficientBalance($deliveryFee)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient wallet balance',
                        'data'    => [
                            'required'  => $deliveryFee,
                            'available' => $wallet->balance,
                            'shortfall' => $deliveryFee - $wallet->balance,
                        ],
                    ], 400);
                }
            }

            // Create the order
            $order = Order::create([
                'order_number'        => $orderNumber,
                'client_id'           => $user->id,
                'customer_name'       => $user->name ?? $request->sender_name,
                'customer_phone'      => $user->phone ?? $request->sender_phone,
                'sender_name'         => $request->sender_name,
                'sender_phone'        => $request->sender_phone,
                'sender_email'        => $request->sender_email,
                'pickup_address'      => $request->pickup_address,
                'receiver_name'       => $request->receiver_name,
                'receiver_phone'      => $request->receiver_phone,
                'receiver_email'      => $request->receiver_email,
                'delivery_address'    => $request->delivery_address,
                'package_description' => $request->package_description,
                'package_size'        => 'medium',
                'package_weight'      => '0',
                'package_quantity'    => $request->package_quantity,
                'pickup_date'         => now()->addDay()->format('Y-m-d'),
                'delivery_date'       => now()->addDays(2)->format('Y-m-d'),
                'distance'            => $distance,
                'price'               => $deliveryFee,
                'status'              => 'pending',
                'payment_method'      => $paymentMethod,
                'paid_with_credits'   => $paymentMethod === 'credits',
            ]);

            $result = [];

            // --- Deduct credits ---
            if ($paymentMethod === 'credits') {
                $result = $this->creditPurchaseService->deductCreditsForOrder(
                    $user,
                    $order->id,
                    $deliveryFee
                );
                $order->update(['credits_used' => $deliveryFee]);
            }

            // --- Deduct from wallet ---
            if ($paymentMethod === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                $wallet->debit(
                    $deliveryFee,
                    "Order payment: #{$orderNumber}",
                    ['order_id' => $order->id, 'order_number' => $orderNumber]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data'    => [
                    'order'             => $order,
                    'payment_method'    => $paymentMethod,
                    'credits_used'      => $paymentMethod === 'credits' ? $deliveryFee : 0,
                    'remaining_credits' => $paymentMethod === 'credits' ? ($result['remaining_balance'] ?? null) : null,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment for a card order.
     * Stores the order data in cache, returns a Paystack authorization URL.
     * The order is NOT created yet — it is created in verifyCardPayment() after confirmed payment.
     */
    public function initializeCardPayment(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'sender_name'         => 'required|string|max:255',
            'sender_phone'        => 'required|string|max:20',
            'sender_email'        => 'nullable|email|max:255',
            'pickup_address'      => 'required|string',
            'receiver_name'       => 'required|string|max:255',
            'receiver_phone'      => 'required|string|max:20',
            'receiver_email'      => 'nullable|email|max:255',
            'delivery_address'    => 'required|string',
            'package_description' => 'required|string',
            'package_quantity'    => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $priceData = $this->deliveryPriceService->processDeliveryCalculation(
                $request->pickup_address,
                $request->delivery_address
            );

            if (isset($priceData['error'])) {
                return response()->json(['success' => false, 'message' => $priceData['error']], 400);
            }

            $deliveryFee = $priceData['delivery_fee'];
            $distance    = $priceData['distance_km'];
            $reference   = $this->paystackService->generateReference();

            // Cache the order data for 30 minutes — retrieved after Paystack confirms payment
            Cache::put('card_order_' . $reference, [
                'client_id'           => $user->id,
                'sender_name'         => $request->sender_name,
                'sender_phone'        => $request->sender_phone,
                'sender_email'        => $request->sender_email,
                'pickup_address'      => $request->pickup_address,
                'receiver_name'       => $request->receiver_name,
                'receiver_phone'      => $request->receiver_phone,
                'receiver_email'      => $request->receiver_email,
                'delivery_address'    => $request->delivery_address,
                'package_description' => $request->package_description,
                'package_quantity'    => $request->package_quantity,
                'delivery_fee'        => $deliveryFee,
                'distance'            => $distance,
            ], now()->addMinutes(30));

            $paystackResponse = $this->paystackService->initializeTransaction([
                'amount'       => $deliveryFee,
                'email'        => $user->email,
                'reference'    => $reference,
                'metadata'     => [
                    'transaction_type' => 'order_payment',
                    'client_id'        => $user->id,
                ],
                'callback_url' => config('app.frontend_url') . '/dashboard/client/orders/verify?reference=' . $reference,
            ]);

            if (!$paystackResponse['success']) {
                Cache::forget('card_order_' . $reference);
                return response()->json([
                    'success' => false,
                    'message' => $paystackResponse['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized. Redirect to Paystack.',
                'data'    => [
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'access_code'       => $paystackResponse['data']['access_code'],
                    'reference'         => $reference,
                    'amount'            => $deliveryFee,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Card order initialize error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Paystack payment and create the order.
     * Called after Paystack redirects back to the frontend.
     */
    public function verifyCardPayment(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment reference is required',
            ], 422);
        }

        $reference = $request->input('reference');

        // Retrieve cached order data
        $orderData = Cache::get('card_order_' . $reference);

        if (!$orderData) {
            return response()->json([
                'success' => false,
                'message' => 'Order session expired or not found. Please try placing your order again.',
            ], 404);
        }

        // Ensure the cached order belongs to this client
        if ($orderData['client_id'] !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify payment with Paystack
        $paystackResponse = $this->paystackService->verifyTransaction($reference);

        if (!$paystackResponse['success'] || $paystackResponse['data']['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please contact support if you were charged.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $orderNumber = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));

            $order = Order::create([
                'order_number'        => $orderNumber,
                'client_id'           => $user->id,
                'customer_name'       => $user->name ?? $orderData['sender_name'],
                'customer_phone'      => $user->phone ?? $orderData['sender_phone'],
                'sender_name'         => $orderData['sender_name'],
                'sender_phone'        => $orderData['sender_phone'],
                'sender_email'        => $orderData['sender_email'],
                'pickup_address'      => $orderData['pickup_address'],
                'receiver_name'       => $orderData['receiver_name'],
                'receiver_phone'      => $orderData['receiver_phone'],
                'receiver_email'      => $orderData['receiver_email'],
                'delivery_address'    => $orderData['delivery_address'],
                'package_description' => $orderData['package_description'],
                'package_size'        => 'medium',
                'package_weight'      => '0',
                'package_quantity'    => $orderData['package_quantity'],
                'pickup_date'         => now()->addDay()->format('Y-m-d'),
                'delivery_date'       => now()->addDays(2)->format('Y-m-d'),
                'distance'            => $orderData['distance'],
                'price'               => $orderData['delivery_fee'],
                'status'              => 'confirmed',
                'payment_method'      => 'card',
                'paid_with_credits'   => false,
                'payment_reference'   => $reference,
            ]);

            // Clear the cache entry now the order is created
            Cache::forget('card_order_' . $reference);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and order created successfully!',
                'data'    => [
                    'order'      => $order,
                    'reference'  => $reference,
                    'amount_paid' => $orderData['delivery_fee'],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Card order creation error after payment: ' . $e->getMessage(), [
                'reference' => $reference,
                'client_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment was received but order creation failed. Please contact support with reference: ' . $reference,
            ], 500);
        }
    }

    /**
     * Get authenticated user's orders
     */
    public function myOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $isClient = $user instanceof Client;

        $orders = $isClient
            ? Order::where('client_id', $user->id)->orderBy('created_at', 'desc')->get()
            : Order::where('sender_email', $user->email)->orWhere('receiver_email', $user->email)->orderBy('created_at', 'desc')->get();

        $stats = [
            'total'      => $orders->count(),
            'pending'    => $orders->where('status', 'pending')->count(),
            'confirmed'  => $orders->where('status', 'confirmed')->count(),
            'in_transit' => $orders->whereIn('status', ['picked_up', 'in_transit'])->count(),
            'delivered'  => $orders->where('status', 'delivered')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data'    => ['orders' => $orders, 'stats' => $stats],
        ]);
    }

    /**
     * Get single order detail
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $isClient = $user instanceof Client;

        $order = Order::where('id', $id)
            ->where(function ($query) use ($user, $isClient) {
                if ($isClient) {
                    $query->where('client_id', $user->id);
                } else {
                    $query->where('sender_email', $user->email)->orWhere('receiver_email', $user->email);
                }
            })
            ->with('rider')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data'    => $order,
        ]);
    }

    /**
     * Get today's orders + unfulfilled orders from previous days
     */
    public function todayOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $isClient = $user instanceof Client;

        $orders = Order::where(function ($query) use ($user, $isClient) {
                if ($isClient) {
                    $query->where('client_id', $user->id);
                } else {
                    $query->where('sender_email', $user->email)->orWhere('receiver_email', $user->email);
                }
            })
            ->with('rider')
            ->where(function ($query) {
                $query->whereDate('created_at', today())
                    ->orWhere(function ($q) {
                        $q->whereDate('created_at', '<', today())
                          ->whereNotIn('status', ['delivered', 'cancelled']);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total'      => $orders->count(),
            'pending'    => $orders->whereIn('status', ['pending', 'confirmed'])->count(),
            'in_transit' => $orders->whereIn('status', ['in_transit', 'picked_up'])->count(),
            'delivered'  => $orders->where('status', 'delivered')->count(),
            'cancelled'  => $orders->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => "Today's orders retrieved successfully",
            'data'    => ['orders' => $orders, 'stats' => $stats],
        ]);
    }
}
