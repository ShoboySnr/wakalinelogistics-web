<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Setting;
use App\Services\ZoneBatchDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index(ZoneBatchDiscountService $service): JsonResponse
    {
        $groups = Setting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn ($settings, $group) => [
                'group' => $group,
                'settings' => $settings->map(fn (Setting $s) => [
                    'key' => $s->key,
                    'value' => Setting::get($s->key),
                    'type' => $s->type,
                    'description' => $s->description,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
                'batch_discount' => [
                    'enabled' => $service->isEnabled(),
                    'tiers' => $service->tiers(),
                ],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
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

        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }

    public function updateBatchDiscount(Request $request, ZoneBatchDiscountService $service): JsonResponse
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
                return response()->json([
                    'success' => false,
                    'message' => 'Discount bands overlap or an open-ended band is not last. Each band must start above the previous band\'s upper limit.',
                ], 422);
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

        return response()->json([
            'success' => true,
            'message' => 'Batch discount settings saved.',
            'data' => ['enabled' => $service->isEnabled(), 'tiers' => $service->tiers()],
        ]);
    }

    public function clearCache(): JsonResponse
    {
        Artisan::call('optimize:clear');
        ActivityLog::log('cache_cleared', 'Admin cleared all application caches');

        return response()->json(['success' => true, 'message' => 'All caches cleared successfully']);
    }
}
