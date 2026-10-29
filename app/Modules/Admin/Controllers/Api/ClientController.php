<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientIntegration;
use App\Modules\Admin\Models\IntegrationLog;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Resources\OrderResource;
use App\Modules\Admin\Services\ClientService;
use App\Modules\Admin\Services\InvoiceService;
use App\Modules\Admin\Services\OrderService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(private ClientService $clients)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->get('per_page', 20)));
        $clients = $this->clients->listQuery($request)
            ->withCount('orders')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($clients->items())->map(fn (Client $client) => $this->summary($client) + [
                'orders_count' => $client->orders_count,
            ]),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
            'stats' => [
                'total' => Client::count(),
                'active' => Client::where('is_active', true)->count(),
                'inactive' => Client::where('is_active', false)->count(),
                'remittance' => Client::where('pod_remittance_enabled', true)->count(),
            ],
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'zones' => collect(ClientService::ZONES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'payment_terms' => [
                    ['value' => 'prepaid', 'label' => 'Prepaid'],
                    ['value' => 'postpaid', 'label' => 'Postpaid'],
                    ['value' => 'credit_30', 'label' => 'Net 30 Days'],
                    ['value' => 'credit_60', 'label' => 'Net 60 Days'],
                ],
                'subscription_plans' => SubscriptionPlan::active()->orderBy('sort_order')->orderBy('price')->get(['id', 'name', 'credits', 'price', 'validity_days']),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $this->clients->create($request->validate($this->clients->rules()));

        return response()->json(['success' => true, 'message' => 'Client created successfully', 'data' => $this->detail($client)], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->detail(Client::findOrFail($id))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $this->clients->update($client, $request->validate($this->clients->rules($client)));

        return response()->json(['success' => true, 'message' => 'Client updated successfully', 'data' => $this->detail($client)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->attempt(fn () => $this->clients->delete(Client::findOrFail($id)), 'Client deleted successfully', false);
    }

    public function orders(Request $request, int $id): JsonResponse
    {
        $orders = Client::findOrFail($id)->orders()
            ->with('rider:id,name,phone')
            ->latest()
            ->paginate(min(100, max(5, (int) $request->get('per_page', 10))));

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function subscribe(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $client = Client::findOrFail($id);
        $subscription = $this->clients->subscribe($client, (int) $validated['subscription_plan_id'], $validated['starts_at'], $validated['notes'] ?? null);
        $plan = $subscription->plan;

        return response()->json([
            'success' => true,
            'message' => "Client subscribed to {$plan->name} successfully. {$plan->credits} credits added.",
            'data' => $this->detail($client),
        ]);
    }

    public function adjustCredits(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:add,deduct',
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($id);
        $add = $validated['type'] === 'add';

        return $this->attempt(
            fn () => $this->clients->adjustCredits($client, (int) $validated['credits'], $validated['reason'], $add),
            $add ? "{$validated['credits']} credits added to {$client->name}." : "{$validated['credits']} credits deducted from {$client->name}.",
            $client,
        );
    }

    public function verifyEmail(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        try {
            $bonus = $this->clients->verifyEmail($client);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "{$client->name}'s email has been verified." . ($bonus ? ' 2,000 signup bonus credits added.' : ''),
            'data' => $this->detail($client),
        ]);
    }

    public function updateStocks(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stocks' => 'nullable|array',
            'stocks.*.id' => 'nullable|integer',
            'stocks.*.product_name' => 'nullable|string|max:255',
            'stocks.*.quantity' => 'nullable|integer|min:0',
        ]);

        $client = Client::findOrFail($id);
        $this->clients->updateStocks($client, $validated['stocks'] ?? []);

        return response()->json(['success' => true, 'message' => 'Stock records updated successfully.', 'data' => $this->detail($client)]);
    }

    public function togglePodRemittance(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $state = $this->clients->togglePodRemittance($client) ? 'enabled' : 'disabled';

        return response()->json(['success' => true, 'message' => "Daily remittance {$state} for {$client->name}.", 'data' => $this->detail($client)]);
    }

    public function shareLink(Request $request, int $id): JsonResponse
    {
        $request->validate(['action' => 'required|in:generate,disable']);
        $client = Client::findOrFail($id);

        if ($request->action === 'generate') {
            $this->clients->generateShareLink($client);
        } else {
            $this->clients->disableShareLink($client);
        }

        return response()->json([
            'success' => true,
            'message' => $request->action === 'generate' ? 'Order tracking link generated.' : 'Order tracking link disabled.',
            'data' => $this->detail($client),
        ]);
    }

    public function apiAccess(Request $request, int $id): JsonResponse
    {
        $request->validate(['action' => 'required|in:generate,regenerate,enable,disable']);
        $client = Client::findOrFail($id);
        $this->clients->setApiAccess($client, $request->action);

        return response()->json([
            'success' => true,
            'message' => [
                'generate' => 'API key generated successfully',
                'regenerate' => 'API key regenerated successfully',
                'enable' => 'API access enabled successfully',
                'disable' => 'API access disabled successfully',
            ][$request->action],
            'data' => $this->detail($client->refresh()),
        ]);
    }

    public function storeInvoice(Request $request, InvoiceService $invoices, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $validated = $request->validate($invoices->rules(), $invoices->messages());

        try {
            $invoice = $invoices->create($client, $validated);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Invoice {$invoice->invoice_number} generated.",
            'data' => ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number],
        ], 201);
    }

    private function attempt(callable $action, string $message, Client|false $client): JsonResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $client ? $this->detail($client) : null,
        ], fn ($value) => $value !== null));
    }

    private function summary(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'company_name' => $client->company_name,
            'phone' => $client->phone,
            'email' => $client->email,
            'pickup_address' => $client->pickup_address,
            'is_active' => (bool) $client->is_active,
            'dashboard_enabled' => (bool) $client->dashboard_enabled,
            'api_enabled' => (bool) $client->api_enabled,
            'pod_remittance_enabled' => (bool) $client->pod_remittance_enabled,
            'email_verified_at' => $client->email_verified_at,
            'created_at' => $client->created_at,
        ];
    }

    private function detail(Client $client): array
    {
        $client->refresh();
        $orders = $client->orders();
        $credit = $client->getOrCreateCredits();
        $subscription = $client->activeSubscription()->with('plan')->first();
        $webhook = ClientIntegration::where('client_id', $client->id)->where('integration_type', 'webhook')->first();

        return $this->summary($client) + [
            'contact_person' => $client->contact_person,
            'alternate_phone' => $client->alternate_phone,
            'alternate_email' => $client->alternate_email,
            'business_address' => $client->business_address,
            'city' => $client->city,
            'state' => $client->state,
            'business_type' => $client->business_type,
            'tax_id' => $client->tax_id,
            'website' => $client->website,
            'payment_terms' => $client->payment_terms,
            'credit_limit' => $client->credit_limit !== null ? (float) $client->credit_limit : null,
            'notes' => $client->notes,
            'special_instructions' => $client->special_instructions,
            'onboarded_date' => $client->onboarded_date?->toDateString(),
            'bank_name' => $client->bank_name,
            'bank_account_name' => $client->bank_account_name,
            'bank_account_number' => $client->bank_account_number,
            'zone_rates' => is_array($client->zone_rates) ? $client->zone_rates : [],
            'last_login_at' => $client->last_login_at,
            'api_key' => $client->api_key,
            'api_key_generated_at' => $client->api_key_generated_at,
            'api_last_used_at' => $client->api_last_used_at,
            'share_url' => $this->clients->shareUrl($client),
            'stats' => [
                'total_orders' => (clone $orders)->count(),
                'completed_orders' => (clone $orders)->where('status', 'delivered')->count(),
                'pending_orders' => (clone $orders)->whereIn('status', ['pending', 'confirmed', 'in_transit'])->count(),
                'total_revenue' => (float) (clone $orders)->where('status', 'delivered')->sum('price'),
            ],
            'credits' => [
                'available' => (int) $credit->available_credits,
                'total' => (int) $credit->total_credits,
                'used' => (int) $credit->used_credits,
            ],
            'active_subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan?->name,
                'credits_allocated' => $subscription->credits_allocated,
                'credits_used' => $subscription->credits_used,
                'starts_at' => $subscription->starts_at,
                'expires_at' => $subscription->expires_at,
            ] : null,
            'stocks' => $client->stocks()->get(['id', 'product_name', 'quantity']),
            'remittance' => $client->pod_remittance_enabled ? app(OrderService::class)->podRemittanceData($client) : null,
            'invoices' => Invoice::where('client_id', $client->id)->withCount('orders')->latest()->get()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'orders_count' => $invoice->orders_count,
                'total' => (float) $invoice->total,
            ]),
            'invoiceable_orders' => app(InvoiceService::class)->invoiceableOrders($client),
            'webhook' => $webhook ? [
                'id' => $webhook->id,
                'is_active' => (bool) $webhook->is_active,
                'url' => $webhook->credentials['webhook_url'] ?? null,
                'last_synced_at' => $webhook->last_synced_at,
                'last_error' => $webhook->last_error,
                'sync_count' => $webhook->sync_count,
                'logs' => IntegrationLog::where('client_integration_id', $webhook->id)->latest()->limit(10)->get(['id', 'action', 'status', 'error_message', 'created_at']),
            ] : null,
        ];
    }
}
