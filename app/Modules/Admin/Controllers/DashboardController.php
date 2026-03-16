<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Setting;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\RouteShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            // Overall stats
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'in_transit_orders' => Order::where('status', 'in_transit')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('price'),
            
            // Today's stats
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                                    ->where('status', 'delivered')
                                    ->sum('price'),
            'today_pending' => Order::whereDate('created_at', today())
                                    ->where('status', 'pending')
                                    ->count(),
            'today_delivered' => Order::whereDate('created_at', today())
                                      ->where('status', 'delivered')
                                      ->count(),
            
            // This week stats
            'week_orders' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'week_revenue' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                                   ->where('status', 'delivered')
                                   ->sum('price'),
            
            // This month stats
            'month_orders' => Order::whereMonth('created_at', now()->month)
                                   ->whereYear('created_at', now()->year)
                                   ->count(),
            'month_revenue' => Order::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->where('status', 'delivered')
                                    ->sum('price'),
            
            // Rider stats
            'total_riders' => Rider::count(),
            'active_riders' => Rider::where('status', 'active')->count(),
            'riders_with_orders' => Rider::whereHas('orders', function($q) {
                $q->whereIn('status', ['pending', 'confirmed', 'in_transit']);
            })->count(),
        ];

        $recent_orders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('Admin::dashboard', compact('stats', 'recent_orders'));
    }

    public function orders(Request $request)
    {
        $query = Order::with('user');

        // Status filtering - default to 'pending' if no filter is specified
        $statusFilter = $request->get('status', 'pending');
        if ($statusFilter && $statusFilter != 'all') {
            $query->where('status', $statusFilter);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Date filtering - optional
        $dateFilter = $request->get('date_filter', '');
        
        if ($dateFilter != '') {
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('created_at', [
                        now()->subWeek()->startOfWeek(),
                        now()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
                case 'custom':
                    if ($request->has('start_date') && $request->start_date != '') {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->has('end_date') && $request->end_date != '') {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        $orders = $query->latest()->paginate(20);

        // Calculate order statistics
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'in_transit' => Order::where('status', 'in_transit')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            // Revenue statistics - only count delivered orders
            'revenue_today' => Order::whereDate('created_at', today())
                                    ->where('status', 'delivered')
                                    ->sum('price'),
            'revenue_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                                   ->where('status', 'delivered')
                                   ->sum('price'),
            'revenue_month' => Order::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->where('status', 'delivered')
                                    ->sum('price'),
        ];

        return view('Admin::orders.index', compact('orders', 'stats'));
    }

    public function showOrder($id)
    {
        $order = Order::with(['user', 'rider'])->findOrFail($id);
        $riders = Rider::where('status', 'active')->orderBy('name')->get();
        return view('Admin::orders.show', compact('order', 'riders'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order->status = $request->status;
        
        if ($request->status == 'delivered' && !$order->delivery_date) {
            $order->delivery_date = now();
        }
        
        $order->save();

        ActivityLog::log('order_status_updated', "Updated order #{$order->order_number} status from {$oldStatus} to {$request->status}", $order, [
            'old_status' => $oldStatus,
            'new_status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Order status updated successfully');
    }

    public function assignRider(Request $request, $id)
    {
        $request->validate([
            'rider_id' => 'nullable|exists:riders,id',
        ]);

        $order = Order::findOrFail($id);
        $oldRiderId = $order->rider_id;
        $order->rider_id = $request->rider_id;
        $order->save();

        $riderName = $request->rider_id ? Rider::find($request->rider_id)->name : 'None';
        $oldRiderName = $oldRiderId ? Rider::find($oldRiderId)->name : 'None';

        ActivityLog::log('order_rider_assigned', "Assigned order #{$order->order_number} to rider: {$riderName}", $order, [
            'old_rider' => $oldRiderName,
            'new_rider' => $riderName,
        ]);

        return redirect()->back()->with('success', 'Rider assigned successfully');
    }

    public function generateInvoice($id)
    {
        $order = Order::with(['user', 'rider', 'creator'])->findOrFail($id);
        
        // Check if order is delivered
        if ($order->status !== 'delivered') {
            return redirect()->back()->with('error', 'Invoice can only be generated for delivered orders.');
        }

        $data = [
            'order' => $order,
            'invoice_date' => now()->format('F d, Y'),
            'invoice_number' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
        ];

        $pdf = Pdf::loadView('Admin::invoices.template', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
        
        ActivityLog::log('invoice_generated', "Generated invoice for order #{$order->order_number}", $order);

        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    public function createOrder()
    {
        return view('Admin::orders.create');
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'source' => 'required|string|in:whatsapp,instagram,web,phone,walk-in,email,other',
            'source_contact' => 'nullable|string|max:255',
            'source_notes' => 'nullable|string',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'item_description' => 'required|string',
            'item_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'distance' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
            'pickup_date' => 'nullable|date',
            'package_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle image uploads
        $imageFields = ['package_image_1', 'package_image_2', 'package_image_3', 'package_image_4'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('orders/packages', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // Handle additional file uploads
        $additionalFileFields = ['additional_file_1', 'additional_file_2', 'additional_file_3'];
        foreach ($additionalFileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('orders/additional', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // Set customer_name and customer_phone from sender for backward compatibility
        $validated['customer_name'] = $validated['sender_name'];
        $validated['customer_phone'] = $validated['sender_phone'];
        $validated['customer_email'] = $validated['sender_email'];
        
        // Record which admin created this order
        $validated['created_by'] = Auth::id();

        $order = Order::create($validated);

        ActivityLog::log('order_created', "Created order #{$order->order_number}", $order, [
            'order_number' => $order->order_number,
            'customer' => $validated['sender_name'],
            'price' => $validated['price'],
        ]);

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Order created successfully');
    }

    public function editOrder($id)
    {
        $order = Order::findOrFail($id);
        return view('Admin::orders.edit', compact('order'));
    }

    public function updateOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'source' => 'required|string|in:whatsapp,instagram,web,phone,walk-in,email,other',
            'source_contact' => 'nullable|string|max:255',
            'source_notes' => 'nullable|string',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'item_description' => 'required|string',
            'item_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'distance' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
            'pickup_date' => 'nullable|date',
            'package_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle image uploads
        $imageFields = ['package_image_1', 'package_image_2', 'package_image_3', 'package_image_4'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('orders/packages', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // Handle additional file uploads
        $additionalFileFields = ['additional_file_1', 'additional_file_2', 'additional_file_3'];
        foreach ($additionalFileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('orders/additional', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        // Set customer_name and customer_phone from sender for backward compatibility
        $validated['customer_name'] = $validated['sender_name'];
        $validated['customer_phone'] = $validated['sender_phone'];
        $validated['customer_email'] = $validated['sender_email'];

        $order->update($validated);

        ActivityLog::log('order_updated', "Updated order #{$order->order_number}", $order, [
            'order_number' => $order->order_number,
            'customer' => $validated['sender_name'],
            'price' => $validated['price'],
        ]);

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Order updated successfully');
    }

    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);
        $orderNumber = $order->order_number;
        $order->delete();

        ActivityLog::log('order_deleted', "Deleted order #{$orderNumber}", null, [
            'order_number' => $orderNumber,
        ]);

        return redirect()->route('admin.orders')->with('success', 'Order deleted successfully');
    }

    public function profile()
    {
        return view('Admin::profile');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
        ]);

        Auth::user()->update($validated);

        ActivityLog::log('profile_updated', 'Updated profile information');

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password'])
        ]);

        ActivityLog::log('password_changed', 'Changed account password');

        return redirect()->route('admin.profile')->with('success', 'Password changed successfully');
    }

    public function settings()
    {
        $settings = Setting::all()->groupBy('group');
        return view('Admin::settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                Setting::set($key, $value, $setting->type, $setting->group, $setting->description);
            }
        }

        ActivityLog::log('settings_updated', 'Updated system settings');

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully');
    }

    // Rider Management
    public function riders()
    {
        $riders = Rider::withCount('orders')->latest()->paginate(15);
        return view('Admin::riders.index', compact('riders'));
    }

    public function createRider()
    {
        return view('Admin::riders.create');
    }

    public function storeRider(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:riders,email',
            'phone' => 'required|string|max:20',
            'age' => 'nullable|integer|min:18|max:100',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
            // Rider documents
            'rider_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rider_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'driver_license_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'vehicle_registration' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'vehicle_insurance' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Guarantor 1
            'guarantor1_full_name' => 'nullable|string|max:255',
            'guarantor1_dob' => 'nullable|date',
            'guarantor1_nationality' => 'nullable|string|max:100',
            'guarantor1_occupation' => 'nullable|string|max:255',
            'guarantor1_nin' => 'nullable|string|max:50',
            'guarantor1_residential_address' => 'nullable|string',
            'guarantor1_phone' => 'nullable|string|max:20',
            'guarantor1_alt_phone1' => 'nullable|string|max:20',
            'guarantor1_alt_phone2' => 'nullable|string|max:20',
            'guarantor1_work_address' => 'nullable|string',
            'guarantor1_relationship' => 'nullable|string|max:100',
            'guarantor1_years_known' => 'nullable|integer',
            'guarantor1_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_proof_of_address' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_employment_letter' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_additional_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Guarantor 2
            'guarantor2_full_name' => 'nullable|string|max:255',
            'guarantor2_dob' => 'nullable|date',
            'guarantor2_nationality' => 'nullable|string|max:100',
            'guarantor2_occupation' => 'nullable|string|max:255',
            'guarantor2_nin' => 'nullable|string|max:50',
            'guarantor2_residential_address' => 'nullable|string',
            'guarantor2_phone' => 'nullable|string|max:20',
            'guarantor2_alt_phone1' => 'nullable|string|max:20',
            'guarantor2_alt_phone2' => 'nullable|string|max:20',
            'guarantor2_work_address' => 'nullable|string',
            'guarantor2_relationship' => 'nullable|string|max:100',
            'guarantor2_years_known' => 'nullable|integer',
            'guarantor2_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_proof_of_address' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_employment_letter' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_additional_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Witness
            'witness_full_name' => 'nullable|string|max:255',
            'witness_phone' => 'nullable|string|max:20',
            'witness_address' => 'nullable|string',
            'witness_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'witness_date' => 'nullable|date',
            // Additional Files
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle file uploads
        $fileFields = [
            'rider_photo', 'rider_id_document', 'driver_license_doc', 'vehicle_registration', 'vehicle_insurance',
            'guarantor1_id_document', 'guarantor1_proof_of_address', 'guarantor1_employment_letter', 'guarantor1_additional_doc',
            'guarantor2_id_document', 'guarantor2_proof_of_address', 'guarantor2_employment_letter', 'guarantor2_additional_doc',
            'witness_signature', 'additional_file_1', 'additional_file_2', 'additional_file_3'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('riders/documents', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        $rider = Rider::create($validated);

        ActivityLog::log('rider_created', "Created rider: {$rider->name}", $rider);

        return redirect()->route('admin.riders')->with('success', 'Rider created successfully');
    }

    public function showRider($id)
    {
        $rider = Rider::with(['orders' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);
        
        // Get pending/active orders (not delivered or cancelled)
        $pendingOrders = $rider->orders()
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->latest()
            ->get();
        
        // Build route waypoints for route planning
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $waypoints = [];
        $cumulativeTime = 0; // in minutes
        
        foreach ($pendingOrders as $order) {
            // Add pickup location only if not yet picked up
            if ($order->pickup_address && !$order->pickup_date) {
                // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
                
                $waypoints[] = [
                    'address' => $order->pickup_address,
                    'type' => 'pickup',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'sender' => $order->sender_name ?? $order->customer_name,
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A')
                ];
            }
            
            // Add delivery location (always include if order is in transit or confirmed)
            if ($order->delivery_address) {
                // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
                $estimatedTravelTime = $order->distance ? ($order->distance * 5) : 15;
                $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
                
                $waypoints[] = [
                    'address' => $order->delivery_address,
                    'type' => 'delivery',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'receiver' => $order->receiver_name ?? 'N/A',
                    'estimated_time' => $cumulativeTime,
                    'eta' => now()->addMinutes($cumulativeTime)->format('g:i A')
                ];
            }
        }
        
        // Generate WhatsApp share text
        $whatsappText = $this->generateWhatsAppRouteText($rider, $waypoints, $startingPoint);
        
        return view('Admin::riders.show', compact('rider', 'pendingOrders', 'startingPoint', 'waypoints', 'whatsappText'));
    }
    
    private function generateWhatsAppRouteText($rider, $waypoints, $startingPoint)
    {
        if (empty($waypoints)) {
            return "No active deliveries at the moment.";
        }
        
        $text = "🚚 *Delivery Route for {$rider->name}*\n\n";
        $text .= "📍 *Starting Point:* {$startingPoint}\n\n";
        $text .= "📋 *Route Plan:*\n";
        
        $stepNumber = 1;
        $previousTime = 0;
        
        foreach ($waypoints as $waypoint) {
            $timeToNextStop = $waypoint['estimated_time'] - $previousTime;
            
            if ($waypoint['type'] === 'pickup') {
                $text .= "{$stepNumber}. 📦 *PICKUP* - Order #{$waypoint['order_number']}\n";
                $text .= "   From: {$waypoint['sender']}\n";
                $text .= "   Location: {$waypoint['address']}\n";
                $text .= "   ⏱️ Time to reach: ~{$timeToNextStop} min\n";
                $text .= "   🕐 ETA: {$waypoint['eta']}\n\n";
            } else {
                $text .= "{$stepNumber}. 🏠 *DROP OFF* - Order #{$waypoint['order_number']}\n";
                $text .= "   To: {$waypoint['receiver']}\n";
                $text .= "   Location: {$waypoint['address']}\n";
                $text .= "   ⏱️ Time to reach: ~{$timeToNextStop} min\n";
                $text .= "   🕐 ETA: {$waypoint['eta']}\n\n";
            }
            
            $previousTime = $waypoint['estimated_time'];
            $stepNumber++;
        }
        
        $text .= "✅ Total Stops: " . count($waypoints) . "\n";
        $text .= "⏱️ Total Journey Time: ~" . end($waypoints)['estimated_time'] . " minutes";
        
        return urlencode($text);
    }

    public function editRider($id)
    {
        $rider = Rider::findOrFail($id);
        return view('Admin::riders.edit', compact('rider'));
    }

    public function updateRider(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:riders,email,' . $id,
            'phone' => 'required|string|max:20',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $rider->update($validated);

        ActivityLog::log('rider_updated', "Updated rider: {$rider->name}", $rider);

        return redirect()->route('admin.riders.show', $id)->with('success', 'Rider updated successfully');
    }

    public function deleteRider($id)
    {
        $rider = Rider::findOrFail($id);
        $riderName = $rider->name;
        
        // Check if rider has active orders
        if ($rider->activeOrders()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete rider with active orders']);
        }

        $rider->delete();

        ActivityLog::log('rider_deleted', "Deleted rider: {$riderName}");

        return redirect()->route('admin.riders')->with('success', 'Rider deleted successfully');
    }

    public function generateRouteShareLink($id)
    {
        $rider = Rider::findOrFail($id);
        
        // Deactivate any existing active shares for this rider
        RouteShare::where('rider_id', $rider->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        
        // Create new share link (expires in 7 days)
        $routeShare = RouteShare::create([
            'rider_id' => $rider->id,
            'token' => RouteShare::generateToken(),
            'expires_at' => now()->addDays(7),
            'is_active' => true,
        ]);
        
        ActivityLog::log('route_share_created', "Generated route share link for rider: {$rider->name}", $routeShare);
        
        return response()->json([
            'success' => true,
            'url' => $routeShare->getShareUrl(),
            'expires_at' => $routeShare->expires_at->format('M d, Y g:i A'),
        ]);
    }

    // Expenses Management
    public function expenses(Request $request)
    {
        $query = Expense::with('creator');

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->latest('expense_date')->paginate(20);

        // Calculate statistics
        $stats = [
            'total_expenses' => Expense::sum('amount'),
            'today_expenses' => Expense::whereDate('expense_date', today())->sum('amount'),
            'week_expenses' => Expense::whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'month_expenses' => Expense::whereMonth('expense_date', now()->month)
                                       ->whereYear('expense_date', now()->year)
                                       ->sum('amount'),
            
            // Revenue stats for profit calculation
            'total_revenue' => Order::where('status', 'delivered')->sum('price'),
            'today_revenue' => Order::whereDate('created_at', today())
                                    ->where('status', 'delivered')
                                    ->sum('price'),
            'week_revenue' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                                   ->where('status', 'delivered')
                                   ->sum('price'),
            'month_revenue' => Order::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->where('status', 'delivered')
                                    ->sum('price'),
        ];

        // Calculate profit
        $stats['total_profit'] = $stats['total_revenue'] - $stats['total_expenses'];
        $stats['today_profit'] = $stats['today_revenue'] - $stats['today_expenses'];
        $stats['week_profit'] = $stats['week_revenue'] - $stats['week_expenses'];
        $stats['month_profit'] = $stats['month_revenue'] - $stats['month_expenses'];

        return view('Admin::expenses.index', compact('expenses', 'stats'));
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        $expense = Expense::create($validated);

        ActivityLog::log('expense_created', "Created expense: {$expense->description} - ₦" . number_format($expense->amount, 2), $expense);

        return redirect()->route('admin.expenses')->with('success', 'Expense added successfully');
    }

    public function deleteExpense($id)
    {
        $expense = Expense::findOrFail($id);
        $description = $expense->description;
        $amount = $expense->amount;
        
        $expense->delete();

        ActivityLog::log('expense_deleted', "Deleted expense: {$description} - ₦" . number_format($amount, 2));

        return redirect()->route('admin.expenses')->with('success', 'Expense deleted successfully');
    }
}
