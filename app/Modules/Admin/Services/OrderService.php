<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientStock;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public const STATUSES = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];

    private const IMAGE_FIELDS = ['package_image_1', 'package_image_2', 'package_image_3', 'package_image_4'];

    private const FILE_FIELDS = ['additional_file_1', 'additional_file_2', 'additional_file_3'];

    public function listQuery(Request $request): Builder
    {
        $query = Order::query();

        $status = $request->get('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->get('no_delivery_date') == '1') {
            $query->whereNull('delivery_date');
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                foreach (['order_number', 'customer_name', 'customer_phone', 'sender_name', 'sender_phone', 'sender_email', 'receiver_name', 'receiver_phone', 'pickup_address', 'delivery_address', 'item_description'] as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        switch ($request->get('date_filter', '')) {
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
                $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                break;
            case 'this_year':
                $query->whereYear('created_at', now()->year);
                break;
            case 'custom':
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
                break;
        }

        return $query;
    }

    public function listStats(): array
    {
        $delivered = fn () => Order::where('status', 'delivered')->whereNotNull('delivery_date');
        $incoming = fn () => Order::whereIn('status', ['confirmed', 'in_transit']);

        return [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'in_transit' => Order::where('status', 'in_transit')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'revenue_today' => $delivered()->whereDate('delivery_date', today())->sum('price'),
            'revenue_week' => $delivered()->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('price'),
            'revenue_month' => $delivered()->whereMonth('delivery_date', now()->month)->whereYear('delivery_date', now()->year)->sum('price'),
            'today_delivered' => $delivered()->whereDate('delivery_date', today())->count(),
            'incoming_revenue_today' => $incoming()->whereDate('created_at', today())->sum('price'),
            'incoming_revenue_week' => $incoming()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('price'),
        ];
    }

    public function remittanceClients(): Collection
    {
        return Client::where('is_active', true)
            ->where('pod_remittance_enabled', true)
            ->orderBy('name')
            ->get(['id', 'name', 'company_name']);
    }

    public function rules(bool $creating): array
    {
        $rules = [
            'client_id' => 'nullable|exists:clients,id',
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
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'priority_level' => 'required|in:normal,high,urgent,express',
            'is_failed_delivery' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'package_description' => 'nullable|string',
            'package_quantity' => 'nullable|integer|min:1',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'stock_items' => 'nullable|array',
            'stock_items.*.client_stock_id' => 'nullable|integer',
            'stock_items.*.quantity' => 'nullable|integer|min:1',
        ];

        foreach (self::IMAGE_FIELDS as $field) {
            $rules[$field] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120';
        }

        foreach (self::FILE_FIELDS as $field) {
            $rules[$field] = 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120';
        }

        if ($creating) {
            $rules['source'] = 'required|string|in:whatsapp,instagram,web,phone,walk-in,email,other';
            $rules['source_contact'] = 'nullable|string|max:255';
            $rules['source_notes'] = 'nullable|string|max:1000';
        }

        return $rules;
    }

    public function create(array $validated, Request $request): Order
    {
        $data = $this->prepare($validated, $request);
        $data['created_by'] = $request->user()?->id;

        if ($request->boolean('mark_remitted') && isset($data['amount_received'])) {
            $data['remitted_at'] = now();
        }

        $order = Order::create($data);

        $this->syncStockItems($order, $request);

        if ($order->remitted_at) {
            $order->deductStock();
        }

        ActivityLog::log('order_created', "Created order #{$order->order_number}", $order);

        return $order;
    }

    public function update(Order $order, array $validated, Request $request): Order
    {
        $order->update($this->prepare($validated, $request));

        $this->syncStockItems($order, $request);

        ActivityLog::log('order_updated', "Updated order #{$order->order_number}", $order);

        return $order;
    }

    public function updateStatus(Order $order, string $status): string
    {
        $oldStatus = $order->status;
        $order->status = $status;

        if ($status === 'in_transit' && !$order->pickup_date) {
            $order->pickup_date = now();
        }

        if ($status === 'delivered' && !$order->delivery_date) {
            $order->delivery_date = now();
        }

        $order->save();

        ActivityLog::log('order_status_updated', "Updated order #{$order->order_number} status from {$oldStatus} to {$status}", $order, [
            'old_status' => $oldStatus,
            'new_status' => $status,
        ]);

        if ($oldStatus !== $status && $order->client_id) {
            $this->notifyClient($order, 'emails.order_status_update', [
                'newStatus' => $status,
                'oldStatus' => $oldStatus,
            ], "Order #{$order->order_number} is now " . ucwords(str_replace('_', ' ', $status)) . ' — Waka Line Logistics');
        }

        return $oldStatus;
    }

    public function assignRider(Order $order, ?int $riderId): void
    {
        $oldRiderId = $order->rider_id;
        $order->rider_id = $riderId;
        $order->save();

        $rider = $riderId ? Rider::find($riderId) : null;
        $riderName = $rider ? $rider->name : 'None';
        $oldRiderName = $oldRiderId ? (Rider::find($oldRiderId)->name ?? 'Unknown') : 'None';

        ActivityLog::log('order_rider_assigned', "Assigned order #{$order->order_number} to rider: {$riderName}", $order, [
            'old_rider' => $oldRiderName,
            'new_rider' => $riderName,
        ]);

        if ($rider && $order->client_id) {
            $this->notifyClient($order, 'emails.rider_assigned', ['rider' => $rider], "Rider assigned to Order #{$order->order_number} — Waka Line Logistics");
        }
    }

    public function stockClients(): array
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

    public function syncStockItems(Order $order, Request $request): void
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

    public function podRemittanceData(Client $client): array
    {
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

        $rows = $orders->map(fn ($o) => [
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

        return [
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
        ];
    }

    public function markRemitted(int $clientId, array $orderIds): int
    {
        return DB::transaction(function () use ($clientId, $orderIds) {
            $orders = Order::whereIn('id', $orderIds)
                ->where('client_id', $clientId)
                ->whereNull('remitted_at')
                ->get();

            foreach ($orders as $order) {
                $order->update(['remitted_at' => now()]);
                $order->deductStock();
            }

            return $orders->count();
        });
    }

    private function prepare(array $validated, Request $request): array
    {
        unset($validated['stock_items']);
        $validated['is_failed_delivery'] = $request->boolean('is_failed_delivery');

        foreach (self::IMAGE_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $this->storeUpload($request, $field, 'orders/packages');
            }
        }

        foreach (self::FILE_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $this->storeUpload($request, $field, 'orders/additional');
            }
        }

        $validated['customer_name'] = $validated['customer_name'] ?? $validated['sender_name'];
        $validated['customer_phone'] = $validated['customer_phone'] ?? $validated['sender_phone'];
        $validated['customer_email'] = $validated['customer_email'] ?? $validated['sender_email'] ?? null;

        return $validated;
    }

    private function storeUpload(Request $request, string $field, string $directory): string
    {
        $file = $request->file($field);
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'public');
    }

    private function notifyClient(Order $order, string $view, array $data, string $subject): void
    {
        try {
            $client = Client::find($order->client_id);
            if (!$client || !$client->email) {
                return;
            }

            Mail::send($view, array_merge(['order' => $order, 'clientName' => $client->name], $data), function ($message) use ($client, $subject) {
                $message->to($client->email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Order client notification failed', ['order_id' => $order->id, 'view' => $view, 'error' => $e->getMessage()]);
        }
    }
}
