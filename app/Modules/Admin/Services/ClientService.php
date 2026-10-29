<?php

namespace App\Modules\Admin\Services;

use App\Models\ClientShare;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientSubscription;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\SubscriptionPlan;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClientService
{
    public const SIGNUP_BONUS = 2000;

    public const ZONES = [
        'Zone A' => 'Zone A — Mainland (Ikeja, Ogba, Agege, Ikotun, Alimosho…)',
        'Zone B' => 'Zone B — Mainland (Ketu, Gbagada, Maryland, Mushin, Mile 12…)',
        'Zone C' => 'Zone C — Mainland (Yaba, Surulere, Ebute Metta…)',
        'Zone D' => 'Zone D — Mainland (Festac, Amuwo Odofin, Mile 2, Ago Palace…)',
        'Zone E' => 'Zone E — Island (Ikoyi, Victoria Island, Lagos Island…)',
        'Zone F' => 'Zone F — Island (Lekki Phase 1 & 2, VGC, Chevron, Orchid…)',
        'Zone G' => 'Zone G — Island (Sangotedo, Awoyaya, Epe, Ibeju-Lekki…)',
        'Zone H' => 'Zone H — Interstate (Ojodu Berger, Warewa, Arepo, Magboro…)',
        'Zone I' => 'Zone I — Interstate (Ikorodu, Ota, Agbara, Ogijo…)',
        'Zone J' => 'Zone J — Mainland (Ojo, Badagry, Satellite Town, Iyana Iba…)',
        'Zone K' => 'Zone K — Island (Ajah, Alpha Beach, Badore, Abraham Adesanya…)',
        'Zone L' => 'Zone L — Mainland (Oshodi, Isolo, Okota, Apapa, Ajegunle…)',
    ];

    public function listQuery(Request $request): Builder
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->get('remittance') === '1') {
            $query->where('pod_remittance_enabled', true);
        }

        return $query;
    }

    public function rules(?Client $client = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client?->id)],
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
        ];
    }

    public function create(array $validated): Client
    {
        $client = Client::create($this->prepare($validated));

        ActivityLog::log('client_created', "Created client: {$client->name}", $client);

        return $client;
    }

    public function update(Client $client, array $validated): Client
    {
        $client->update($this->prepare($validated));

        ActivityLog::log('client_updated', "Updated client: {$client->name}", $client);

        return $client;
    }

    public function delete(Client $client): void
    {
        if ($client->orders()->exists()) {
            throw new DomainException('Cannot delete client with existing orders');
        }

        $name = $client->name;
        $client->delete();

        ActivityLog::log('client_deleted', "Deleted client: {$name}");
    }

    public function subscribe(Client $client, int $planId, string $startsAt, ?string $notes): ClientSubscription
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        return DB::transaction(function () use ($client, $plan, $startsAt, $notes) {
            $client->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $start = Carbon::parse($startsAt);
            $subscription = ClientSubscription::create([
                'client_id' => $client->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $start,
                'expires_at' => $plan->validity_days ? $start->copy()->addDays($plan->validity_days) : null,
                'credits_allocated' => $plan->credits,
                'credits_used' => 0,
                'amount_paid' => 0,
                'auto_renew' => false,
            ]);

            $this->recordCreditChange($client, $plan->credits, 'purchase', "Manual subscription: {$plan->name}" . ($notes ? " — {$notes}" : ''), [
                'admin_note' => $notes,
                'subscription_id' => $subscription->id,
            ], $plan->id);

            return $subscription;
        });
    }

    public function adjustCredits(Client $client, int $credits, string $reason, bool $add): void
    {
        DB::transaction(function () use ($client, $credits, $reason, $add) {
            if (!$add && $client->getOrCreateCredits()->available_credits < $credits) {
                throw new DomainException("Cannot deduct {$credits} credits — client only has {$client->getOrCreateCredits()->available_credits} available.");
            }

            $this->recordCreditChange(
                $client,
                $add ? $credits : -$credits,
                'adjustment',
                $add ? "Manual credit addition: {$reason}" : "Admin credit deduction: {$reason}",
                ['admin_note' => $reason, 'adjustment_type' => $add ? 'add' : 'deduct'],
            );
        });
    }

    public function verifyEmail(Client $client): bool
    {
        if ($client->email_verified_at) {
            throw new DomainException("This client's email is already verified.");
        }

        $client->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();

        return $this->creditSignupBonus($client);
    }

    public function creditSignupBonus(Client $client): bool
    {
        if ($client->signup_bonus_credited) {
            return false;
        }

        try {
            $client->getOrCreateCredits()->addCredits(self::SIGNUP_BONUS);
            $client->forceFill(['signup_bonus_credited' => true])->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Signup bonus credit failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function updateStocks(Client $client, array $stocks): int
    {
        $items = collect($stocks)
            ->filter(fn ($item) => filled($item['product_name'] ?? null))
            ->map(fn ($item) => [
                'id' => $item['id'] ?? null,
                'product_name' => trim($item['product_name']),
                'quantity' => (int) ($item['quantity'] ?? 0),
            ])
            ->values();

        DB::transaction(function () use ($client, $items) {
            $client->stocks()->whereNotIn('id', $items->pluck('id')->filter()->all())->delete();

            foreach ($items as $item) {
                $data = ['product_name' => $item['product_name'], 'quantity' => $item['quantity']];
                $stock = $item['id'] ? $client->stocks()->find($item['id']) : null;
                $stock ? $stock->update($data) : $client->stocks()->create($data);
            }
        });

        ActivityLog::log('client_stocks_updated', "Updated stock records for client: {$client->name}", $client, ['items' => $items->count()]);

        return $items->count();
    }

    public function togglePodRemittance(Client $client): bool
    {
        $client->forceFill(['pod_remittance_enabled' => !$client->pod_remittance_enabled])->save();

        $state = $client->pod_remittance_enabled ? 'enabled' : 'disabled';
        ActivityLog::log('pod_remittance_' . $state, "POD remittance {$state} for client: {$client->name}", $client);

        return $client->pod_remittance_enabled;
    }

    public function shareUrl(Client $client): ?string
    {
        $share = $client->clientShare()->where('is_active', true)->first();

        return $share ? config('app.frontend_url') . '/share/' . $share->token : null;
    }

    public function generateShareLink(Client $client): string
    {
        ClientShare::where('client_id', $client->id)->delete();

        $share = ClientShare::create([
            'client_id' => $client->id,
            'token' => ClientShare::generateToken(),
            'expires_at' => now()->addYears(10),
            'is_active' => true,
        ]);

        ActivityLog::log('client_share_created', "Enabled order tracking for client: {$client->name}", $share);

        return config('app.frontend_url') . '/share/' . $share->token;
    }

    public function disableShareLink(Client $client): void
    {
        ClientShare::where('client_id', $client->id)->update(['is_active' => false]);

        ActivityLog::log('client_share_disabled', "Disabled order tracking for client: {$client->name}");
    }

    public function setApiAccess(Client $client, string $action): ?string
    {
        $key = match ($action) {
            'generate', 'regenerate' => $client->generateApiKey(),
            'enable' => $client->enableApiAccess(),
            'disable' => tap(null, fn () => $client->disableApiAccess()),
        };

        $labels = [
            'generate' => ['api_key_generated', 'Generated API key'],
            'regenerate' => ['api_key_regenerated', 'Regenerated API key'],
            'enable' => ['api_access_enabled', 'Enabled API access'],
            'disable' => ['api_access_disabled', 'Disabled API access'],
        ];
        [$event, $label] = $labels[$action];
        ActivityLog::log($event, "{$label} for client: {$client->name}", $client);

        return $key;
    }

    private function recordCreditChange(Client $client, int $credits, string $type, string $description, array $metadata, ?int $planId = null): void
    {
        $credit = $client->getOrCreateCredits();
        $before = $credit->available_credits;

        $credits >= 0 ? $credit->addCredits($credits) : $credit->deductCredits(abs($credits));

        CreditTransaction::create([
            'client_id' => $client->id,
            'transaction_reference' => CreditTransaction::generateReference(),
            'type' => $type,
            'status' => 'completed',
            'credits' => $credits,
            'balance_before' => $before,
            'balance_after' => $credit->available_credits,
            'subscription_plan_id' => $planId,
            'amount_paid' => 0,
            'payment_method' => 'manual',
            'description' => $description,
            'processed_by' => Auth::id(),
            'metadata' => $metadata,
        ]);
    }

    private function prepare(array $validated): array
    {
        if (array_key_exists('zone_rates', $validated)) {
            $validated['zone_rates'] = collect($validated['zone_rates'] ?? [])
                ->filter(fn ($rate) => $rate !== null && $rate !== '')
                ->map(fn ($rate) => (float) $rate)
                ->all();
        }

        return $validated;
    }
}
