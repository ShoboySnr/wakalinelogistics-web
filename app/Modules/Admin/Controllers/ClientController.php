<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use App\Modules\Admin\Services\ClientService;
use App\Modules\Admin\Services\OrderService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct(private ClientService $clients)
    {
    }

    public function index(Request $request)
    {
        $clients = $this->clients->listQuery($request)->orderBy('name', 'asc')->paginate(20);

        return view('Admin::clients.index', compact('clients'));
    }

    public function create()
    {
        return view('Admin::clients.create');
    }

    public function store(Request $request)
    {
        $this->clients->create($request->validate($this->clients->rules()));

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
        $this->clients->update($client, $request->validate($this->clients->rules($client)));

        return redirect()->route('admin.clients.show', $id)->with('success', 'Client updated successfully');
    }

    public function destroy($id)
    {
        try {
            $this->clients->delete(Client::findOrFail($id));
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

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
        return response()->json([
            'success' => true,
            'url' => $this->clients->generateShareLink(Client::findOrFail($id)),
        ]);
    }

    public function disableShareLink($id)
    {
        $this->clients->disableShareLink(Client::findOrFail($id));

        return response()->json([
            'success' => true,
            'message' => 'Order tracking link disabled successfully',
        ]);
    }

    public function generateApiKey($id)
    {
        return $this->apiAccessResponse($id, 'generate', 'API key generated successfully');
    }

    public function regenerateApiKey($id)
    {
        return $this->apiAccessResponse($id, 'regenerate', 'API key regenerated successfully');
    }

    public function enableApiAccess($id)
    {
        return $this->apiAccessResponse($id, 'enable', 'API access enabled successfully');
    }

    public function disableApiAccess($id)
    {
        $this->clients->setApiAccess(Client::findOrFail($id), 'disable');

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

        try {
            $subscription = $this->clients->subscribe($client, (int) $validated['subscription_plan_id'], $validated['starts_at'], $validated['notes'] ?? null);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to subscribe client: ' . $e->getMessage());
        }

        $plan = $subscription->plan;

        return back()->with('success', "Client subscribed to {$plan->name} successfully. {$plan->credits} credits added.");
    }

    public function addCredits(Request $request, $id)
    {
        return $this->adjustCredits($request, $id, true);
    }

    public function deductCredits(Request $request, $id)
    {
        return $this->adjustCredits($request, $id, false);
    }

    public function verifyEmail($id)
    {
        $client = Client::findOrFail($id);

        try {
            $bonus = $this->clients->verifyEmail($client);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $bonusMessage = $bonus ? ' 2,000 signup bonus credits added.' : ($client->signup_bonus_credited ? '' : ' (Bonus credit failed.)');

        return back()->with('success', "{$client->name}'s email has been manually verified.{$bonusMessage}");
    }

    public function markRemitted(Request $request, $id)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        app(OrderService::class)->markRemitted((int) $id, $request->order_ids);

        return back()->with('success', 'Orders marked as remitted successfully.');
    }

    public function updateStocks(Request $request, $id)
    {
        $validated = $request->validate($this->stockRules());

        $this->clients->updateStocks(Client::findOrFail($id), $validated['stocks'] ?? []);

        return back()->with('success', 'Stock records updated successfully.');
    }

    public function togglePodRemittance($id)
    {
        $client = Client::findOrFail($id);
        $state = $this->clients->togglePodRemittance($client) ? 'enabled' : 'disabled';

        return back()->with('success', "Daily remittance feature {$state} for {$client->name}.");
    }

    public function stockRules(): array
    {
        return [
            'stocks' => 'nullable|array',
            'stocks.*.id' => 'nullable|integer',
            'stocks.*.product_name' => 'nullable|string|max:255',
            'stocks.*.quantity' => 'nullable|integer|min:0',
        ];
    }

    private function adjustCredits(Request $request, $id, bool $add)
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($id);

        try {
            $this->clients->adjustCredits($client, (int) $validated['credits'], $validated['reason'], $add);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to ' . ($add ? 'add' : 'deduct') . ' credits: ' . $e->getMessage());
        }

        return back()->with('success', $add
            ? "{$validated['credits']} credits added to {$client->name} successfully."
            : "{$validated['credits']} credits deducted from {$client->name} successfully.");
    }

    private function apiAccessResponse($id, string $action, string $message)
    {
        $client = Client::findOrFail($id);
        $apiKey = $this->clients->setApiAccess($client, $action);

        return response()->json([
            'success' => true,
            'message' => $message,
            'api_key' => $apiKey,
            'generated_at' => $client->api_key_generated_at->format('M d, Y h:i A'),
        ]);
    }
}
