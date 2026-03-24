<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Setting;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\RouteShare;
use App\Modules\Admin\Models\Client;
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

        // Status filtering - show all orders if no filter is specified
        $statusFilter = $request->get('status');
        if ($statusFilter) {
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
        
        // Auto-set pickup_date when status changes to in_transit (item has been picked up)
        if ($request->status == 'in_transit' && !$order->pickup_date) {
            $order->pickup_date = now();
        }
        
        // Auto-set delivery_date when status changes to delivered
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
            'client_id' => 'nullable|exists:clients,id',
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
            'priority_level' => 'required|in:normal,high,urgent',
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
            'client_id' => 'nullable|exists:clients,id',
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
            'priority_level' => 'required|in:normal,high,urgent',
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
        
        // Get orders for route planning: assigned to rider but not yet delivered
        // Includes: 1) Orders not yet picked up (no pickup_date), 2) Orders in transit (no delivery_date)
        $pendingOrders = $rider->orders()
            ->where(function($query) {
                $query->whereNull('pickup_date') // Not yet picked up
                      ->orWhere(function($q) {
                          $q->whereNotNull('pickup_date') // Already picked up
                            ->whereNull('delivery_date'); // But not yet delivered
                      });
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();
        
        \Log::info("Route Planning Debug", [
            'rider_id' => $rider->id,
            'pending_orders_count' => $pendingOrders->count(),
            'orders' => $pendingOrders->pluck('order_number')->toArray(),
            'priorities' => $pendingOrders->map(function($o) {
                return ['order' => $o->order_number, 'priority' => $o->priority_level ?? 'NULL'];
            })->toArray()
        ]);
        
        // Build route waypoints with proximity-based smart routing
        $startingPoint = 'Iju Ishaga, Lagos, Nigeria';
        $allStops = [];
        
        // Collect all stops (pickups and drop-offs)
        foreach ($pendingOrders as $order) {
            // For orders not yet picked up: add both pickup AND dropoff
            if (!$order->pickup_date) {
                // Add pickup stop
                if ($order->pickup_address) {
                    $allStops[] = [
                        'address' => $order->pickup_address,
                        'type' => 'pickup',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'sender' => $order->sender_name ?? $order->customer_name,
                        'phone' => $order->sender_phone ?? $order->customer_phone,
                        'priority' => 1,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
                
                // Add dropoff stop (will be done after pickup due to constraint)
                if ($order->delivery_address) {
                    $allStops[] = [
                        'address' => $order->delivery_address,
                        'type' => 'dropoff',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'receiver' => $order->receiver_name ?? 'N/A',
                        'phone' => $order->receiver_phone ?? 'N/A',
                        'priority' => 2,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
            }
            // For orders already picked up: add only dropoff
            elseif ($order->pickup_date && !$order->delivery_date) {
                if ($order->delivery_address) {
                    $allStops[] = [
                        'address' => $order->delivery_address,
                        'type' => 'dropoff',
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'receiver' => $order->receiver_name ?? 'N/A',
                        'phone' => $order->receiver_phone ?? 'N/A',
                        'priority' => 2,
                        'paired_order_id' => $order->id,
                        'status' => $order->status,
                        'priority_level' => $order->priority_level ?? 'normal',
                        'item_description' => $order->item_description ?? 'N/A',
                    ];
                }
            }
        }
        
        \Log::info("All Stops Collected", ['count' => count($allStops), 'stops' => $allStops]);
        
        // Optimize route using nearest neighbor algorithm with constraints
        $waypoints = $this->optimizeRoute($allStops, $startingPoint);
        
        \Log::info("Waypoints After Optimization", ['count' => count($waypoints), 'waypoints' => $waypoints]);
        
        // Calculate cumulative time and ETA for each stop
        $cumulativeTime = 0;
        foreach ($waypoints as &$waypoint) {
            // Estimate time: ~5 min per km in Lagos traffic + 5 min stop time
            $estimatedTravelTime = 15;
            $cumulativeTime += $estimatedTravelTime + 5; // travel + stop time
            
            $waypoint['estimated_time'] = $cumulativeTime;
            $waypoint['eta'] = now()->addMinutes($cumulativeTime)->format('g:i A');
        }
        
        // Generate WhatsApp share text
        $whatsappText = $this->generateWhatsAppRouteText($rider, $waypoints, $startingPoint);
        
        // Generate Google Maps URL
        $googleMapsUrl = $this->generateGoogleMapsUrl($startingPoint, $waypoints);
        
        return view('Admin::riders.show', compact('rider', 'pendingOrders', 'startingPoint', 'waypoints', 'whatsappText', 'googleMapsUrl'));
    }
    
    private function generateWhatsAppRouteText($rider, $waypoints, $startingPoint)
    {
        if (empty($waypoints)) {
            return "No active deliveries at the moment.";
        }
        
        $text = "*Drop-off Route for {$rider->name}*\n\n";
        $text .= "*Starting Point:* {$startingPoint}\n\n";
        $text .= "*Route Plan:*\n";
        
        $stepNumber = 1;
        $previousTime = 0;
        
        foreach ($waypoints as $waypoint) {
            $timeToNextStop = $waypoint['estimated_time'] - $previousTime;
            
            if ($waypoint['type'] === 'pickup') {
                $text .= "{$stepNumber}. *PICKUP* - Order #{$waypoint['order_number']}\n";
                $text .= "   From: {$waypoint['sender']}\n";
                if (isset($waypoint['phone'])) {
                    $text .= "   Phone: {$waypoint['phone']}\n";
                }
                $text .= "   Location: {$waypoint['address']}\n";
                $text .= "   Time to reach: ~{$timeToNextStop} min\n";
                $text .= "   ETA: {$waypoint['eta']}\n\n";
            } else {
                $text .= "{$stepNumber}. *DROP OFF* - Order #{$waypoint['order_number']}\n";
                $text .= "   To: {$waypoint['receiver']}\n";
                if (isset($waypoint['phone'])) {
                    $text .= "   Phone: {$waypoint['phone']}\n";
                }
                $text .= "   Location: {$waypoint['address']}\n";
                $text .= "   Time to reach: ~{$timeToNextStop} min\n";
                $text .= "   ETA: {$waypoint['eta']}\n\n";
            }
            
            $previousTime = $waypoint['estimated_time'];
            $stepNumber++;
        }
        
        $text .= "Total Stops: " . count($waypoints) . "\n";
        $text .= "Total Journey Time: ~" . end($waypoints)['estimated_time'] . " minutes";
        
        return urlencode($text);
    }
    
    /**
     * Generate Google Maps URL with route waypoints
     */
    private function generateGoogleMapsUrl($startingPoint, $waypoints)
    {
        if (empty($waypoints)) {
            return null;
        }
        
        // Google Maps Directions URL format:
        // https://www.google.com/maps/dir/?api=1&origin=START&destination=END&waypoints=WAYPOINT1|WAYPOINT2|...
        
        $origin = urlencode($startingPoint);
        $destination = urlencode($waypoints[count($waypoints) - 1]['address']); // Last stop
        
        // Build waypoints string (all stops except the last one)
        $waypointAddresses = [];
        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $waypointAddresses[] = urlencode($waypoints[$i]['address']);
        }
        $waypointsParam = implode('|', $waypointAddresses);
        
        // Construct Google Maps URL
        $url = "https://www.google.com/maps/dir/?api=1&origin={$origin}&destination={$destination}";
        
        if (!empty($waypointsParam)) {
            $url .= "&waypoints={$waypointsParam}";
        }
        
        // Add travel mode (driving)
        $url .= "&travelmode=driving";
        
        return $url;
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
    
    /**
     * Optimize route using smart hybrid approach with priority handling:
     * - Urgent and high-priority orders are visited first
     * - Dropoffs for already-picked-up orders can be delivered anytime (most flexible)
     * - New pickups must happen before their corresponding dropoffs
     * - Uses nearest neighbor with these constraints
     */
    private function optimizeRoute($stops, $startingPoint)
    {
        if (empty($stops)) {
            return [];
        }
        
        // Identify which orders are already picked up (from previous routes)
        $pickups = array_filter($stops, function($s) { return $s['type'] === 'pickup'; });
        $dropoffs = array_filter($stops, function($s) { return $s['type'] === 'dropoff'; });
        
        $alreadyPickedUpOrders = [];
        foreach ($dropoffs as $dropoff) {
            $hasPickupInRoute = false;
            foreach ($pickups as $pickup) {
                if ($pickup['paired_order_id'] === $dropoff['paired_order_id']) {
                    $hasPickupInRoute = true;
                    break;
                }
            }
            if (!$hasPickupInRoute) {
                $alreadyPickedUpOrders[] = $dropoff['paired_order_id'];
            }
        }
        
        // Separate stops by priority level
        $urgentStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'urgent'; });
        $highPriorityStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'high'; });
        $normalStops = array_filter($stops, function($s) { return ($s['priority_level'] ?? 'normal') === 'normal'; });
        
        \Log::info("Priority Separation", [
            'urgent_count' => count($urgentStops),
            'high_count' => count($highPriorityStops),
            'normal_count' => count($normalStops),
            'urgent_orders' => array_map(fn($s) => $s['order_number'], $urgentStops),
            'high_orders' => array_map(fn($s) => $s['order_number'], $highPriorityStops),
        ]);
        
        // Combine in priority order: urgent first, then high, then normal
        $prioritizedStops = array_merge(array_values($urgentStops), array_values($highPriorityStops), array_values($normalStops));
        
        $optimizedRoute = [];
        $remainingStops = $prioritizedStops;
        $currentLocation = $startingPoint;
        $pickedUpOrders = $alreadyPickedUpOrders; // Orders already in vehicle
        
        // Use nearest neighbor with smart constraints and priority
        while (!empty($remainingStops)) {
            $nearestStop = null;
            $nearestDistance = PHP_FLOAT_MAX;
            $nearestIndex = -1;
            $currentPriorityLevel = null;
            
            // First pass: find the highest priority level among valid stops
            foreach ($remainingStops as $stop) {
                if ($stop['type'] === 'dropoff' && !in_array($stop['paired_order_id'], $pickedUpOrders)) {
                    continue;
                }
                $priorityLevel = $stop['priority_level'] ?? 'normal';
                if ($currentPriorityLevel === null || $this->getPriorityWeight($priorityLevel) > $this->getPriorityWeight($currentPriorityLevel)) {
                    $currentPriorityLevel = $priorityLevel;
                }
            }
            
            // Second pass: find nearest stop among highest priority stops
            foreach ($remainingStops as $index => $stop) {
                // Constraint: Can only drop-off if item has been picked up
                if ($stop['type'] === 'dropoff' && !in_array($stop['paired_order_id'], $pickedUpOrders)) {
                    continue; // Skip this drop-off, item not picked up yet
                }
                
                // Only consider stops with current highest priority level
                if (($stop['priority_level'] ?? 'normal') !== $currentPriorityLevel) {
                    continue;
                }
                
                $distance = $this->estimateDistance($currentLocation, $stop['address']);
                
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestStop = $stop;
                    $nearestIndex = $index;
                }
            }
            
            if ($nearestStop === null) {
                // No valid stops found (shouldn't happen, but safety check)
                break;
            }
            
            // Add to optimized route
            $optimizedRoute[] = $nearestStop;
            
            // Track pickups
            if ($nearestStop['type'] === 'pickup') {
                $pickedUpOrders[] = $nearestStop['paired_order_id'];
            }
            
            // Update current location
            $currentLocation = $nearestStop['address'];
            
            // Remove from remaining stops
            array_splice($remainingStops, $nearestIndex, 1);
        }
        
        return $optimizedRoute;
    }
    
    /**
     * Get priority weight for sorting (higher = more urgent)
     */
    private function getPriorityWeight($priorityLevel)
    {
        return match($priorityLevel) {
            'urgent' => 3,
            'high' => 2,
            'normal' => 1,
            default => 1,
        };
    }
    
    /**
     * Estimate distance between two addresses using Google Maps Distance Matrix API
     */
    private function estimateDistance($address1, $address2)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        // If API key is available, use Google Maps Distance Matrix API
        if ($apiKey) {
            try {
                $distance = $this->getGoogleMapsDistance($address1, $address2, $apiKey);
                if ($distance !== null) {
                    return $distance;
                }
            } catch (\Exception $e) {
                \Log::warning('Google Maps API failed, using fallback', [
                    'error' => $e->getMessage(),
                    'address1' => $address1,
                    'address2' => $address2
                ]);
            }
        }
        
        // Fallback to area-based estimation
        return $this->estimateDistanceByArea($address1, $address2);
    }
    
    /**
     * Get actual distance using Google Maps Distance Matrix API with caching
     */
    private function getGoogleMapsDistance($origin, $destination, $apiKey)
    {
        // Create cache key based on normalized addresses
        $cacheKey = 'gmaps_distance_' . md5(strtolower(trim($origin)) . '|' . strtolower(trim($destination)));
        
        // Check if we have cached result (valid for 7 days)
        $cachedDistance = \Cache::get($cacheKey);
        if ($cachedDistance !== null) {
            return $cachedDistance;
        }
        
        $origin = urlencode($origin);
        $destination = urlencode($destination);
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&key={$apiKey}&mode=driving";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            return null;
        }
        
        if (!isset($data['rows'][0]['elements'][0]['distance']['value'])) {
            // If Google can't find the address, try with simplified address (city/area only)
            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'NOT_FOUND') {
                $simplifiedOrigin = $this->extractCityArea($origin);
                $simplifiedDestination = $this->extractCityArea($destination);
                
                if ($simplifiedOrigin !== $origin || $simplifiedDestination !== $destination) {
                    // Try again with simplified addresses
                    return $this->getGoogleMapsDistance($simplifiedOrigin, $simplifiedDestination, $apiKey);
                }
            }
            return null;
        }
        
        // Distance is in meters, convert to kilometers
        $distanceInMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        $distanceInKm = $distanceInMeters / 1000;
        
        // Cache the result for 7 days (604800 seconds)
        \Cache::put($cacheKey, $distanceInKm, 604800);
        
        return $distanceInKm;
    }
    
    /**
     * Extract city/area from full address for fallback when Google can't find address
     * Example: "21b Fatal Idowu Arobieke Lekki Phase 1 Lagos" -> "Lekki Phase 1 Lagos"
     */
    private function extractCityArea($address)
    {
        // Common Lagos areas and landmarks
        $areas = [
            'Lekki Phase 1', 'Lekki Phase 2', 'Lekki', 'Ajah', 'Victoria Island', 'VI', 'Ikoyi',
            'Ikeja', 'Surulere', 'Yaba', 'Gbagada', 'Maryland', 'Ojota', 'Ketu', 'Ikorodu',
            'Festac', 'Apapa', 'Isolo', 'Oshodi', 'Mushin', 'Bariga', 'Somolu', 'Agege',
            'Egbeda', 'Alimosho', 'Ifako', 'Abule Egba', 'Iyana Ipaja', 'Badagry', 'Epe',
            'Magodo', 'Omole', 'Berger', 'Ojodu', 'Ogba', 'Anthony', 'Obanikoro',
            'Palmgrove', 'Onipanu', 'Fadeyi', 'Jibowu', 'Baruwa', 'Igando', 'Isheri',
            'Iju Ishaga', 'Iju', 'Ishaga', 'Ejigbo', 'Idimu', 'Ikotun', 'Akowonjo'
        ];
        
        $addressLower = strtolower($address);
        
        // Try to find area in address
        foreach ($areas as $area) {
            if (stripos($addressLower, strtolower($area)) !== false) {
                // Extract from area onwards, include "Lagos" if present
                $pattern = '/' . preg_quote($area, '/') . '.*?Lagos/i';
                if (preg_match($pattern, $address, $matches)) {
                    return trim($matches[0]);
                }
                // If no "Lagos" found, just return area + Lagos
                return $area . ' Lagos';
            }
        }
        
        // If no specific area found, try to extract last 2-3 words (usually area + city)
        $words = explode(' ', trim($address));
        if (count($words) >= 2) {
            return implode(' ', array_slice($words, -2));
        }
        
        return $address; // Return original if can't simplify
    }
    
    /**
     * Fallback: Estimate distance using area/location matching
     */
    private function estimateDistanceByArea($address1, $address2)
    {
        // Extract area names from addresses (common Lagos areas)
        $lagosAreas = [
            'ikeja', 'lekki', 'vi', 'victoria island', 'ikoyi', 'surulere', 'yaba', 
            'gbagada', 'maryland', 'ojota', 'ketu', 'mile 12', 'ikorodu', 'ajah',
            'festac', 'apapa', 'isolo', 'oshodi', 'mushin', 'bariga', 'somolu',
            'agege', 'egbeda', 'alimosho', 'ifako', 'abule egba', 'iyana ipaja',
            'badagry', 'epe', 'magodo', 'omole', 'berger', 'ojodu', 'ogba',
            'anthony', 'obanikoro', 'palmgrove', 'onipanu', 'fadeyi', 'jibowu'
        ];
        
        $addr1Lower = strtolower($address1);
        $addr2Lower = strtolower($address2);
        
        // Find areas in both addresses
        $area1 = null;
        $area2 = null;
        
        foreach ($lagosAreas as $area) {
            if (strpos($addr1Lower, $area) !== false) {
                $area1 = $area;
                break;
            }
        }
        
        foreach ($lagosAreas as $area) {
            if (strpos($addr2Lower, $area) !== false) {
                $area2 = $area;
                break;
            }
        }
        
        // If same area, very close
        if ($area1 && $area2 && $area1 === $area2) {
            return 1 + (rand(0, 5) / 10); // 1-1.5 km
        }
        
        // If both areas found but different, use predefined distances
        if ($area1 && $area2) {
            return $this->getAreaDistance($area1, $area2);
        }
        
        // Fallback to string similarity
        similar_text($addr1Lower, $addr2Lower, $percent);
        $estimatedDistance = 100 - $percent;
        
        return max(5, $estimatedDistance); // Minimum 5km if no area match
    }
    
    /**
     * Get approximate distance between two Lagos areas
     */
    private function getAreaDistance($area1, $area2)
    {
        // Simplified distance matrix for major Lagos areas (in km)
        $distances = [
            'ikeja' => ['lekki' => 25, 'vi' => 15, 'yaba' => 10, 'surulere' => 8, 'ikorodu' => 30],
            'lekki' => ['ikeja' => 25, 'vi' => 8, 'yaba' => 20, 'ajah' => 10, 'ikorodu' => 35],
            'vi' => ['ikeja' => 15, 'lekki' => 8, 'yaba' => 12, 'ikoyi' => 3],
            'yaba' => ['ikeja' => 10, 'lekki' => 20, 'vi' => 12, 'surulere' => 5, 'gbagada' => 8],
            'surulere' => ['ikeja' => 8, 'yaba' => 5, 'isolo' => 6, 'oshodi' => 7],
            'ikorodu' => ['ikeja' => 30, 'lekki' => 35, 'gbagada' => 20, 'ketu' => 15],
        ];
        
        // Check if we have a predefined distance
        if (isset($distances[$area1][$area2])) {
            return $distances[$area1][$area2] + (rand(0, 10) / 10);
        }
        
        if (isset($distances[$area2][$area1])) {
            return $distances[$area2][$area1] + (rand(0, 10) / 10);
        }
        
        // Default distance if not in matrix
        return 15 + (rand(0, 20) / 10); // 15-17 km average
    }

    // Client Management
    public function clients(Request $request)
    {
        $query = Client::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $clients = $query->orderBy('name', 'asc')->paginate(20);

        return view('Admin::clients.index', compact('clients'));
    }

    public function createClient()
    {
        return view('Admin::clients.create');
    }

    public function storeClient(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'business_address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'payment_terms' => 'nullable|in:prepaid,postpaid,credit_30,credit_60',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'onboarded_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $client = Client::create($validated);

        ActivityLog::log('client_created', "Created client: {$client->name}", $client);

        return redirect()->route('admin.clients')->with('success', 'Client created successfully');
    }

    public function editClient($id)
    {
        $client = Client::findOrFail($id);
        return view('Admin::clients.edit', compact('client'));
    }

    public function updateClient(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'business_address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'payment_terms' => 'nullable|in:prepaid,postpaid,credit_30,credit_60',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'onboarded_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $client->update($validated);

        ActivityLog::log('client_updated', "Updated client: {$client->name}", $client);

        return redirect()->route('admin.clients')->with('success', 'Client updated successfully');
    }

    public function deleteClient($id)
    {
        $client = Client::findOrFail($id);
        $clientName = $client->name;

        // Check if client has orders
        if ($client->orders()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete client with existing orders. Consider deactivating instead.']);
        }

        $client->delete();

        ActivityLog::log('client_deleted', "Deleted client: {$clientName}");

        return redirect()->route('admin.clients')->with('success', 'Client deleted successfully');
    }

    public function getClientData($id)
    {
        $client = Client::findOrFail($id);
        
        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'pickup_address' => $client->pickup_address,
        ]);
    }
}
