<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use App\Models\ClientShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status != '') {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $clients = $query->orderBy('name', 'asc')->paginate(20);

        return view('Admin::clients.index', compact('clients'));
    }

    public function create()
    {
        return view('Admin::clients.create');
    }

    public function store(Request $request)
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

    public function show($id)
    {
        $client = Client::with(['orders' => function($query) {
            $query->latest()->take(10);
        }])->findOrFail($id);

        $totalOrders = $client->orders()->count();
        $completedOrders = $client->orders()->where('status', 'delivered')->count();
        $pendingOrders = $client->orders()->whereIn('status', ['pending', 'picked_up', 'in_transit'])->count();
        $totalRevenue = $client->orders()->where('status', 'delivered')->sum('price');

        $subscriptionPlans = SubscriptionPlan::active()->orderBy('sort_order')->orderBy('price')->get();
        $clientCredit = $client->getOrCreateCredits();
        $activeSubscription = $client->activeSubscription()->with('plan')->first();

        $podPendingOrders = $client->orders()
            ->where('payment_method', 'cash')
            ->whereNull('remitted_at')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', 'delivered')->whereNotNull('amount_received');
                })->orWhere('is_failed_delivery', true);
            })
            ->with('stockItems')
            ->latest()
            ->get();

        $podStockAfter = $client->stockAfterOrders($podPendingOrders);
        $podTotalReceived = $podPendingOrders->sum(fn ($o) => $o->is_failed_delivery ? 0 : (float) $o->amount_received);
        $podTotalFees = $podPendingOrders->sum(fn ($o) => (float) $o->price);

        $podTotalRemittance = $podPendingOrders->sum(function ($o) {
            if ($o->is_failed_delivery) {
                return -(float) $o->price;
            }
            return max(0, (float) $o->amount_received - (float) $o->price);
        });

        $invoiceableOrders = $client->orders()
            ->latest()
            ->get(['id', 'order_number', 'status', 'price', 'created_at', 'delivery_date', 'pickup_address', 'delivery_address', 'item_description']);

        $invoicedOrderIds = DB::table('invoice_order')
            ->whereIn('order_id', $invoiceableOrders->pluck('id'))
            ->pluck('order_id')
            ->unique()
            ->flip();

        $invoices = Invoice::where('client_id', $client->id)
            ->withCount('orders')
            ->latest()
            ->get();

        $webhook = ClientIntegration::where('client_id', $client->id)
            ->where('integration_type', 'webhook')
            ->first();

        $webhookLogs = [];
        if ($webhook) {
            $webhookLogs = IntegrationLog::where('client_integration_id', $webhook->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        $stocks = $client->stocks()->get();

        return view('Admin::clients.show', compact(
            'client', 'totalOrders', 'completedOrders', 'pendingOrders', 'totalRevenue',
            'subscriptionPlans', 'clientCredit', 'activeSubscription',
            'podPendingOrders', 'podTotalRemittance', 'podStockAfter', 'podTotalReceived', 'podTotalFees',
            'invoiceableOrders', 'invoicedOrderIds', 'invoices',
            'webhook', 'webhookLogs', 'stocks'
        ));
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('Admin::clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
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
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:20',
            'zone_rates' => 'nullable|array',
            'zone_rates.*' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['zone_rates'])) {
            $validated['zone_rates'] = json_encode($validated['zone_rates']);
        }

        $client->update($validated);

        ActivityLog::log('client_updated', "Updated client: {$client->name}", $client);

        return redirect()->route('admin.clients.show', $id)->with('success', 'Client updated successfully');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $clientName = $client->name;
        
        if ($client->orders()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete client with existing orders']);
        }

        $client->delete();

        ActivityLog::log('client_deleted', "Deleted client: {$clientName}");

        return redirect()->route('admin.clients')->with('success', 'Client deleted successfully');
    }

    public function getData($id)
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

    public function generateShareLink($id)
    {
        $client = Client::findOrFail($id);
        
        ClientShare::where('client_id', $client->id)->delete();
        
        $clientShare = ClientShare::create([
            'client_id' => $client->id,
            'token' => ClientShare::generateToken(),
            'expires_at' => now()->addYears(10),
            'is_active' => true,
        ]);
        
        ActivityLog::log('client_share_created', "Enabled order tracking for client: {$client->name}", $clientShare);
        
        $shareUrl = config('app.frontend_url') . '/share/' . $clientShare->token;
        
        return response()->json([
            'success' => true,
            'url' => $shareUrl,
        ]);
    }

    public function disableShareLink($id)
    {
        $client = Client::findOrFail($id);
        
        ClientShare::where('client_id', $client->id)
            ->update(['is_active' => false]);
        
        ActivityLog::log('client_share_disabled', "Disabled order tracking for client: {$client->name}");
        
        return response()->json([
            'success' => true,
            'message' => 'Order tracking link disabled successfully',
        ]);
    }

    public function generateApiKey($id)
    {
        $client = Client::findOrFail($id);
        
        $apiKey = $client->generateApiKey();
        
        ActivityLog::log('api_key_generated', "Generated API key for client: {$client->name}", $client);
        
        return response()->json([
            'success' => true,
            'message' => 'API key generated successfully',
            'api_key' => $apiKey,
            'generated_at' => $client->api_key_generated_at->format('M d, Y h:i A'),
        ]);
    }

    public function regenerateApiKey($id)
    {
        $client = Client::findOrFail($id);
        
        $apiKey = $client->regenerateApiKey();
        
        ActivityLog::log('api_key_regenerated', "Regenerated API key for client: {$client->name}", $client);
        
        return response()->json([
            'success' => true,
            'message' => 'API key regenerated successfully',
            'api_key' => $apiKey,
            'generated_at' => $client->api_key_generated_at->format('M d, Y h:i A'),
        ]);
    }

    public function enableApiAccess($id)
    {
        $client = Client::findOrFail($id);
        
        $apiKey = $client->enableApiAccess();
        
        ActivityLog::log('api_access_enabled', "Enabled API access for client: {$client->name}", $client);
        
        return response()->json([
            'success' => true,
            'message' => 'API access enabled successfully',
            'api_key' => $apiKey,
            'generated_at' => $client->api_key_generated_at->format('M d, Y h:i A'),
        ]);
    }

    public function disableApiAccess($id)
    {
        $client = Client::findOrFail($id);
        
        $client->disableApiAccess();
        
        ActivityLog::log('api_access_disabled', "Disabled API access for client: {$client->name}", $client);
        
        return response()->json([
            'success' => true,
            'message' => 'API access disabled successfully',
        ]);
    }

    public function subscribe(Request $request, $id)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $client = Client::findOrFail($id);
        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        DB::beginTransaction();
        try {
            $client->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $startsAt = Carbon::parse($validated['starts_at']);
            $expiresAt = $plan->validity_days ? $startsAt->copy()->addDays($plan->validity_days) : null;

            $subscription = \App\Modules\Admin\Models\ClientSubscription::create([
                'client_id' => $client->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'credits_allocated' => $plan->credits,
                'credits_used' => 0,
                'amount_paid' => 0,
                'auto_renew' => false,
            ]);

            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;
            $clientCredit->addCredits($plan->credits);

            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'purchase',
                'status' => 'completed',
                'credits' => $plan->credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'subscription_plan_id' => $plan->id,
                'amount_paid' => 0,
                'payment_method' => 'manual',
                'description' => "Manual subscription: {$plan->name}" . ($validated['notes'] ? " — {$validated['notes']}" : ''),
                'processed_by' => auth()->id(),
                'metadata' => ['admin_note' => $validated['notes'] ?? null, 'subscription_id' => $subscription->id],
            ]);

            DB::commit();

            return back()->with('success', "Client subscribed to {$plan->name} successfully. {$plan->credits} credits added.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to subscribe client: ' . $e->getMessage());
        }
    }

    public function addCredits(Request $request, $id)
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($id);

        DB::beginTransaction();
        try {
            $clientCredit = $client->getOrCreateCredits();
            $balanceBefore = $clientCredit->available_credits;
            $clientCredit->addCredits($validated['credits']);

            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'adjustment',
                'status' => 'completed',
                'credits' => $validated['credits'],
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'amount_paid' => 0,
                'payment_method' => 'manual',
                'description' => "Manual credit addition: {$validated['reason']}",
                'processed_by' => auth()->id(),
                'metadata' => ['admin_note' => $validated['reason'], 'adjustment_type' => 'add'],
            ]);

            DB::commit();

            return back()->with('success', "{$validated['credits']} credits added to {$client->name} successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add credits: ' . $e->getMessage());
        }
    }

    public function deductCredits(Request $request, $id)
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($id);

        DB::beginTransaction();
        try {
            $clientCredit = $client->getOrCreateCredits();

            if ($clientCredit->available_credits < $validated['credits']) {
                return back()->with('error', "Cannot deduct {$validated['credits']} credits — client only has {$clientCredit->available_credits} available.");
            }

            $balanceBefore = $clientCredit->available_credits;
            $clientCredit->deductCredits($validated['credits']);

            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'adjustment',
                'status' => 'completed',
                'credits' => -$validated['credits'],
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'amount_paid' => 0,
                'payment_method' => 'manual',
                'description' => "Admin credit deduction: {$validated['reason']}",
                'processed_by' => auth()->id(),
                'metadata' => ['admin_note' => $validated['reason'], 'adjustment_type' => 'deduct'],
            ]);

            DB::commit();

            return back()->with('success', "{$validated['credits']} credits deducted from {$client->name} successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to deduct credits: ' . $e->getMessage());
        }
    }

    public function verifyEmail($id)
    {
        $client = Client::findOrFail($id);

        if ($client->email_verified_at) {
            return back()->with('error', 'This client\'s email is already verified.');
        }

        $client->email_verified_at = now();
        $client->email_verification_code = null;
        $client->email_verification_expires_at = null;
        $client->save();

        $bonusMessage = '';
        if (!$client->signup_bonus_credited) {
            try {
                $credits = $client->getOrCreateCredits();
                $credits->addCredits(2000);
                $client->signup_bonus_credited = true;
                $client->save();
                $bonusMessage = ' 2,000 signup bonus credits added.';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Admin manual verify — bonus credit failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
                $bonusMessage = ' (Bonus credit failed.)';
            }
        }

        return back()->with('success', "{$client->name}'s email has been manually verified.{$bonusMessage}");
    }

    public function markRemitted(Request $request, $id)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        DB::transaction(function () use ($request, $id) {
            $orders = Order::whereIn('id', $request->order_ids)
                ->where('client_id', $id)
                ->whereNull('remitted_at')
                ->get();

            foreach ($orders as $order) {
                $order->update(['remitted_at' => now()]);
                $order->deductStock();
            }
        });

        return back()->with('success', 'Orders marked as remitted successfully.');
    }

    public function updateStocks(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'stocks' => 'nullable|array',
            'stocks.*.id' => 'nullable|integer',
            'stocks.*.product_name' => 'nullable|string|max:255',
            'stocks.*.quantity' => 'nullable|integer|min:0',
        ]);

        $items = collect($validated['stocks'] ?? [])
            ->filter(fn ($item) => filled($item['product_name'] ?? null))
            ->map(fn ($item) => [
                'id' => $item['id'] ?? null,
                'product_name' => trim($item['product_name']),
                'quantity' => (int) ($item['quantity'] ?? 0),
            ])
            ->values();

        DB::transaction(function () use ($client, $items) {
            $keptIds = $items->pluck('id')->filter()->all();
            $client->stocks()->whereNotIn('id', $keptIds)->delete();

            foreach ($items as $item) {
                $data = ['product_name' => $item['product_name'], 'quantity' => $item['quantity']];
                $stock = $item['id'] ? $client->stocks()->find($item['id']) : null;
                $stock ? $stock->update($data) : $client->stocks()->create($data);
            }
        });

        ActivityLog::log('client_stocks_updated', "Updated stock records for client: {$client->name}", $client, [
            'items' => $items->count(),
        ]);

        return back()->with('success', 'Stock records updated successfully.');
    }

    public function togglePodRemittance($id)
    {
        $client = Client::findOrFail($id);
        $client->pod_remittance_enabled = !$client->pod_remittance_enabled;
        $client->save();

        $state = $client->pod_remittance_enabled ? 'enabled' : 'disabled';
        ActivityLog::log('pod_remittance_' . $state, "POD remittance {$state} for client: {$client->name}", $client);

        return back()->with('success', "Daily remittance feature {$state} for {$client->name}.");
    }
}
