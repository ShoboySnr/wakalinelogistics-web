<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Setting;
use App\Modules\Admin\Models\ActivityLog;
use App\Services\ZoneBatchDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('Admin::settings', compact('settings'));
    }

    public function update(Request $request)
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

    public function batchDiscount(ZoneBatchDiscountService $service)
    {
        return view('Admin::settings.batch-discount', [
            'enabled' => $service->isEnabled(),
            'tiers' => $service->tiers(),
        ]);
    }

    public function updateBatchDiscount(Request $request, ZoneBatchDiscountService $service)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'tiers' => 'nullable|array',
            'tiers.*.min' => 'nullable|integer|min:2',
            'tiers.*.max' => 'nullable|integer|min:2',
            'tiers.*.percent' => 'nullable|numeric|min:0|max:100',
        ], [
            'tiers.*.min.min' => 'The smallest band must start at 2 — a single order has nothing to pool with.',
        ]);

        $tiers = $service->normaliseTiers($validated['tiers'] ?? []);

        foreach ($tiers as $i => $tier) {
            $next = $tiers[$i + 1] ?? null;
            if ($next === null) {
                continue;
            }
            if ($tier['max'] === null || $next['min'] <= $tier['max']) {
                return back()
                    ->withInput()
                    ->with('error', 'Discount bands overlap or an open-ended band is not last. Each band must start above the previous band\'s upper limit.');
            }
        }

        Setting::set(
            ZoneBatchDiscountService::SETTING_ENABLED,
            $request->boolean('enabled') ? '1' : '0',
            'boolean',
            ZoneBatchDiscountService::SETTING_GROUP,
            'Discount repeat deliveries into a zone a rider is already visiting',
        );

        Setting::set(
            ZoneBatchDiscountService::SETTING_TIERS,
            $tiers,
            'json',
            ZoneBatchDiscountService::SETTING_GROUP,
            'Discount bands keyed on how many open orders are heading to the same zone',
        );

        ActivityLog::log('settings_updated', 'Updated same-zone batch discount settings');

        return redirect()->route('admin.settings.batch-discount')
            ->with('success', 'Batch discount settings saved.');
    }

    public function clearCache(Request $request)
    {
        Artisan::call('optimize:clear');
        ActivityLog::log('cache_cleared', 'Admin cleared all application caches');
        return redirect()->route('admin.settings')->with('cache_cleared', 'All caches cleared successfully!');
    }
}
