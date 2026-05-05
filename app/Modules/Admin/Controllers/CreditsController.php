<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use App\Modules\Admin\Models\ClientCredit;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\CreditSetting;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CreditsController extends Controller
{
    /**
     * Display credits dashboard
     */
    public function index()
    {
        $stats = [
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::active()->count(),
            'total_packages' => CreditPackage::count(),
            'active_packages' => CreditPackage::active()->count(),
            'total_credits_sold' => CreditTransaction::where('type', 'purchase')->sum('credits'),
            'total_credits_used' => CreditTransaction::where('type', 'usage')->sum('credits'),
            'total_revenue' => CreditTransaction::where('type', 'purchase')->sum('amount_paid'),
            'clients_with_credits' => ClientCredit::where('available_credits', '>', 0)->count(),
        ];

        $recentTransactions = CreditTransaction::with(['client', 'subscriptionPlan', 'creditPackage'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $lowCreditClients = ClientCredit::with('client')
            ->where('available_credits', '>', 0)
            ->where('available_credits', '<=', CreditSetting::get('low_credit_threshold', 10))
            ->orderBy('available_credits', 'asc')
            ->limit(10)
            ->get();

        return view('Admin::credits.index', compact('stats', 'recentTransactions', 'lowCreditClients'));
    }

    /**
     * Display subscription plans
     */
    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->orderBy('price')->get();
        return view('Admin::credits.plans.index', compact('plans'));
    }

    /**
     * Show create plan form
     */
    public function createPlan()
    {
        return view('Admin::credits.plans.create');
    }

    /**
     * Store new subscription plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,quarterly,yearly,one-time',
            'validity_days' => 'nullable|integer|min:1',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Decode features JSON string to array
        if (isset($validated['features'])) {
            $validated['features'] = json_decode($validated['features'], true);
        }
        
        SubscriptionPlan::create($validated);

        return redirect()->route('admin.credits.plans')->with('success', 'Subscription plan created successfully');
    }

    /**
     * Show edit plan form
     */
    public function editPlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        return view('Admin::credits.plans.edit', compact('plan'));
    }

    /**
     * Update subscription plan
     */
    public function updatePlan(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,quarterly,yearly,one-time',
            'validity_days' => 'nullable|integer|min:1',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Decode features JSON string to array
        if (isset($validated['features'])) {
            $validated['features'] = json_decode($validated['features'], true);
        }
        
        $plan->update($validated);

        return redirect()->route('admin.credits.plans')->with('success', 'Subscription plan updated successfully');
    }

    /**
     * Delete subscription plan
     */
    public function deletePlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();

        return back()->with('success', 'Subscription plan deleted successfully');
    }

    /**
     * Display credit packages
     */
    public function packages()
    {
        $packages = CreditPackage::orderBy('sort_order')->orderBy('credits')->get();
        return view('Admin::credits.packages.index', compact('packages'));
    }

    /**
     * Show create package form
     */
    public function createPackage()
    {
        return view('Admin::credits.packages.create');
    }

    /**
     * Store new credit package
     */
    public function storePackage(Request $request)
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
        
        CreditPackage::create($validated);

        return redirect()->route('admin.credits.packages')->with('success', 'Credit package created successfully');
    }

    /**
     * Show edit package form
     */
    public function editPackage($id)
    {
        $package = CreditPackage::findOrFail($id);
        return view('Admin::credits.packages.edit', compact('package'));
    }

    /**
     * Update credit package
     */
    public function updatePackage(Request $request, $id)
    {
        $package = CreditPackage::findOrFail($id);

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
        
        $package->update($validated);

        return redirect()->route('admin.credits.packages')->with('success', 'Credit package updated successfully');
    }

    /**
     * Delete credit package
     */
    public function deletePackage($id)
    {
        $package = CreditPackage::findOrFail($id);
        $package->delete();

        return back()->with('success', 'Credit package deleted successfully');
    }

    /**
     * Display credit settings
     */
    public function settings()
    {
        $settings = CreditSetting::all()->groupBy('group');
        return view('Admin::credits.settings', compact('settings'));
    }

    /**
     * Update credit settings
     */
    public function updateSettings(Request $request)
    {
        foreach ($request->except('_token', '_method') as $key => $value) {
            $setting = CreditSetting::where('key', $key)->first();
            if ($setting) {
                CreditSetting::set($key, $value, $setting->type);
            }
        }

        return back()->with('success', 'Settings updated successfully');
    }

    /**
     * Manually adjust client credits
     */
    public function adjustCredits(Request $request, $clientId)
    {
        $validated = $request->validate([
            'credits' => 'required|integer',
            'type' => 'required|in:add,deduct',
            'reason' => 'required|string|max:500',
        ]);

        $client = Client::findOrFail($clientId);
        $clientCredit = $client->getOrCreateCredits();

        DB::beginTransaction();
        try {
            $credits = $validated['type'] === 'add' ? abs($validated['credits']) : -abs($validated['credits']);
            
            if ($validated['type'] === 'deduct' && $clientCredit->available_credits < abs($validated['credits'])) {
                return back()->with('error', 'Insufficient credits to deduct');
            }

            $balanceBefore = $clientCredit->available_credits;

            if ($validated['type'] === 'add') {
                $clientCredit->addCredits(abs($validated['credits']));
            } else {
                $clientCredit->deductCredits(abs($validated['credits']));
            }

            // Create transaction record
            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'adjustment',
                'credits' => $credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $clientCredit->available_credits,
                'description' => $validated['reason'],
                'processed_by' => auth()->id(),
                'metadata' => [
                    'adjustment_type' => $validated['type'],
                    'admin_note' => $validated['reason'],
                ],
            ]);

            DB::commit();

            return back()->with('success', 'Credits adjusted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to adjust credits: ' . $e->getMessage());
        }
    }

    /**
     * View client credit history
     */
    public function clientHistory($clientId)
    {
        $client = Client::with('credits')->findOrFail($clientId);
        $transactions = CreditTransaction::where('client_id', $clientId)
            ->with(['subscriptionPlan', 'creditPackage', 'order', 'processedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('Admin::credits.client-history', compact('client', 'transactions'));
    }
}
