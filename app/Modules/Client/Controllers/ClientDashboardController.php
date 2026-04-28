<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientDashboardController extends Controller
{
    /**
     * Show the client dashboard
     */
    public function index()
    {
        $client = Auth::guard('client')->user();

        // Get statistics
        $stats = [
            'total_orders' => Order::where('client_id', $client->id)->count(),
            'pending_orders' => Order::where('client_id', $client->id)->where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('client_id', $client->id)->where('status', 'confirmed')->count(),
            'in_transit_orders' => Order::where('client_id', $client->id)->where('status', 'in_transit')->count(),
            'delivered_orders' => Order::where('client_id', $client->id)->where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('client_id', $client->id)->where('status', 'cancelled')->count(),
            
            // Today's stats
            'today_orders' => Order::where('client_id', $client->id)
                                   ->whereDate('created_at', today())
                                   ->count(),
            'today_delivered' => Order::where('client_id', $client->id)
                                     ->whereDate('delivery_date', today())
                                     ->where('status', 'delivered')
                                     ->count(),
            
            // This month stats
            'month_orders' => Order::where('client_id', $client->id)
                                  ->whereMonth('created_at', now()->month)
                                  ->whereYear('created_at', now()->year)
                                  ->count(),
            'month_delivered' => Order::where('client_id', $client->id)
                                     ->whereMonth('delivery_date', now()->month)
                                     ->whereYear('delivery_date', now()->year)
                                     ->where('status', 'delivered')
                                     ->count(),
        ];

        // Get recent orders
        $recent_orders = Order::where('client_id', $client->id)
                             ->latest()
                             ->limit(10)
                             ->get();

        return view('Client::dashboard.index', compact('stats', 'recent_orders'));
    }

    /**
     * Show all orders
     */
    public function orders(Request $request)
    {
        $client = Auth::guard('client')->user();
        $query = Order::where('client_id', $client->id);

        // Status filtering
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        // Date filtering
        if ($request->has('date_filter') && $request->date_filter != '') {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
            }
        }

        $orders = $query->latest()->paginate(20);

        // Calculate statistics for the filtered view
        $stats = [
            'total' => Order::where('client_id', $client->id)->count(),
            'pending' => Order::where('client_id', $client->id)->where('status', 'pending')->count(),
            'confirmed' => Order::where('client_id', $client->id)->where('status', 'confirmed')->count(),
            'in_transit' => Order::where('client_id', $client->id)->where('status', 'in_transit')->count(),
            'delivered' => Order::where('client_id', $client->id)->where('status', 'delivered')->count(),
            'cancelled' => Order::where('client_id', $client->id)->where('status', 'cancelled')->count(),
        ];

        return view('Client::orders.index', compact('orders', 'stats'));
    }

    /**
     * Show single order details
     */
    public function showOrder($id)
    {
        $client = Auth::guard('client')->user();
        
        $order = Order::where('client_id', $client->id)
                     ->where('id', $id)
                     ->firstOrFail();

        return view('Client::orders.show', compact('order'));
    }

    /**
     * Show create order form
     */
    public function createOrder()
    {
        return view('Client::orders.create');
    }

    /**
     * Store new order
     */
    public function storeOrder(Request $request)
    {
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_email' => 'nullable|email|max:255',
            'delivery_address' => 'required|string',
            'item_description' => 'required|string',
            'item_weight' => 'nullable|numeric|min:0',
            'item_quantity' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'priority' => 'nullable|in:normal,urgent,express',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'special_instructions' => 'nullable|string',
        ]);

        // Generate order number
        $lastOrder = Order::latest('id')->first();
        $nextNumber = $lastOrder ? ($lastOrder->id + 1) : 1;
        $orderNumber = 'ORD-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Add client and order details
        $validated['order_number'] = $orderNumber;
        $validated['client_id'] = $client->id;
        $validated['source'] = 'Client Dashboard';
        $validated['source_contact'] = $client->phone;
        $validated['source_notes'] = "Order created by {$client->name} via client dashboard";
        $validated['status'] = 'pending';
        $validated['priority'] = $validated['priority'] ?? 'normal';

        $order = Order::create($validated);

        return redirect()->route('client.orders.show', $order->id)
                        ->with('success', 'Order created successfully! Order Number: ' . $orderNumber);
    }

    /**
     * Show profile page
     */
    public function profile()
    {
        $client = Auth::guard('client')->user();
        return view('Client::profile.index', compact('client'));
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:clients,email,' . $client->id,
            'pickup_address' => 'nullable|string',
        ]);

        $client->update($validated);

        return back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $client->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $client->update([
            'password' => Hash::make($validated['password'])
        ]);

        return back()->with('success', 'Password updated successfully');
    }
}
