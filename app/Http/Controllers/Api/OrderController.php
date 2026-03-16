<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function submitOrder(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'required|email|max:255',
            'pickup_address' => 'required|string',
            'pickup_area' => 'nullable|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'delivery_area' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string',
            'package_description' => 'required|string|max:255',
            'package_size' => 'required|string|max:100',
            'preferred_time' => 'required|string|max:100',
            'additional_notes' => 'nullable|string',
            'price' => 'nullable|numeric',
            'distance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $orderData = $validator->validated();
        
        try {
            // Create order in database with confirmed status
            $order = Order::create([
                'source' => 'Website Form',
                'source_contact' => $orderData['sender_phone'],
                'source_notes' => 'Submitted via ' . ($request->input('form_source', 'Landing Page')),
                'customer_name' => $orderData['sender_name'],
                'customer_email' => $orderData['sender_email'],
                'customer_phone' => $orderData['sender_phone'],
                'sender_name' => $orderData['sender_name'],
                'sender_phone' => $orderData['sender_phone'],
                'sender_email' => $orderData['sender_email'],
                'pickup_address' => $orderData['pickup_address'] . ($orderData['pickup_area'] ? ', ' . $orderData['pickup_area'] : ''),
                'delivery_address' => $orderData['delivery_address'] . ($orderData['delivery_area'] ? ', ' . $orderData['delivery_area'] : ''),
                'receiver_name' => $orderData['recipient_name'],
                'receiver_phone' => $orderData['recipient_phone'],
                'item_description' => $orderData['package_description'],
                'item_size' => $orderData['package_size'],
                'price' => $orderData['price'] ?? 0,
                'distance' => $orderData['distance'] ?? 0,
                'status' => 'confirmed',
                'notes' => trim(
                    "Preferred Pickup Time: {$orderData['preferred_time']}\n" .
                    "Delivery Notes: " . ($orderData['delivery_notes'] ?? 'None') . "\n" .
                    "Additional Notes: " . ($orderData['additional_notes'] ?? 'None')
                ),
            ]);

            Log::info('Order Created from Website Form', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'sender' => $orderData['sender_name'],
                'pickup' => $orderData['pickup_area'] ?? $orderData['pickup_address'],
                'delivery' => $orderData['delivery_area'] ?? $orderData['delivery_address'],
                'package' => $orderData['package_description'],
                'status' => 'confirmed',
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order submitted successfully! We will contact you shortly.',
                'order_id' => $order->id,
                'order_number' => $order->order_number
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
}
