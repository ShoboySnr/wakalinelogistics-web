<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use App\Services\ZoneBatchDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private DeliveryPriceService $priceService;

    private ZoneBatchDiscountService $batchDiscount;

    public function __construct(
        DeliveryPriceService $priceService,
        ZoneBatchDiscountService $batchDiscount,
    ) {
        $this->priceService = $priceService;
        $this->batchDiscount = $batchDiscount;
    }

    public function submitOrder(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'pickup_area' => 'nullable|string|max:255',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_email' => 'nullable|email|max:255',
            'delivery_address' => 'required|string',
            'delivery_area' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string',
            'item_description' => 'required|string',
            'item_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'distance' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'source_contact' => 'nullable|string|max:255',
            'source_notes' => 'nullable|string',
        ], [
            'sender_name.required' => 'Sender name is required',
            'sender_phone.required' => 'Sender phone number is required',
            'sender_email.email' => 'Please enter a valid sender email address',
            'pickup_address.required' => 'Pickup address is required',
            'receiver_name.required' => 'Receiver name is required',
            'receiver_phone.required' => 'Receiver phone number is required',
            'receiver_email.email' => 'Please enter a valid receiver email address',
            'delivery_address.required' => 'Delivery address is required',
            'item_description.required' => 'Item description is required',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be negative',
            'weight.numeric' => 'Weight must be a valid number',
            'weight.min' => 'Weight cannot be negative',
            'quantity.integer' => 'Quantity must be a valid number',
            'quantity.min' => 'Quantity must be at least 1',
            'distance.numeric' => 'Distance must be a valid number',
            'distance.min' => 'Distance cannot be negative',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $orderData = $validator->validated();
        
        // Get authenticated client from middleware
        $client = $request->attributes->get('client');
        
        try {
            $pickupArea = $orderData['pickup_area'] ?? null;
            $deliveryArea = $orderData['delivery_area'] ?? null;
            $pickupAddress = $orderData['pickup_address'] . ($pickupArea ? ', ' . $pickupArea : '');
            $deliveryAddress = $orderData['delivery_address'] . ($deliveryArea ? ', ' . $deliveryArea : '');

            $priceCalculation = $this->priceService->processDeliveryCalculation(
                $pickupAddress,
                $deliveryAddress
            );

            if (isset($priceCalculation['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to calculate delivery price',
                    'error' => $priceCalculation['error']
                ], 400);
            }

            $finalDistance = $priceCalculation['distance_km'];
            $deliveryZone = $priceCalculation['delivery_zone'];
            $fixedRate = $client ? $client->getZoneRate($deliveryZone) : null;
            $finalPrice = $fixedRate ?? $priceCalculation['delivery_fee'];

            // Same-zone batch discount: only ever comes off an agreed fixed
            // zone rate, never off a distance-calculated fee.
            $pricing = $this->batchDiscount->apply(
                $fixedRate !== null ? $client : null,
                $deliveryZone,
                (float) $finalPrice,
            );
            $finalPrice = $pricing['price'];

            // Prepare order data
            $orderCreateData = [
                'client_id' => $client ? $client->id : null,
                'source' => 'API',
                'source_contact' => $orderData['source_contact'] ?? ($client ? $client->phone : $orderData['sender_phone']),
                'source_notes' => $orderData['source_notes'] ?? 'Submitted via ' . ($client ? $client->name : 'API'),
                'sender_name' => $orderData['sender_name'],
                'sender_phone' => $orderData['sender_phone'],
                'sender_email' => $orderData['sender_email'] ?? null,
                'pickup_address' => $pickupAddress,
                'receiver_name' => $orderData['receiver_name'],
                'receiver_phone' => $orderData['receiver_phone'],
                'receiver_email' => $orderData['receiver_email'] ?? null,
                'delivery_address' => $deliveryAddress,
                'item_description' => $orderData['item_description'],
                'item_size' => $orderData['item_size'] ?? null,
                'weight' => $orderData['weight'] ?? null,
                'quantity' => $orderData['quantity'] ?? null,
                'distance' => $finalDistance,
                'price' => $finalPrice,
                'pickup_zone' => $priceCalculation['pickup_zone'],
                'delivery_zone' => $deliveryZone,
                'base_price' => $pricing['base_price'],
                'zone_discount_percent' => $pricing['zone_discount_percent'],
                'zone_discount_amount' => $pricing['zone_discount_amount'],
                'zone_batch_size' => $pricing['zone_batch_size'],
                'status' => 'confirmed',
                'priority_level' => 'normal',
                'notes' => $orderData['notes'] ?? ($orderData['delivery_notes'] ?? null),
            ];

            // Set customer fields for backward compatibility
            $orderCreateData['customer_name'] = $orderData['sender_name'];
            $orderCreateData['customer_phone'] = $orderData['sender_phone'];
            $orderCreateData['customer_email'] = $orderData['sender_email'] ?? null;

            // Create order in database with confirmed status
            $order = Order::create($orderCreateData);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully!',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                    'client_id' => $order->client_id,
                    'source' => $order->source,
                    'source_contact' => $order->source_contact,
                    'source_notes' => $order->source_notes,
                    'sender' => [
                        'name' => $order->sender_name,
                        'phone' => $order->sender_phone,
                        'email' => $order->sender_email ?? '',
                    ],
                    'receiver' => [
                        'name' => $order->receiver_name,
                        'phone' => $order->receiver_phone,
                        'email' => $order->receiver_email ?? '',
                    ],
                    'pickup_address' => $order->pickup_address,
                    'delivery_address' => $order->delivery_address,
                    'item_description' => $order->item_description,
                    'item_size' => $order->item_size,
                    'weight' => $order->weight,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'currency' => 'NGN',
                    'delivery_zone' => $order->delivery_zone,
                    'batch_discount' => $pricing['zone_discount_percent'] === null ? null : [
                        'percent' => (float) $pricing['zone_discount_percent'],
                        'amount' => (float) $pricing['zone_discount_amount'],
                        'base_price' => (float) $pricing['base_price'],
                        'orders_in_zone' => $pricing['zone_batch_size'],
                        'reason' => $pricing['evaluation']['reason'],
                    ],
                    'priority_level' => $order->priority_level,
                    'pickup_date' => $order->pickup_date ?? '',
                    'delivery_date' => $order->delivery_date ?? '',
                    'notes' => $order->notes,
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Order Submission Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your order. Please try again.'
            ], 500);
        }
    }

    public function getQuote(Request $request)
    {
        $validated = $request->validate([
            'pickup_address'  => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
        ]);

        $result = $this->priceService->processDeliveryCalculation(
            $validated['pickup_address'],
            $validated['dropoff_address']
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate delivery price',
                'error'   => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pickup' => [
                    'address'           => $result['pickup'],
                    'formatted_address' => $result['pickup_formatted'] ?? $result['pickup'],
                    'zone'              => $result['pickup_zone'],
                ],
                'delivery' => [
                    'address'           => $result['delivery'],
                    'formatted_address' => $result['delivery_formatted'] ?? $result['delivery'],
                    'zone'              => $result['delivery_zone'],
                ],
                'distance_km'  => (float) number_format($result['distance_km'], 2, '.', ''),
                'delivery_fee' => $result['delivery_fee'],
                'currency'     => 'NGN',
            ],
        ]);
    }

    public function getOrderStatus(Request $request, $orderNumber)
    {
        // Get authenticated client from middleware
        $client = $request->attributes->get('client');

        // Find order by order number and ensure it belongs to the authenticated client
        $order = Order::where('order_number', $orderNumber)
            ->where('client_id', $client->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or you do not have permission to view this order.',
                'error' => 'order_not_found'
            ], 404);
        }

        // Prepare order status response
        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
                'sender' => [
                    'name' => $order->sender_name,
                    'phone' => $order->sender_phone,
                    'email' => $order->sender_email ?? '',
                ],
                'receiver' => [
                    'name' => $order->receiver_name,
                    'phone' => $order->receiver_phone,
                    'email' => $order->receiver_email ?? '',
                ],
                'pickup_address' => $order->pickup_address,
                'delivery_address' => $order->delivery_address,
                'item_description' => $order->item_description,
                'item_size' => $order->item_size,
                'weight' => $order->weight !== null ? (float) $order->weight : null,
                'quantity' => $order->quantity,
                'price' => $order->price !== null ? (float) $order->price : null,
                'currency' => 'NGN',
                'priority_level' => $order->priority_level,
                'pickup_date' => $order->pickup_date ?? '',
                'delivery_date' => $order->delivery_date ?? '',
                'notes' => $order->notes,
                'rider' => $order->rider ? [
                    'id' => $order->rider->id,
                    'name' => $order->rider->name,
                    'phone' => $order->rider->phone,
                ] : null,
            ]
        ], 200);
    }
}
