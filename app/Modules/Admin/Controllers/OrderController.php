<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Services\OrderService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $orders = $this->orders->listQuery($request)->with('user')->latest()->paginate(20);
        $clientFilter = $request->filled('client_id') ? Client::find($request->client_id) : null;
        $stats = $this->orders->listStats();
        $clients = $this->orders->remittanceClients();

        return view('Admin::orders.index', compact('orders', 'stats', 'clientFilter', 'clients'));
    }

    public function create()
    {
        $stockClients = $this->orders->stockClients();

        return view('Admin::orders.create', compact('stockClients'));
    }

    public function store(Request $request)
    {
        $order = $this->orders->create($request->validate($this->orders->rules(true)), $request);

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
        $stockClients = $this->orders->stockClients();

        return view('Admin::orders.edit', compact('order', 'stockClients'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $this->orders->update($order, $request->validate($this->orders->rules(false)), $request);

        return redirect()->route('admin.orders.show', $order->id)
            ->with('success', 'Order updated successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', OrderService::STATUSES),
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $this->orders->updateStatus($order, $request->status);

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

        $this->orders->assignRider(Order::findOrFail($id), $request->rider_id);

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

        return response()->json($this->orders->podRemittanceData($client));
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
