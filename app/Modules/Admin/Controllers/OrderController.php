<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user');

        // Status filtering - show all orders if no filter is specified
        $statusFilter = $request->get('status');
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Filter for orders without delivery dates
        if ($request->has('no_delivery_date') && $request->no_delivery_date == '1') {
            $query->whereNull('delivery_date');
        }

        // Filter by client
        $clientFilter = null;
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
            $clientFilter = Client::find($request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('sender_email', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhere('item_description', 'like', "%{$search}%");
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
            // Revenue statistics - only count delivered orders (based on delivery date)
            'revenue_today' => Order::whereDate('delivery_date', today())
                                    ->where('status', 'delivered')
                                    ->whereNotNull('delivery_date')
                                    ->sum('price'),
            'revenue_week' => Order::whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                                   ->where('status', 'delivered')
                                   ->whereNotNull('delivery_date')
                                   ->sum('price'),
            'revenue_month' => Order::whereMonth('delivery_date', now()->month)
                                    ->whereYear('delivery_date', now()->year)
                                    ->where('status', 'delivered')
                                    ->whereNotNull('delivery_date')
                                    ->sum('price'),
            // Today's delivered count
            'today_delivered' => Order::whereDate('delivery_date', today())
                                      ->where('status', 'delivered')
                                      ->whereNotNull('delivery_date')
                                      ->count(),
            // Today's incoming revenue (confirmed and in_transit orders created today)
            'incoming_revenue_today' => Order::whereDate('created_at', today())
                                             ->whereIn('status', ['confirmed', 'in_transit'])
                                             ->sum('price'),
            // This week's incoming revenue (confirmed and in_transit orders created this week)
            'incoming_revenue_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                                            ->whereIn('status', ['confirmed', 'in_transit'])
                                            ->sum('price'),
        ];

        $clients = Client::where('is_active', true)->where('pod_remittance_enabled', true)->orderBy('name')->get(['id', 'name', 'company_name']);

        return view('Admin::orders.index', compact('orders', 'stats', 'clientFilter', 'clients'));
    }

    public function create()
    {
        $stockClients = $this->stockClients();

        return view('Admin::orders.create', compact('stockClients'));
    }

    private function stockClients(): array
    {
        return Client::where('pod_remittance_enabled', true)
            ->whereHas('stocks')
            ->with('stocks')
            ->get()
            ->mapWithKeys(fn ($client) => [
                $client->id => $client->stocks->map(fn ($stock) => [
                    'id' => $stock->id,
                    'product_name' => $stock->product_name,
                    'quantity' => $stock->quantity,
                ])->values(),
            ])
            ->all();
    }

    private function syncStockItems(Order $order, Request $request): void
    {
        if (!$order->client_id || $order->stockItems()->whereNotNull('deducted_at')->exists()) {
            return;
        }

        $order->stockItems()->delete();

        $stocks = ClientStock::where('client_id', $order->client_id)->get()->keyBy('id');

        collect($request->input('stock_items', []))
            ->filter(fn ($item) => !empty($item['client_stock_id']) && $stocks->has($item['client_stock_id']))
            ->each(function ($item) use ($order, $stocks) {
                $stock = $stocks->get($item['client_stock_id']);
                $order->stockItems()->create([
                    'client_stock_id' => $stock->id,
                    'product_name' => $stock->product_name,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                ]);
            });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'source' => 'required|string|in:whatsapp,instagram,web,phone,walk-in,email,other',
            'source_contact' => 'nullable|string|max:255',
            'source_notes' => 'nullable|string|max:1000',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'pickup_city' => 'nullable|string|max:100',
            'delivery_address' => 'required|string',
            'delivery_city' => 'nullable|string|max:100',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'item_description' => 'required|string',
            'item_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'distance' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,pos,online,credit',
            'amount_received' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
            'priority_level' => 'required|in:normal,urgent,express',
            'is_failed_delivery' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'package_description' => 'nullable|string',
            'package_quantity' => 'nullable|integer|min:1',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'package_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'stock_items' => 'nullable|array',
            'stock_items.*.client_stock_id' => 'nullable|integer',
            'stock_items.*.quantity' => 'nullable|integer|min:1',
        ]);

        unset($validated['stock_items']);
        $validated['is_failed_delivery'] = $request->boolean('is_failed_delivery');

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

        $validated['customer_name'] = $validated['customer_name'] ?? $validated['sender_name'];
        $validated['customer_phone'] = $validated['customer_phone'] ?? $validated['sender_phone'];
        $validated['customer_email'] = $validated['customer_email'] ?? $validated['sender_email'];
        
        $validated['created_by'] = auth()->id();
        
        if ($request->boolean('mark_remitted') && isset($validated['amount_received'])) {
            $validated['remitted_at'] = now();
        }

        $order = Order::create($validated);

        $this->syncStockItems($order, $request);

        if ($order->remitted_at) {
            $order->deductStock();
        }

        ActivityLog::log('order_created', "Created order #{$order->order_number}", $order);

        return redirect()->route('admin.orders.show', $order->id)
            ->with('success', 'Order created successfully');
    }

    public function show($id)
    {
        $order = Order::with(['user', 'rider', 'stockItems'])->findOrFail($id);
        $riders = Rider::where('status', 'active')->orderBy('name')->get();
        return view('Admin::orders.show', compact('order', 'riders'));
    }

    public function edit($id)
    {
        $order = Order::with('stockItems')->findOrFail($id);
        $stockClients = $this->stockClients();

        return view('Admin::orders.edit', compact('order', 'stockClients'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'pickup_address' => 'required|string',
            'pickup_city' => 'nullable|string|max:100',
            'delivery_address' => 'required|string',
            'delivery_city' => 'nullable|string|max:100',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'item_description' => 'required|string',
            'item_size' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'distance' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,pos,online,credit',
            'amount_received' => 'nullable|numeric|min:0',
            'priority_level' => 'required|in:normal,urgent,express',
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled',
            'is_failed_delivery' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'package_description' => 'nullable|string',
            'package_quantity' => 'nullable|integer|min:1',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'package_image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'package_image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'stock_items' => 'nullable|array',
            'stock_items.*.client_stock_id' => 'nullable|integer',
            'stock_items.*.quantity' => 'nullable|integer|min:1',
        ]);

        unset($validated['stock_items']);
        $validated['is_failed_delivery'] = $request->boolean('is_failed_delivery');

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

        $validated['customer_name'] = $validated['customer_name'] ?? $validated['sender_name'];
        $validated['customer_phone'] = $validated['customer_phone'] ?? $validated['sender_phone'];
        $validated['customer_email'] = $validated['customer_email'] ?? $validated['sender_email'];

        $order->update($validated);

        $this->syncStockItems($order, $request);

        ActivityLog::log('order_updated', "Updated order #{$order->order_number}", $order);

        return redirect()->route('admin.orders.show', $order->id)
            ->with('success', 'Order updated successfully');
    }

    public function updateStatus(Request $request, $id)
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

        // Notify client by email when status changes to a meaningful state
        if ($oldStatus !== $request->status && $order->client_id) {
            try {
                $client = Client::find($order->client_id);
                if ($client && $client->email) {
                    Mail::send('emails.order_status_update', [
                        'order'      => $order,
                        'clientName' => $client->name,
                        'newStatus'  => $request->status,
                        'oldStatus'  => $oldStatus,
                    ], function ($message) use ($client, $order, $request) {
                        $label = ucwords(str_replace('_', ' ', $request->status));
                        $message->to($client->email)
                            ->subject("Order #{$order->order_number} is now {$label} — Waka Line Logistics");
                    });
                }
            } catch (\Exception $e) {
                Log::error('Order status email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'old_status' => $oldStatus,
                    'new_status' => $order->status,
                    'pickup_date' => $order->pickup_date,
                    'delivery_date' => $order->delivery_date,
                ]
            ]);
        }

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

        $rider = $request->rider_id ? Rider::find($request->rider_id) : null;
        $riderName = $rider ? $rider->name : 'None';
        $oldRiderName = $oldRiderId ? (Rider::find($oldRiderId)->name ?? 'Unknown') : 'None';

        ActivityLog::log('order_rider_assigned', "Assigned order #{$order->order_number} to rider: {$riderName}", $order, [
            'old_rider' => $oldRiderName,
            'new_rider' => $riderName,
        ]);

        // Notify client when a rider is newly assigned or changed
        if ($rider && $order->client_id) {
            try {
                $client = Client::find($order->client_id);
                if ($client && $client->email) {
                    Mail::send('emails.rider_assigned', [
                        'order'      => $order,
                        'rider'      => $rider,
                        'clientName' => $client->name,
                    ], function ($message) use ($client, $order) {
                        $message->to($client->email)
                            ->subject("Rider assigned to Order #{$order->order_number} — Waka Line Logistics");
                    });
                }
            } catch (\Exception $e) {
                Log::error('Rider assigned email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

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

    public function getPodRemittanceData(Request $request)
    {
        $request->validate(['client_id' => 'required|integer|exists:clients,id']);

        $client = Client::findOrFail($request->client_id);

        if (!$client->pod_remittance_enabled) {
            return response()->json(['error' => 'POD remittance is not enabled for this client.'], 403);
        }

        $orders = Order::where('client_id', $client->id)
            ->where('payment_method', 'cash')
            ->whereNull('remitted_at')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', 'delivered')->whereNotNull('amount_received');
                })->orWhere('is_failed_delivery', true);
            })
            ->with('stockItems:id,order_id,product_name,quantity')
            ->latest()
            ->get(['id', 'order_number', 'delivery_address', 'price', 'amount_received', 'delivery_date', 'is_failed_delivery']);

        $rows = $orders->map(fn($o) => [
            'id'               => $o->id,
            'order_number'     => $o->order_number,
            'delivery_address' => $o->delivery_address,
            'date'             => $o->delivery_date ? $o->delivery_date->format('M d, Y') : '—',
            'amount_received'  => $o->is_failed_delivery ? 0 : (float) $o->amount_received,
            'delivery_fee'     => (float) $o->price,
            'to_remit'         => $o->is_failed_delivery ? -(float) $o->price : max(0, (float) $o->amount_received - (float) $o->price),
            'is_failed'        => (bool) $o->is_failed_delivery,
            'products'         => $o->stockItems->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity'     => $item->quantity,
            ])->values(),
        ]);

        return response()->json([
            'client_name'      => $client->name . ($client->company_name ? ' (' . $client->company_name . ')' : ''),
            'client_id'        => $client->id,
            'orders'           => $rows,
            'total_remittance' => $rows->sum('to_remit'),
            'stocks'           => $client->stockAfterOrders($orders),
            'bank'             => [
                'bank_name'      => $client->bank_name,
                'account_name'   => $client->bank_account_name,
                'account_number' => $client->bank_account_number,
            ],
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $orderNumber = $order->order_number;
        
        $order->delete();

        ActivityLog::log('order_deleted', "Deleted order #{$orderNumber}");

        return redirect()->route('admin.orders')
            ->with('success', 'Order deleted successfully');
    }
}
