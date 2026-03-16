<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $orderData = $validator->validated();
        
        // Generate unique order ID
        $orderId = 'MTR-' . strtoupper(uniqid());
        
        try {
            // Log the order for record keeping
            Log::info('New Metter Order Received', [
                'order_id' => $orderId,
                'sender' => $orderData['sender_name'],
                'pickup' => $orderData['pickup_area'] ?? $orderData['pickup_address'],
                'delivery' => $orderData['delivery_area'] ?? $orderData['delivery_address'],
                'package' => $orderData['package_description'],
                'timestamp' => now()
            ]);

            // Here you can add additional logic:
            // - Save to database
            // - Send email notification
            // - Send SMS notification
            // - Integrate with delivery management system
            
            // Example: Send email notification (uncomment when ready)
            /*
            Mail::send('emails.order-notification', [
                'order_id' => $orderId,
                'order_data' => $orderData
            ], function ($message) use ($orderData) {
                $message->to('orders@wakalinelogistics.com')
                        ->subject('New Metter Order: ' . $orderId);
            });
            */

            return response()->json([
                'success' => true,
                'message' => 'Order submitted successfully! We will contact you shortly.',
                'order_id' => $orderId
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
