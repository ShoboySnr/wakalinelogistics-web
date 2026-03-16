<?php

namespace App\Modules\Rider\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiderDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        $stats = [
            'total_deliveries' => $rider->total_deliveries,
            'active_orders' => $rider->activeOrders()->count(),
            'completed_today' => $rider->orders()->where('status', 'delivered')
                ->whereDate('updated_at', today())->count(),
            'rating' => $rider->rating,
        ];

        $activeOrders = $rider->activeOrders()->with(['user', 'creator'])->latest()->get();
        $recentOrders = $rider->orders()->with(['user', 'creator'])->latest()->limit(10)->get();

        return view('Rider::dashboard', compact('rider', 'stats', 'activeOrders', 'recentOrders'));
    }

    public function orders()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        $orders = $rider->orders()->with(['user', 'creator'])->latest()->paginate(20);

        return view('Rider::orders.index', compact('rider', 'orders'));
    }

    public function showOrder($id)
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        $order = $rider->orders()->with(['user', 'creator'])->findOrFail($id);

        return view('Rider::orders.show', compact('rider', 'order'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        $order = $rider->orders()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:confirmed,in_transit,delivered',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $order->notes,
        ]);

        // Update rider's total deliveries if order is delivered
        if ($validated['status'] === 'delivered') {
            $rider->increment('total_deliveries');
        }

        return redirect()->back()->with('success', 'Order status updated successfully');
    }

    public function profile()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        return view('Rider::profile', compact('rider', 'user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();
        
        if (!$rider) {
            return redirect()->route('rider.login')->with('error', 'Rider profile not found');
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        $rider->update($validated);

        return redirect()->route('rider.profile')->with('success', 'Profile updated successfully');
    }
}
