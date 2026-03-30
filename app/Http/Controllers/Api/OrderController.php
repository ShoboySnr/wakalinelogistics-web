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
            'sender_name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'sender_phone' => 'required|string|min:10|max:20|regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            'sender_email' => 'required|email:rfc,dns|max:255',
            'pickup_address' => 'required|string|min:10|max:500',
            'pickup_area' => 'nullable|string|max:255',
            'recipient_name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'recipient_phone' => 'required|string|min:10|max:20|regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            'delivery_address' => 'required|string|min:10|max:500',
            'delivery_area' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string|max:1000',
            'package_description' => 'required|string|min:3|max:255',
            'package_size' => 'required|string|in:Small,Medium,Large,Extra Large',
            'preferred_time' => 'required|string|in:Morning (8AM-12PM),Afternoon (12PM-4PM),Evening (4PM-8PM),Anytime',
            'additional_notes' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'distance' => 'nullable|numeric|min:0|max:9999.99',
            'form_source' => 'nullable|string|max:100',
        ], [
            'sender_name.required' => 'Sender name is required',
            'sender_name.min' => 'Sender name must be at least 2 characters',
            'sender_name.regex' => 'Sender name can only contain letters, spaces, hyphens, and periods',
            'sender_phone.required' => 'Sender phone number is required',
            'sender_phone.min' => 'Phone number must be at least 10 digits',
            'sender_phone.regex' => 'Please enter a valid phone number',
            'sender_email.required' => 'Sender email is required',
            'sender_email.email' => 'Please enter a valid email address',
            'pickup_address.required' => 'Pickup address is required',
            'pickup_address.min' => 'Pickup address must be at least 10 characters',
            'recipient_name.required' => 'Recipient name is required',
            'recipient_name.min' => 'Recipient name must be at least 2 characters',
            'recipient_name.regex' => 'Recipient name can only contain letters, spaces, hyphens, and periods',
            'recipient_phone.required' => 'Recipient phone number is required',
            'recipient_phone.min' => 'Recipient phone number must be at least 10 digits',
            'recipient_phone.regex' => 'Please enter a valid recipient phone number',
            'delivery_address.required' => 'Delivery address is required',
            'delivery_address.min' => 'Delivery address must be at least 10 characters',
            'package_description.required' => 'Package description is required',
            'package_description.min' => 'Package description must be at least 3 characters',
            'package_size.required' => 'Package size is required',
            'package_size.in' => 'Package size must be one of: Small, Medium, Large, Extra Large',
            'preferred_time.required' => 'Preferred pickup time is required',
            'preferred_time.in' => 'Please select a valid preferred time slot',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be negative',
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
