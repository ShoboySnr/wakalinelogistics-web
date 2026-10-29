<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientCredit;
use App\Modules\Admin\Models\CreditPackage;
use App\Modules\Admin\Models\CreditSetting;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_plans' => SubscriptionPlan::count(),
                    'active_plans' => SubscriptionPlan::active()->count(),
                    'total_packages' => CreditPackage::count(),
                    'active_packages' => CreditPackage::active()->count(),
                    'total_credits_sold' => (int) CreditTransaction::where('type', 'purchase')->sum('credits'),
                    'total_credits_used' => (int) CreditTransaction::where('type', 'usage')->sum('credits'),
                    'total_revenue' => (float) CreditTransaction::where('type', 'purchase')->sum('amount_paid'),
                    'clients_with_credits' => ClientCredit::where('available_credits', '>', 0)->count(),
                ],
                'recent_transactions' => CreditTransaction::with(['client', 'subscriptionPlan', 'creditPackage'])
                    ->latest()->limit(10)->get()
                    ->map(fn (CreditTransaction $t) => [
                        'id' => $t->id,
                        'reference' => $t->transaction_reference,
                        'type' => $t->type,
                        'credits' => $t->credits,
                        'amount_paid' => $t->amount_paid !== null ? (float) $t->amount_paid : null,
                        'client' => $t->client?->name,
                        'created_at' => $t->created_at,
                    ]),
                'low_credit_clients' => ClientCredit::with('client')
                    ->where('available_credits', '>', 0)
                    ->where('available_credits', '<=', CreditSetting::get('low_credit_threshold', 10))
                    ->orderBy('available_credits')->limit(10)->get()
                    ->map(fn (ClientCredit $c) => [
                        'client_id' => $c->client_id,
                        'client' => $c->client?->name,
                        'available_credits' => $c->available_credits,
                    ]),
            ],
        ]);
    }

    // ─── Plans ───

    public function plans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SubscriptionPlan::orderBy('sort_order')->orderBy('price')->get()->map(fn ($p) => $this->planData($p)),
        ]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($this->planAttributes($request));

        return response()->json(['success' => true, 'message' => 'Subscription plan created successfully', 'data' => $this->planData($plan)], 201);
    }

    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($this->planAttributes($request));

        return response()->json(['success' => true, 'message' => 'Subscription plan updated successfully', 'data' => $this->planData($plan)]);
    }

    public function deletePlan(int $id): JsonResponse
    {
        SubscriptionPlan::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Subscription plan deleted successfully']);
    }

    // ─── Packages ───

    public function packages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CreditPackage::orderBy('sort_order')->orderBy('credits')->get()->map(fn ($p) => $this->packageData($p)),
        ]);
    }

    public function storePackage(Request $request): JsonResponse
    {
        $package = CreditPackage::create($this->packageAttributes($request));

        return response()->json(['success' => true, 'message' => 'Credit package created successfully', 'data' => $this->packageData($package)], 201);
    }

    public function updatePackage(Request $request, int $id): JsonResponse
    {
        $package = CreditPackage::findOrFail($id);
        $package->update($this->packageAttributes($request));

        return response()->json(['success' => true, 'message' => 'Credit package updated successfully', 'data' => $this->packageData($package)]);
    }

    public function deletePackage(int $id): JsonResponse
    {
        CreditPackage::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Credit package deleted successfully']);
    }

    // ─── Settings ───

    public function settings(): JsonResponse
    {
        $groups = CreditSetting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn ($settings, $group) => [
                'group' => $group,
                'settings' => $settings->map(fn (CreditSetting $s) => [
                    'key' => $s->key,
                    'value' => CreditSetting::get($s->key),
                    'type' => $s->type,
                    'description' => $s->description ?? null,
                ])->values(),
            ])->values();

        return response()->json(['success' => true, 'data' => ['groups' => $groups]]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $settings = $request->validate(['settings' => 'required|array'])['settings'];

        foreach ($settings as $key => $value) {
            $setting = CreditSetting::where('key', $key)->first();
            if ($setting) {
                CreditSetting::set($key, $value, $setting->type);
            }
        }

        return response()->json(['success' => true, 'message' => 'Credit settings updated successfully']);
    }

    // ─── Client credits ───

    public function adjustCredits(Request $request, int $clientId): JsonResponse
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1',
            'type' => 'required|in:add,deduct',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($clientId);
        $clientCredit = $client->getOrCreateCredits();

        if ($validated['type'] === 'deduct' && $clientCredit->available_credits < $validated['credits']) {
            return response()->json(['success' => false, 'message' => 'Insufficient credits to deduct'], 422);
        }

        DB::transaction(function () use ($client, $clientCredit, $validated) {
            $balanceBefore = $clientCredit->available_credits;
            $signed = $validated['type'] === 'add' ? $validated['credits'] : -$validated['credits'];

            if ($validated['type'] === 'add') {
                $clientCredit->addCredits($validated['credits']);
            } else {
                $clientCredit->deductCredits($validated['credits']);
            }

            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'adjustment',
                'credits' => $signed,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'description' => $validated['reason'],
                'processed_by' => auth()->id(),
                'metadata' => ['adjustment_type' => $validated['type'], 'admin_note' => $validated['reason']],
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Credits adjusted successfully', 'data' => [
            'available_credits' => $clientCredit->fresh()->available_credits,
        ]]);
    }

    public function clientHistory(Request $request, int $clientId): JsonResponse
    {
        $client = Client::with('credits')->findOrFail($clientId);
        $transactions = CreditTransaction::where('client_id', $clientId)
            ->with(['subscriptionPlan', 'creditPackage', 'order', 'processedBy'])
            ->latest()
            ->paginate(min(100, max(5, (int) $request->get('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($transactions->items())->map(fn (CreditTransaction $t) => [
                'id' => $t->id,
                'reference' => $t->transaction_reference,
                'type' => $t->type,
                'credits' => $t->credits,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'processed_by' => $t->processedBy?->name,
                'created_at' => $t->created_at,
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'available_credits' => $client->credits?->available_credits ?? 0,
            ],
        ]);
    }

    // ─── Helpers ───

    private function planAttributes(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,quarterly,yearly,one-time',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['features'] = array_values(array_filter(array_map('trim', $validated['features'] ?? [])));

        return $validated;
    }

    private function packageAttributes(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'nullable|integer|min:1',
            'bonus_credits' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        return $validated;
    }

    private function planData(SubscriptionPlan $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'credits' => $p->credits,
            'price' => (float) $p->price,
            'billing_cycle' => $p->billing_cycle,
            'validity_days' => $p->validity_days,
            'features' => $p->features ?? [],
            'is_active' => (bool) $p->is_active,
            'is_featured' => (bool) $p->is_featured,
            'sort_order' => $p->sort_order,
        ];
    }

    private function packageData(CreditPackage $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'credits' => $p->credits,
            'bonus_credits' => $p->bonus_credits,
            'price' => (float) $p->price,
            'validity_days' => $p->validity_days,
            'is_active' => (bool) $p->is_active,
            'is_popular' => (bool) $p->is_popular,
            'sort_order' => $p->sort_order,
        ];
    }
}
