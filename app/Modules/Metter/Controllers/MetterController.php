<?php

namespace App\Modules\Metter\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metter\Models\MetterConfiguration;
use App\Modules\Metter\Models\MetterFeature;
use App\Modules\Admin\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetterController extends Controller
{
    public function showSettings()
    {
        // Get all configurations grouped by category
        $allConfigs = MetterConfiguration::where('is_active', true)->get();
        
        // Create a simple key-value array for easy access in the view
        $configs = [];
        foreach ($allConfigs as $config) {
            $configs[$config->key] = MetterConfiguration::castValue($config->value, $config->type);
        }
        
        return view('Metter::metter.settings', compact('configs'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            // Pricing
            'metter_base_rate' => 'required|numeric|min:0',
            'metter_per_km_rate' => 'required|numeric|min:0',
            'metter_minimum_charge' => 'required|numeric|min:0',
            'metter_maximum_charge' => 'required|numeric|min:0',
            'metter_free_weight_limit' => 'required|numeric|min:0',
            'metter_weight_surcharge' => 'required|numeric|min:0',
            'metter_express_multiplier' => 'required|numeric|min:0',
            'metter_fuel_rate' => 'required|numeric|min:0',
            // Delivery Time
            'metter_standard_delivery_time' => 'required|integer|min:1',
            'metter_express_delivery_time' => 'required|integer|min:1',
            'metter_same_day_cutoff' => 'required|string',
            'metter_max_delivery_distance' => 'required|numeric|min:1',
            'metter_operating_hours_start' => 'required|string',
            'metter_operating_hours_end' => 'required|string',
            'metter_weekend_delivery' => 'required|boolean',
            // Zone-Based Pricing
            'metter_mainland_rate' => 'required|numeric|min:0',
            'metter_island_rate' => 'required|numeric|min:0',
            'metter_inter_zone_surcharge' => 'required|numeric|min:0',
            // Calculation & General
            'metter_calculation_method' => 'required|string|in:distance,zone,hybrid',
            'metter_round_up_price' => 'required|boolean',
            'metter_include_vat' => 'required|boolean',
            'metter_vat_rate' => 'required|numeric|min:0',
            'metter_peak_hours_enabled' => 'required|boolean',
            'metter_peak_hours_multiplier' => 'required|numeric|min:1',
            // Features & API
            'metter_api_enabled' => 'required|boolean',
            'metter_tracking_enabled' => 'required|boolean',
            'metter_sms_notifications' => 'required|boolean',
            'metter_email_notifications' => 'required|boolean',
            // Service Area
            'metter_service_city' => 'required|string|max:255',
            'metter_service_state' => 'required|string|max:255',
        ]);

        // Update each configuration
        foreach ($validated as $key => $value) {
            $config = MetterConfiguration::where('key', $key)->first();
            
            if ($config) {
                $config->update(['value' => $value]);
            } else {
                // Create if doesn't exist
                $type = is_numeric($value) ? 'number' : 'string';
                $category = str_contains($key, 'delivery') || str_contains($key, 'distance') ? 'delivery' : 
                           (str_contains($key, 'service') ? 'service_area' : 'pricing');
                
                MetterConfiguration::create([
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'category' => $category,
                    'is_active' => true,
                ]);
            }
        }

        // Clear the cached configuration so changes take effect immediately
        Cache::forget('metter_configuration');

        ActivityLog::log('metter_settings_updated', 'Updated Metter calculator settings');

        return redirect()->route('metter.settings')->with('success', 'Metter settings updated successfully');
    }

    public function toggleFeature($id)
    {
        $feature = MetterFeature::findOrFail($id);
        $feature->is_enabled = !$feature->is_enabled;
        $feature->save();

        $status = $feature->is_enabled ? 'enabled' : 'disabled';
        ActivityLog::log('metter_feature_toggled', "Toggled Metter feature '{$feature->name}' to {$status}", $feature);

        return redirect()->back()->with('success', "Feature {$status} successfully");
    }

    public function index()
    {
        return view('Metter::index');
    }
}
