<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private DeliveryPriceService $priceService;

    public function __construct(DeliveryPriceService $priceService)
    {
        $this->priceService = $priceService;
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
            // Calculate price using delivery calculator service
            $pickupAddress = $orderData['pickup_address'] . ($orderData['pickup_area'] ? ', ' . $orderData['pickup_area'] : '');
            $deliveryAddress = $orderData['delivery_address'] . ($orderData['delivery_area'] ? ', ' . $orderData['delivery_area'] : '');
            
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
            
            $calculatedPrice = $priceCalculation['delivery_fee'];
            $calculatedDistance = $priceCalculation['distance_km'];
            
            // Use calculated price or override if provided
            $finalPrice = $orderData['price'] ?? $calculatedPrice;
            $finalDistance = $orderData['distance'] ?? $calculatedDistance;
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

            Log::info('Order Created via API', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'client_id' => $client ? $client->id : null,
                'client_name' => $client ? $client->name : 'N/A',
                'sender' => $orderData['sender_name'],
                'receiver' => $orderData['receiver_name'],
                'pickup' => $pickupAddress,
                'delivery' => $deliveryAddress,
                'item' => $orderData['item_description'],
                'price' => $finalPrice,
                'distance_km' => $finalDistance,
                'calculated_price' => $calculatedPrice,
                'status' => 'confirmed',
                'timestamp' => now()
            ]);

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
                        'email' => $order->sender_email,
                    ],
                    'receiver' => [
                        'name' => $order->receiver_name,
                        'phone' => $order->receiver_phone,
                        'email' => $order->receiver_email,
                    ],
                    'pickup_address' => $order->pickup_address,
                    'delivery_address' => $order->delivery_address,
                    'item_description' => $order->item_description,
                    'item_size' => $order->item_size,
                    'weight' => $order->weight,
                    'quantity' => $order->quantity,
                    'price' => $order->price,
                    'currency' => 'NGN',
                    'priority_level' => $order->priority_level,
                    'pickup_date' => $order->pickup_date,
                    'delivery_date' => $order->delivery_date,
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
                    'email' => $order->sender_email,
                ],
                'receiver' => [
                    'name' => $order->receiver_name,
                    'phone' => $order->receiver_phone,
                    'email' => $order->receiver_email,
                ],
                'pickup_address' => $order->pickup_address,
                'delivery_address' => $order->delivery_address,
                'item_description' => $order->item_description,
                'item_size' => $order->item_size,
                'weight' => $order->weight,
                'quantity' => $order->quantity,
                'price' => $order->price,
                'currency' => 'NGN',
                'priority_level' => $order->priority_level,
                'pickup_date' => $order->pickup_date,
                'delivery_date' => $order->delivery_date,
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
