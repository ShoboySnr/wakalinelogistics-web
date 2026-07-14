<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ClientCustomer;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            'pickup_city'         => 'nullable|string|max:255',
            'receiver_name'       => 'required|string|max:255',
            'receiver_phone'      => 'required|string|max:20',
            'receiver_email'      => 'nullable|email|max:255',
            'delivery_address'    => 'required|string',
            'delivery_city'       => 'nullable|string|max:255',
            'package_description' => 'required|string',
            'package_quantity'    => 'required|integer|min:1',
            'payment_method'      => 'nullable|in:credits,cash',
            'use_credits'         => 'nullable|boolean',
            'credits_amount'      => 'nullable|numeric|min:1',
            'estimated_price'     => 'nullable|numeric|min:0',
            'estimated_distance'  => 'nullable|numeric|min:0',
            'package_image_1'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_2'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_3'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_4'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $useCredits    = (bool) $request->input('use_credits', false);
        $creditsAmount = (float) $request->input('credits_amount', 0);

        DB::beginTransaction();
        try {
            $orderNumber   = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $paymentMethod = $request->input('payment_method', 'cash');

            // Build calculation address: combine street + city for better zone detection
            $pickupForCalc   = $this->buildCalcAddress($request->pickup_address, $request->pickup_city);
            $deliveryForCalc = $this->buildCalcAddress($request->delivery_address, $request->delivery_city);

            $priceData = $this->deliveryPriceService->processDeliveryCalculation(
                $pickupForCalc,
                $deliveryForCalc
            );

            if (isset($priceData['error'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $priceData['error']], 400);
            }

            $deliveryFee = $priceData['delivery_fee'];
            $distance    = $priceData['distance_km'];

            // Price change detection: if frontend estimated price differs by >10%, notify before charging
            $estimatedPrice = $request->input('estimated_price');
            if ($estimatedPrice !== null && $estimatedPrice > 0) {
                $priceDiff = abs($deliveryFee - $estimatedPrice);
                if (($priceDiff / $estimatedPrice) > 0.10) {
                    DB::rollBack();
                    return response()->json([
                        'success'       => false,
                        'price_changed' => true,
                        'message'       => 'Delivery fee has been updated. Please review the new price.',
                        'data'          => [
                            'old_price' => (float) $estimatedPrice,
                            'new_price' => $deliveryFee,
                        ],
                    ], 422);
                }
            }

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

            // --- Partial credits with cash: validate credit portion ---
            if ($paymentMethod === 'cash' && $useCredits && $creditsAmount > 0) {
                $clientCredit = $user->getOrCreateCredits();
                $creditsAmount = min($creditsAmount, $clientCredit->available_credits, $deliveryFee);
                if ($creditsAmount <= 0) {
                    $useCredits = false;
                } elseif (!$clientCredit->hasEnoughCredits($creditsAmount)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient credits for the requested credit portion',
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
                'pickup_city'         => $request->pickup_city,
                'receiver_name'       => $request->receiver_name,
                'receiver_phone'      => $request->receiver_phone,
                'receiver_email'      => $request->receiver_email,
                'delivery_address'    => $request->delivery_address,
                'delivery_city'       => $request->delivery_city,
                'package_description' => $request->package_description,
                'package_size'        => 'medium',
                'package_weight'      => '0',
                'package_quantity'    => $request->package_quantity,
                'distance'            => $distance,
                'price'               => $deliveryFee,
                'status'              => 'pending',
                'payment_method'      => $paymentMethod,
                'paid_with_credits'   => $paymentMethod === 'credits',
            ]);

            $result = [];

            // --- Deduct credits (full credits payment) ---
            if ($paymentMethod === 'credits') {
                $result = $this->creditPurchaseService->deductCreditsForOrder(
                    $user,
                    $order->id,
                    $deliveryFee
                );
                $order->update(['credits_used' => $deliveryFee]);
            }

            // --- Deduct partial credits (cash + credits) ---
            if ($paymentMethod === 'cash' && $useCredits && $creditsAmount > 0) {
                $result = $this->creditPurchaseService->deductCreditsForOrder(
                    $user,
                    $order->id,
                    $creditsAmount
                );
                $order->update(['credits_used' => $creditsAmount]);
            }

            // --- Store package images if uploaded ---
            $imageUpdates = [];
            foreach (['package_image_1', 'package_image_2', 'package_image_3', 'package_image_4'] as $field) {
                if ($request->hasFile($field)) {
                    $file     = $request->file($field);
                    $filename = $field . '_' . $order->order_number . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $path     = $file->storeAs('orders/packages', $filename, 'public');
                    $imageUpdates[$field] = $path;
                }
            }
            if (!empty($imageUpdates)) {
                $order->update($imageUpdates);
            }

            // --- Register / update customer record ---
            ClientCustomer::upsertFromOrder(
                $user->id,
                $request->receiver_name,
                $request->receiver_phone,
                $request->receiver_email,
                $request->delivery_address,
                $deliveryFee,
                $order->created_at ?? now()
            );

            DB::commit();

            $this->sendOrderPlacedEmail($user, $order);

            $creditsUsed      = $paymentMethod === 'credits' ? $deliveryFee : ($useCredits ? $creditsAmount : 0);
            $remainingCredits = !empty($result['remaining_balance']) ? $result['remaining_balance'] : null;

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data'    => [
                    'order'             => $order,
                    'payment_method'    => $paymentMethod,
                    'credits_used'      => $creditsUsed,
                    'remaining_credits' => $remainingCredits,
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
            'pickup_city'         => 'nullable|string|max:255',
            'receiver_name'       => 'required|string|max:255',
            'receiver_phone'      => 'required|string|max:20',
            'receiver_email'      => 'nullable|email|max:255',
            'delivery_address'    => 'required|string',
            'delivery_city'       => 'nullable|string|max:255',
            'package_description' => 'required|string',
            'package_quantity'    => 'required|integer|min:1',
            'use_credits'         => 'nullable|boolean',
            'credits_amount'      => 'nullable|numeric|min:1',
            'estimated_price'     => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $useCredits    = (bool) $request->input('use_credits', false);
        $creditsAmount = (float) $request->input('credits_amount', 0);

        try {
            $pickupForCalc   = $this->buildCalcAddress($request->pickup_address, $request->pickup_city);
            $deliveryForCalc = $this->buildCalcAddress($request->delivery_address, $request->delivery_city);

            $priceData = $this->deliveryPriceService->processDeliveryCalculation(
                $pickupForCalc,
                $deliveryForCalc
            );

            if (isset($priceData['error'])) {
                return response()->json(['success' => false, 'message' => $priceData['error']], 400);
            }

            $deliveryFee = $priceData['delivery_fee'];
            $distance    = $priceData['distance_km'];

            $estimatedPrice = $request->input('estimated_price');
            if ($estimatedPrice !== null && $estimatedPrice > 0) {
                $priceDiff = abs($deliveryFee - $estimatedPrice);
                if (($priceDiff / $estimatedPrice) > 0.10) {
                    return response()->json([
                        'success'       => false,
                        'price_changed' => true,
                        'message'       => 'Delivery fee has been updated. Please review the new price.',
                        'data'          => [
                            'old_price' => (float) $estimatedPrice,
                            'new_price' => $deliveryFee,
                        ],
                    ], 422);
                }
            }

            // Validate and cap partial credits
            if ($useCredits && $creditsAmount > 0) {
                $clientCredit  = $user->getOrCreateCredits();
                $creditsAmount = min($creditsAmount, $clientCredit->available_credits, $deliveryFee);
                if ($creditsAmount <= 0) $useCredits = false;
            }

            $chargeAmount = ($useCredits && $creditsAmount > 0)
                ? max(0, $deliveryFee - $creditsAmount)
                : $deliveryFee;

            $reference = $this->paystackService->generateReference();

            // Cache the order data for 30 minutes — retrieved after Paystack confirms payment
            Cache::put('card_order_' . $reference, [
                'client_id'           => $user->id,
                'sender_name'         => $request->sender_name,
                'sender_phone'        => $request->sender_phone,
                'sender_email'        => $request->sender_email,
                'pickup_address'      => $request->pickup_address,
                'pickup_city'         => $request->pickup_city,
                'receiver_name'       => $request->receiver_name,
                'receiver_phone'      => $request->receiver_phone,
                'receiver_email'      => $request->receiver_email,
                'delivery_address'    => $request->delivery_address,
                'delivery_city'       => $request->delivery_city,
                'package_description' => $request->package_description,
                'package_quantity'    => $request->package_quantity,
                'delivery_fee'        => $deliveryFee,
                'distance'            => $distance,
                'use_credits'         => $useCredits,
                'credits_amount'      => $useCredits ? $creditsAmount : 0,
            ], now()->addMinutes(30));

            $paystackResponse = $this->paystackService->initializeTransaction([
                'amount'       => $chargeAmount,
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
     * Verify Paystack payment and create the order (JSON API endpoint).
     * Called by the frontend after Paystack redirects the user back.
     */
    public function verifyCardPayment(Request $request)
    {
        set_time_limit(120);

        $user = Auth::user();

        if (!$user instanceof Client) {
            Log::error('verifyCardPayment: User is not a Client', ['user_id' => $user?->id]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('verifyCardPayment: Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['success' => false, 'message' => 'Payment reference is required'], 422);
        }

        $reference = $request->input('reference');
        
        Log::info('verifyCardPayment: Starting verification', [
            'reference' => $reference,
            'client_id' => $user->id,
        ]);

        // Idempotency: if the order was already created for this reference, return success
        $existingOrder = Order::where('payment_reference', $reference)
            ->where('client_id', $user->id)
            ->where('payment_method', 'card')
            ->first();

        if ($existingOrder) {
            return response()->json([
                'success' => true,
                'message' => 'Order already confirmed.',
                'data'    => [
                    'order' => [
                        'id'               => $existingOrder->id,
                        'order_number'     => $existingOrder->order_number,
                        'price'            => $existingOrder->price,
                        'pickup_address'   => $existingOrder->pickup_address,
                        'delivery_address' => $existingOrder->delivery_address,
                        'status'           => $existingOrder->status,
                    ],
                ],
            ]);
        }

        // Retrieve cached order data
        $orderData = Cache::get('card_order_' . $reference);

        if (!$orderData) {
            Log::error('verifyCardPayment: Order data not found in cache', [
                'reference' => $reference,
                'client_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Session expired or not found. Please contact support if you were charged.',
            ], 404);
        }

        if ($orderData['client_id'] !== $user->id) {
            Log::error('verifyCardPayment: Client ID mismatch', [
                'reference' => $reference,
                'cached_client_id' => $orderData['client_id'],
                'current_client_id' => $user->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify payment with Paystack
        Log::info('verifyCardPayment: Verifying with Paystack', ['reference' => $reference]);
        $paystackResponse = $this->paystackService->verifyTransaction($reference);

        if (!$paystackResponse['success'] || $paystackResponse['data']['status'] !== 'success') {
            Log::error('verifyCardPayment: Paystack verification failed', [
                'reference' => $reference,
                'paystack_response' => $paystackResponse,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please use the Recheck button or contact support.',
            ], 400);
        }
        
        Log::info('verifyCardPayment: Paystack verification successful', ['reference' => $reference]);

        $useCredits    = (bool) ($orderData['use_credits'] ?? false);
        $creditsAmount = (float) ($orderData['credits_amount'] ?? 0);

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
                'pickup_city'         => $orderData['pickup_city'] ?? null,
                'receiver_name'       => $orderData['receiver_name'],
                'receiver_phone'      => $orderData['receiver_phone'],
                'receiver_email'      => $orderData['receiver_email'],
                'delivery_address'    => $orderData['delivery_address'],
                'delivery_city'       => $orderData['delivery_city'] ?? null,
                'package_description' => $orderData['package_description'],
                'package_size'        => 'medium',
                'package_weight'      => '0',
                'package_quantity'    => $orderData['package_quantity'],
                'distance'            => $orderData['distance'],
                'price'               => $orderData['delivery_fee'],
                'status'              => 'pending',
                'payment_method'      => 'card',
                'paid_with_credits'   => false,
                'payment_reference'   => $reference,
            ]);

            if ($useCredits && $creditsAmount > 0) {
                $this->creditPurchaseService->deductCreditsForOrder($user, $order->id, $creditsAmount);
                $order->update(['credits_used' => $creditsAmount]);
            }

            ClientCustomer::upsertFromOrder(
                $user->id,
                $orderData['receiver_name'],
                $orderData['receiver_phone'],
                $orderData['receiver_email'] ?? null,
                $orderData['delivery_address'],
                $orderData['delivery_fee'],
                $order->created_at ?? now()
            );

            Cache::forget('card_order_' . $reference);
            DB::commit();

            Log::info('verifyCardPayment: Order created successfully', [
                'reference' => $reference,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            $this->sendOrderPlacedEmail($user, $order);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified. Your order has been placed.',
                'data'    => [
                    'order' => [
                        'id'               => $order->id,
                        'order_number'     => $order->order_number,
                        'price'            => $order->price,
                        'pickup_address'   => $order->pickup_address,
                        'delivery_address' => $order->delivery_address,
                        'status'           => $order->status,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Card order creation error after payment: ' . $e->getMessage(), [
                'reference' => $reference,
                'client_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment received but order creation failed. Please contact support.',
                'error'   => $e->getMessage(),
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
     * Upload / replace package images for an existing order.
     */
    public function uploadImages(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order = Order::where('id', $id)->where('client_id', $user->id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->allFiles(), [
            'package_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'package_image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid file(s)', 'errors' => $validator->errors()], 422);
        }

        $imageUpdates = [];
        foreach (['package_image_1', 'package_image_2', 'package_image_3', 'package_image_4'] as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if it exists
                if ($order->$field) {
                    Storage::disk('public')->delete($order->$field);
                }
                $file     = $request->file($field);
                $filename = $field . '_' . $order->order_number . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs('orders/packages', $filename, 'public');
                $imageUpdates[$field] = $path;
            }
        }

        if (empty($imageUpdates)) {
            return response()->json(['success' => false, 'message' => 'No images provided'], 422);
        }

        $order->update($imageUpdates);
        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data'    => [
                'package_image_1' => $order->package_image_1,
                'package_image_2' => $order->package_image_2,
                'package_image_3' => $order->package_image_3,
                'package_image_4' => $order->package_image_4,
            ],
        ]);
    }

    /**
     * Return a sample CSV template for bulk order uploads.
     */
    public function bulkTemplate()
    {
        $headers = [
            'sender_name', 'sender_phone', 'sender_email',
            'pickup_address',
            'receiver_name', 'receiver_phone', 'receiver_email',
            'delivery_address',
            'package_description', 'package_quantity',
        ];

        $sample = [
            'John Doe', '08012345678', 'john@example.com',
            '123 Victoria Island, Lagos',
            'Jane Smith', '08098765432', 'jane@example.com',
            '456 Ikeja, Lagos',
            'Electronics package', '1',
        ];

        $csv = implode(',', $headers) . "\n" . implode(',', $sample) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="wakaline_bulk_orders_template.csv"',
        ]);
    }

    /**
     * Validate and process a bulk order CSV upload.
     * No orders are created unless every row passes all validation.
     */
    public function bulkUpload(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $fileValidator = Validator::make($request->all(), [
            'file'           => 'required|file|mimes:csv,txt|max:102400',
            'payment_method' => 'nullable|in:credits,cash,card',
        ]);

        if ($fileValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors'  => $fileValidator->errors(),
            ], 422);
        }

        $paymentMethod = $request->input('payment_method', 'cash');

        // --- Parse CSV ---
        $handle   = fopen($request->file('file')->getPathname(), 'r');
        $csvHeaders = fgetcsv($handle); // consume header row

        $expectedColumns = [
            'sender_name', 'sender_phone', 'sender_email',
            'pickup_address',
            'receiver_name', 'receiver_phone', 'receiver_email',
            'delivery_address',
            'package_description', 'package_quantity',
        ];

        $rawRows  = [];
        $rowIndex = 1;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip completely blank lines
            if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }

            if (count($row) < count($expectedColumns)) {
                $rawRows[] = ['index' => $rowIndex, 'data' => null, 'parse_error' => 'Row has too few columns (expected at least ' . count($expectedColumns) . ')'];
            } else {
                $rawRows[] = [
                    'index'       => $rowIndex,
                    'data'        => array_combine($expectedColumns, array_slice($row, 0, count($expectedColumns))),
                    'parse_error' => null,
                ];
            }
            $rowIndex++;
        }
        fclose($handle);

        if (empty($rawRows)) {
            return response()->json(['success' => false, 'message' => 'The CSV file contains no data rows.'], 422);
        }

        if (count($rawRows) > 1000) {
            return response()->json(['success' => false, 'message' => 'Maximum 1,000 orders per upload. Your file has ' . count($rawRows) . ' rows.'], 422);
        }

        // --- Phase 1: Field validation for ALL rows ---
        $allErrors    = [];
        $validRows    = [];

        foreach ($rawRows as $rowInfo) {
            $rowNum = $rowInfo['index'];

            if ($rowInfo['parse_error']) {
                $allErrors[] = ['row' => $rowNum, 'errors' => [$rowInfo['parse_error']]];
                continue;
            }

            $v = Validator::make($rowInfo['data'], [
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

            if ($v->fails()) {
                $allErrors[] = ['row' => $rowNum, 'errors' => $v->errors()->all()];
            } else {
                $validRows[] = ['index' => $rowNum, 'data' => $rowInfo['data']];
            }
        }

        if (!empty($allErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for ' . count($allErrors) . ' row(s). Fix the errors and re-upload.',
                'errors'  => $allErrors,
            ], 422);
        }

        // --- Phase 2: Delivery price calculation for ALL rows ---
        $priceErrors   = [];
        $processedRows = [];

        foreach ($validRows as $rowInfo) {
            $priceData = $this->deliveryPriceService->processDeliveryCalculation(
                $rowInfo['data']['pickup_address'],
                $rowInfo['data']['delivery_address']
            );

            if (isset($priceData['error'])) {
                $priceErrors[] = ['row' => $rowInfo['index'], 'errors' => [$priceData['error']]];
            } else {
                $processedRows[] = [
                    'index'        => $rowInfo['index'],
                    'data'         => $rowInfo['data'],
                    'delivery_fee' => $priceData['delivery_fee'],
                    'distance'     => $priceData['distance_km'],
                ];
            }
        }

        if (!empty($priceErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Address/price calculation failed for ' . count($priceErrors) . ' row(s). Fix the addresses and re-upload.',
                'errors'  => $priceErrors,
            ], 422);
        }

        $totalFee = array_sum(array_column($processedRows, 'delivery_fee'));

        // --- Phase 3a: Card payment — initialize Paystack, cache rows, return redirect URL ---
        if ($paymentMethod === 'card') {
            $reference = $this->paystackService->generateReference();

            Cache::put('bulk_card_order_' . $reference, [
                'client_id' => $user->id,
                'rows'      => $processedRows,
            ], now()->addMinutes(30));

            $paystackResponse = $this->paystackService->initializeTransaction([
                'email'        => $user->email,
                'amount'       => $totalFee,
                'reference'    => $reference,
                'callback_url' => config('app.frontend_url') . '/dashboard/client/orders?bulk_reference=' . $reference,
                'metadata'     => [
                    'transaction_type' => 'bulk_order_payment',
                    'client_id'        => $user->id,
                    'order_count'      => count($processedRows),
                ],
            ]);

            if (!$paystackResponse['success']) {
                Cache::forget('bulk_card_order_' . $reference);
                return response()->json([
                    'success' => false,
                    'message' => $paystackResponse['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized. Redirecting to checkout.',
                'data'    => [
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'reference'         => $reference,
                    'total_amount'      => $totalFee,
                    'order_count'       => count($processedRows),
                ],
            ]);
        }

        // --- Phase 3b: Balance/credit check ---
        if ($paymentMethod === 'credits') {
            $clientCredit = $user->getOrCreateCredits();
            if (!$clientCredit->hasEnoughCredits($totalFee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits. Total required: ' . number_format($totalFee) . ', Available: ' . number_format($clientCredit->available_credits),
                ], 400);
            }
        }

        // --- Phase 4: Create all orders in one transaction ---
        DB::beginTransaction();
        try {
            $createdOrders = [];

            foreach ($processedRows as $rowInfo) {
                $data        = $rowInfo['data'];
                $deliveryFee = $rowInfo['delivery_fee'];
                $distance    = $rowInfo['distance'];
                $orderNumber = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));

                $order = Order::create([
                    'order_number'        => $orderNumber,
                    'client_id'           => $user->id,
                    'customer_name'       => $user->name ?? $data['sender_name'],
                    'customer_phone'      => $user->phone ?? $data['sender_phone'],
                    'sender_name'         => $data['sender_name'],
                    'sender_phone'        => $data['sender_phone'],
                    'sender_email'        => $data['sender_email'] ?: null,
                    'pickup_address'      => $data['pickup_address'],
                    'receiver_name'       => $data['receiver_name'],
                    'receiver_phone'      => $data['receiver_phone'],
                    'receiver_email'      => $data['receiver_email'] ?: null,
                    'delivery_address'    => $data['delivery_address'],
                    'package_description' => $data['package_description'],
                    'package_size'        => 'medium',
                    'package_weight'      => '0',
                    'package_quantity'    => (int) $data['package_quantity'],
                    'distance'            => $distance,
                    'price'               => $deliveryFee,
                    'status'              => 'pending',
                    'payment_method'      => $paymentMethod,
                    'paid_with_credits'   => $paymentMethod === 'credits',
                ]);

                if ($paymentMethod === 'credits') {
                    $this->creditPurchaseService->deductCreditsForOrder($user, $order->id, $deliveryFee);
                    $order->update(['credits_used' => $deliveryFee]);
                }

                ClientCustomer::upsertFromOrder(
                    $user->id,
                    $data['receiver_name'],
                    $data['receiver_phone'],
                    $data['receiver_email'] ?: null,
                    $data['delivery_address'],
                    $deliveryFee,
                    $order->created_at ?? now()
                );

                $createdOrders[] = [
                    'row'          => $rowInfo['index'],
                    'order_number' => $orderNumber,
                    'delivery_fee' => $deliveryFee,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdOrders) . ' order(s) created successfully!',
                'data'    => [
                    'total_created' => count($createdOrders),
                    'orders'        => $createdOrders,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk order creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create orders. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify card payment for a bulk order upload (JSON API endpoint).
     * Called by the frontend after Paystack redirects the user back.
     */
    public function verifyBulkCardPayment(Request $request)
    {
        set_time_limit(120);

        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Payment reference is required'], 422);
        }

        $reference  = $request->input('reference');

        // Idempotency: if orders were already created for this reference, return success
        $existingOrders = Order::where('payment_reference', $reference)
            ->where('client_id', $user->id)
            ->where('payment_method', 'card')
            ->get(['order_number', 'price']);

        if ($existingOrders->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Orders already confirmed.',
                'data'    => [
                    'orders' => $existingOrders->map(fn($o) => [
                        'order_number' => $o->order_number,
                        'delivery_fee' => $o->price,
                    ])->values(),
                ],
            ]);
        }

        $cachedData = Cache::get('bulk_card_order_' . $reference);

        if (!$cachedData) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired or not found. Please contact support if you were charged.',
            ], 404);
        }

        if ($cachedData['client_id'] !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $paystackResponse = $this->paystackService->verifyTransaction($reference);

        if (!$paystackResponse['success'] || $paystackResponse['data']['status'] !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please use the Recheck button or contact support.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $createdOrders = [];

            foreach ($cachedData['rows'] as $rowInfo) {
                $data        = $rowInfo['data'];
                $deliveryFee = $rowInfo['delivery_fee'];
                $distance    = $rowInfo['distance'];
                $orderNumber = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));

                Order::create([
                    'order_number'        => $orderNumber,
                    'client_id'           => $user->id,
                    'customer_name'       => $user->name ?? $data['sender_name'],
                    'customer_phone'      => $user->phone ?? $data['sender_phone'],
                    'sender_name'         => $data['sender_name'],
                    'sender_phone'        => $data['sender_phone'],
                    'sender_email'        => $data['sender_email'] ?: null,
                    'pickup_address'      => $data['pickup_address'],
                    'receiver_name'       => $data['receiver_name'],
                    'receiver_phone'      => $data['receiver_phone'],
                    'receiver_email'      => $data['receiver_email'] ?: null,
                    'delivery_address'    => $data['delivery_address'],
                    'package_description' => $data['package_description'],
                    'package_size'        => 'medium',
                    'package_weight'      => '0',
                    'package_quantity'    => (int) $data['package_quantity'],
                    'distance'            => $distance,
                    'price'               => $deliveryFee,
                    'status'              => 'pending',
                    'payment_method'      => 'card',
                    'paid_with_credits'   => false,
                    'payment_reference'   => $reference,
                ]);

                ClientCustomer::upsertFromOrder(
                    $user->id,
                    $data['receiver_name'],
                    $data['receiver_phone'],
                    $data['receiver_email'] ?: null,
                    $data['delivery_address'],
                    $deliveryFee,
                    now()
                );

                $createdOrders[] = [
                    'row'          => $rowInfo['index'],
                    'order_number' => $orderNumber,
                    'delivery_fee' => $deliveryFee,
                ];
            }

            Cache::forget('bulk_card_order_' . $reference);
            DB::commit();

            try {
                Mail::send('emails.order_placed', [
                    'order'      => Order::where('payment_reference', $reference)->where('client_id', $user->id)->latest()->first(),
                    'clientName' => $user->name,
                ], function ($message) use ($user, $createdOrders) {
                    $count = count($createdOrders);
                    $message->to($user->email)
                        ->subject("{$count} Order(s) Confirmed — Waka Line Logistics");
                });
            } catch (\Exception $e) {
                Log::error('Bulk order email failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => count($createdOrders) . ' order(s) confirmed successfully.',
                'data'    => ['orders' => $createdOrders],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk card order creation error: ' . $e->getMessage(), ['reference' => $reference]);
            return response()->json([
                'success' => false,
                'message' => 'Payment received but order creation failed. Please contact support.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
                    $query->where(function ($q) use ($user) {
                        $q->where('sender_email', $user->email)
                          ->orWhere('receiver_email', $user->email);
                    });
                }
            })
            ->with('rider')
            ->where(function ($query) {
                // Today's orders (all statuses)
                $query->whereDate('created_at', today())
                    // OR unfulfilled orders from previous days
                    ->orWhere(function ($q) {
                        $q->whereDate('created_at', '<', today())
                          ->whereNotIn('status', ['delivered', 'cancelled']);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total'      => $orders->count(),
            'pending'    => $orders->where('status', 'pending')->count(),
            'confirmed'  => $orders->where('status', 'confirmed')->count(),
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

    /**
     * Public order tracking — no authentication required.
     * Returns only safe, non-sensitive fields suitable for sharing with customers.
     */
    public function publicTrack($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('rider:id,name')
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'order_number'        => $order->order_number,
                'status'              => $order->status,
                'is_failed_delivery'  => (bool) $order->is_failed_delivery,
                'pickup_address'      => $order->pickup_address,
                'delivery_address'    => $order->delivery_address,
                'receiver_name'       => $order->receiver_name,
                'package_description' => $order->package_description,
                'price'               => $order->price ? (float) $order->price : null,
                'created_at'          => $order->created_at,
                'updated_at'          => $order->updated_at,
                'rider'               => $order->rider ? ['name' => $order->rider->name] : null,
            ],
        ]);
    }

    /**
     * Cancel an order and refund the payment.
     * Credits → refund to credit balance.
     * Card/Cash → no automatic refund; contact support.
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order = Order::where('id', $id)->where('client_id', $user->id)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or confirmed orders can be cancelled.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->update(['status' => 'cancelled']);

            $refundMessage = 'Order cancelled.';
            $paymentMethod = $order->payment_method ?? 'cash';

            if ($paymentMethod === 'credits' && $order->credits_used > 0) {
                $clientCredit = $user->getOrCreateCredits();
                $balanceBefore = $clientCredit->available_credits;
                $clientCredit->addCredits($order->credits_used);

                \App\Modules\Admin\Models\CreditTransaction::create([
                    'client_id'               => $user->id,
                    'transaction_reference'   => \App\Modules\Admin\Models\CreditTransaction::generateReference(),
                    'type'                    => 'refund',
                    'credits'                 => $order->credits_used,
                    'balance_before'          => $balanceBefore,
                    'balance_after'           => $clientCredit->available_credits,
                    'order_id'                => $order->id,
                    'description'             => "Credit refund for cancelled order #{$order->order_number}",
                    'metadata'                => ['order_id' => $order->id, 'reason' => 'cancellation'],
                ]);

                $refundMessage = "Order cancelled. {$order->credits_used} credits refunded to your account.";

            } elseif ($paymentMethod === 'card' && $order->price > 0) {
                $refundMessage = 'Order cancelled. Card refunds are processed manually — please contact support with your order number.';
            }

            DB::commit();

            $this->sendOrderCancelledEmail($user, $order, $paymentMethod === 'credits' && $order->credits_used > 0
                ? "{$order->credits_used} credits have been refunded to your account."
                : ($paymentMethod === 'card' ? 'Card refunds are processed manually — please contact support with your order number.' : '')
            );

            return response()->json([
                'success' => true,
                'message' => $refundMessage,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to cancel order. Please try again.'], 500);
        }
    }

    /**
     * Create order with subscription payment
     * Creates order as pending, initiates subscription payment, confirms after payment
     */
    public function storeWithSubscription(Request $request)
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
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
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
            $orderNumber = 'WKL' . date('Ymd') . strtoupper(substr(uniqid(), -6));

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

            // Create the order with pending status
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
                'distance'            => $distance,
                'price'               => $deliveryFee,
                'status'              => 'pending', // Will be confirmed after payment
                'payment_method'      => 'subscription',
                'subscription_plan_id' => $request->subscription_plan_id,
            ]);

            // Initialize Paystack payment for subscription only
            // Delivery fee will be deducted from subscription credits after payment
            $plan = \App\Modules\Admin\Models\SubscriptionPlan::find($request->subscription_plan_id);
            
            $reference = 'SUB_ORDER_' . $orderNumber . '_' . time();
            
            $paymentData = $this->paystackService->initializeTransaction([
                'amount' => $plan->price, // PaystackService will convert to kobo (multiply by 100)
                'email' => $user->email,
                'reference' => $reference,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'subscription_plan_id' => $plan->id,
                    'subscription_plan_name' => $plan->name,
                    'subscription_price' => $plan->price,
                    'delivery_fee' => $deliveryFee,
                    'client_id' => $user->id,
                    'type' => 'subscription_with_order',
                ],
            ]);

            if (!$paymentData['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment: ' . ($paymentData['message'] ?? 'Unknown error'),
                ], 400);
            }

            // Update order with payment reference
            $order->update(['payment_reference' => $reference]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created. Please complete subscription payment.',
                'data' => [
                    'order' => $order,
                    'authorization_url' => $paymentData['data']['authorization_url'],
                    'access_code' => $paymentData['data']['access_code'],
                    'reference' => $reference,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order. Please try again.',
            ], 500);
        }
    }

    /**
     * Get or create client's public share link
     */
    public function getShareLink(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Get existing share link or create new one
        $clientShare = \App\Models\ClientShare::firstOrCreate(
            ['client_id' => $user->id],
            [
                'token' => \App\Models\ClientShare::generateToken(),
                'is_active' => true,
                'expires_at' => now()->addYears(10),
            ]
        );

        // Ensure it's active
        if (!$clientShare->is_active) {
            $clientShare->update(['is_active' => true]);
        }

        $shareUrl = config('app.frontend_url') . '/share/' . $clientShare->token;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $clientShare->token,
                'share_url' => $shareUrl,
                'is_active' => $clientShare->is_active,
                'expires_at' => $clientShare->expires_at,
            ],
        ]);
    }

    private function buildCalcAddress(?string $address, ?string $city): string
    {
        $a = trim((string) $address);
        $c = trim((string) $city);
        if ($a === '') return $c;
        if ($c === '') return $a;
        // avoid duplicating when one already contains the other
        if (str_contains(strtolower($a), strtolower($c)) || str_contains(strtolower($c), strtolower($a))) {
            return strlen($a) >= strlen($c) ? $a : $c;
        }
        return $a . ', ' . $c;
    }

    private function sendOrderPlacedEmail(Client $client, Order $order): void
    {
        try {
            Mail::send('emails.order_placed', [
                'order'      => $order,
                'clientName' => $client->name,
            ], function ($message) use ($client, $order) {
                $message->to($client->email)
                    ->subject("Order #{$order->order_number} Received — Waka Line Logistics");
            });
        } catch (\Exception $e) {
            Log::error('Order placed email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendOrderCancelledEmail(Client $client, Order $order, string $refundInfo = ''): void
    {
        try {
            Mail::send('emails.order_cancelled', [
                'order'      => $order,
                'clientName' => $client->name,
                'refundInfo' => $refundInfo ?: null,
            ], function ($message) use ($client, $order) {
                $message->to($client->email)
                    ->subject("Order #{$order->order_number} Cancelled — Waka Line Logistics");
            });
        } catch (\Exception $e) {
            Log::error('Order cancelled email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
