@extends('Admin::layout')

@section('title', 'Metter Settings')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Metter Calculator Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure pricing and delivery settings for the Metter delivery calculator</p>
    </div>

    @if($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
        <ul class="list-disc list-inside text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('metter.settings.update') }}">
        @csrf
        @method('PUT')

        <!-- Pricing Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Pricing Configuration</h2>
                <p class="text-sm text-gray-500 mt-1">Set base rates and pricing rules for delivery calculations</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="metter_base_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Base Delivery Rate (₦)
                        </label>
                        <input type="number" name="metter_base_rate" id="metter_base_rate" 
                               value="{{ old('metter_base_rate', $configs['metter_base_rate'] ?? 3500) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Starting price for any delivery</p>
                    </div>

                    <div>
                        <label for="metter_per_km_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Per Kilometer Rate (₦)
                        </label>
                        <input type="number" name="metter_per_km_rate" id="metter_per_km_rate" 
                               value="{{ old('metter_per_km_rate', $configs['metter_per_km_rate'] ?? 100) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Additional cost per kilometer</p>
                    </div>

                    <div>
                        <label for="metter_minimum_charge" class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Charge (₦)
                        </label>
                        <input type="number" name="metter_minimum_charge" id="metter_minimum_charge" 
                               value="{{ old('metter_minimum_charge', $configs['metter_minimum_charge'] ?? 2000) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Minimum delivery charge</p>
                    </div>

                    <div>
                        <label for="metter_maximum_charge" class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Charge (₦)
                        </label>
                        <input type="number" name="metter_maximum_charge" id="metter_maximum_charge" 
                               value="{{ old('metter_maximum_charge', $configs['metter_maximum_charge'] ?? 50000) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Maximum delivery charge cap</p>
                    </div>

                    <div>
                        <label for="metter_free_weight_limit" class="block text-sm font-medium text-gray-700 mb-2">
                            Free Weight Limit (kg)
                        </label>
                        <input type="number" name="metter_free_weight_limit" id="metter_free_weight_limit" 
                               value="{{ old('metter_free_weight_limit', $configs['metter_free_weight_limit'] ?? 5) }}" 
                               step="0.1" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Weight included in base price</p>
                    </div>

                    <div>
                        <label for="metter_weight_surcharge" class="block text-sm font-medium text-gray-700 mb-2">
                            Weight Surcharge per kg (₦)
                        </label>
                        <input type="number" name="metter_weight_surcharge" id="metter_weight_surcharge" 
                               value="{{ old('metter_weight_surcharge', $configs['metter_weight_surcharge'] ?? 500) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Extra charge per kg above free limit</p>
                    </div>

                    <div>
                        <label for="metter_express_multiplier" class="block text-sm font-medium text-gray-700 mb-2">
                            Express Delivery Multiplier
                        </label>
                        <input type="number" name="metter_express_multiplier" id="metter_express_multiplier" 
                               value="{{ old('metter_express_multiplier', $configs['metter_express_multiplier'] ?? 1.5) }}" 
                               step="0.1" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Multiply total price by this for express (e.g., 1.5 = 50% more)</p>
                    </div>

                    <div>
                        <label for="metter_fuel_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Fuel Rate (₦)
                        </label>
                        <input type="number" name="metter_fuel_rate" id="metter_fuel_rate" 
                               value="{{ old('metter_fuel_rate', $configs['metter_fuel_rate'] ?? 200) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Fuel surcharge per delivery</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Time Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Delivery Time Configuration</h2>
                <p class="text-sm text-gray-500 mt-1">Set delivery time estimates and cutoff times</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="metter_standard_delivery_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Standard Delivery Time (hours)
                        </label>
                        <input type="number" name="metter_standard_delivery_time" id="metter_standard_delivery_time" 
                               value="{{ old('metter_standard_delivery_time', $configs['metter_standard_delivery_time'] ?? 24) }}" 
                               step="1" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Expected delivery time for standard service</p>
                    </div>

                    <div>
                        <label for="metter_express_delivery_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Express Delivery Time (hours)
                        </label>
                        <input type="number" name="metter_express_delivery_time" id="metter_express_delivery_time" 
                               value="{{ old('metter_express_delivery_time', $configs['metter_express_delivery_time'] ?? 6) }}" 
                               step="1" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Expected delivery time for express service</p>
                    </div>

                    <div>
                        <label for="metter_same_day_cutoff" class="block text-sm font-medium text-gray-700 mb-2">
                            Same-Day Delivery Cutoff Time
                        </label>
                        <input type="time" name="metter_same_day_cutoff" id="metter_same_day_cutoff" 
                               value="{{ old('metter_same_day_cutoff', $configs['metter_same_day_cutoff'] ?? '14:00') }}" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Orders after this time go to next day</p>
                    </div>

                    <div>
                        <label for="metter_max_delivery_distance" class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Delivery Distance (km)
                        </label>
                        <input type="number" name="metter_max_delivery_distance" id="metter_max_delivery_distance" 
                               value="{{ old('metter_max_delivery_distance', $configs['metter_max_delivery_distance'] ?? 100) }}" 
                               step="1" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Maximum distance for delivery service</p>
                    </div>

                    <div>
                        <label for="metter_operating_hours_start" class="block text-sm font-medium text-gray-700 mb-2">
                            Operating Hours Start
                        </label>
                        <input type="time" name="metter_operating_hours_start" id="metter_operating_hours_start" 
                               value="{{ old('metter_operating_hours_start', $configs['metter_operating_hours_start'] ?? '08:00') }}" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Daily operation start time</p>
                    </div>

                    <div>
                        <label for="metter_operating_hours_end" class="block text-sm font-medium text-gray-700 mb-2">
                            Operating Hours End
                        </label>
                        <input type="time" name="metter_operating_hours_end" id="metter_operating_hours_end" 
                               value="{{ old('metter_operating_hours_end', $configs['metter_operating_hours_end'] ?? '18:00') }}" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Daily operation end time</p>
                    </div>

                    <div>
                        <label for="metter_weekend_delivery" class="block text-sm font-medium text-gray-700 mb-2">
                            Weekend Delivery
                        </label>
                        <select name="metter_weekend_delivery" id="metter_weekend_delivery"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_weekend_delivery', $configs['metter_weekend_delivery'] ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_weekend_delivery', $configs['metter_weekend_delivery'] ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Allow deliveries on weekends</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone-Based Pricing -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Zone-Based Pricing</h2>
                <p class="text-sm text-gray-500 mt-1">Configure flat rates for different delivery zones</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="metter_mainland_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Mainland Rate (₦)
                        </label>
                        <input type="number" name="metter_mainland_rate" id="metter_mainland_rate" 
                               value="{{ old('metter_mainland_rate', $configs['metter_mainland_rate'] ?? 3500) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Flat rate for mainland deliveries</p>
                    </div>

                    <div>
                        <label for="metter_island_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Island Rate (₦)
                        </label>
                        <input type="number" name="metter_island_rate" id="metter_island_rate" 
                               value="{{ old('metter_island_rate', $configs['metter_island_rate'] ?? 5000) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Flat rate for island deliveries</p>
                    </div>

                    <div>
                        <label for="metter_inter_zone_surcharge" class="block text-sm font-medium text-gray-700 mb-2">
                            Inter-Zone Surcharge (₦)
                        </label>
                        <input type="number" name="metter_inter_zone_surcharge" id="metter_inter_zone_surcharge" 
                               value="{{ old('metter_inter_zone_surcharge', $configs['metter_inter_zone_surcharge'] ?? 1500) }}" 
                               step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Extra charge for cross-zone deliveries</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation & General Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Calculation & General Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure how prices are calculated and displayed</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="metter_calculation_method" class="block text-sm font-medium text-gray-700 mb-2">
                            Calculation Method
                        </label>
                        <select name="metter_calculation_method" id="metter_calculation_method"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="distance" {{ old('metter_calculation_method', $configs['metter_calculation_method'] ?? 'distance') == 'distance' ? 'selected' : '' }}>Distance-Based</option>
                            <option value="zone" {{ old('metter_calculation_method', $configs['metter_calculation_method'] ?? 'distance') == 'zone' ? 'selected' : '' }}>Zone-Based</option>
                            <option value="hybrid" {{ old('metter_calculation_method', $configs['metter_calculation_method'] ?? 'distance') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">How to calculate delivery prices</p>
                    </div>

                    <div>
                        <label for="metter_round_up_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Round Up Price
                        </label>
                        <select name="metter_round_up_price" id="metter_round_up_price"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_round_up_price', $configs['metter_round_up_price'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('metter_round_up_price', $configs['metter_round_up_price'] ?? 1) == 0 ? 'selected' : '' }}>No</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Round final price to nearest hundred</p>
                    </div>

                    <div>
                        <label for="metter_include_vat" class="block text-sm font-medium text-gray-700 mb-2">
                            Include VAT
                        </label>
                        <select name="metter_include_vat" id="metter_include_vat"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_include_vat', $configs['metter_include_vat'] ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('metter_include_vat', $configs['metter_include_vat'] ?? 0) == 0 ? 'selected' : '' }}>No</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Add VAT to calculations</p>
                    </div>

                    <div>
                        <label for="metter_vat_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            VAT Rate (%)
                        </label>
                        <input type="number" name="metter_vat_rate" id="metter_vat_rate" 
                               value="{{ old('metter_vat_rate', $configs['metter_vat_rate'] ?? 7.5) }}" 
                               step="0.1" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">VAT percentage rate</p>
                    </div>

                    <div>
                        <label for="metter_peak_hours_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                            Peak Hours Pricing
                        </label>
                        <select name="metter_peak_hours_enabled" id="metter_peak_hours_enabled"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_peak_hours_enabled', $configs['metter_peak_hours_enabled'] ?? 0) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_peak_hours_enabled', $configs['metter_peak_hours_enabled'] ?? 0) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Enable peak hours surcharge</p>
                    </div>

                    <div>
                        <label for="metter_peak_hours_multiplier" class="block text-sm font-medium text-gray-700 mb-2">
                            Peak Hours Multiplier
                        </label>
                        <input type="number" name="metter_peak_hours_multiplier" id="metter_peak_hours_multiplier" 
                               value="{{ old('metter_peak_hours_multiplier', $configs['metter_peak_hours_multiplier'] ?? 1.2) }}" 
                               step="0.1" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Price multiplier during peak hours</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features & API Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Features & API Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Enable or disable various features and integrations</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="metter_api_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                            API Access
                        </label>
                        <select name="metter_api_enabled" id="metter_api_enabled"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_api_enabled', $configs['metter_api_enabled'] ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_api_enabled', $configs['metter_api_enabled'] ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Enable Metter API access</p>
                    </div>

                    <div>
                        <label for="metter_tracking_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                            Real-Time Tracking
                        </label>
                        <select name="metter_tracking_enabled" id="metter_tracking_enabled"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_tracking_enabled', $configs['metter_tracking_enabled'] ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_tracking_enabled', $configs['metter_tracking_enabled'] ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Enable real-time order tracking</p>
                    </div>

                    <div>
                        <label for="metter_sms_notifications" class="block text-sm font-medium text-gray-700 mb-2">
                            SMS Notifications
                        </label>
                        <select name="metter_sms_notifications" id="metter_sms_notifications"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_sms_notifications', $configs['metter_sms_notifications'] ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_sms_notifications', $configs['metter_sms_notifications'] ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Send SMS notifications to customers</p>
                    </div>

                    <div>
                        <label for="metter_email_notifications" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Notifications
                        </label>
                        <select name="metter_email_notifications" id="metter_email_notifications"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="1" {{ old('metter_email_notifications', $configs['metter_email_notifications'] ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ old('metter_email_notifications', $configs['metter_email_notifications'] ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Send email notifications to customers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Area Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Service Area Configuration</h2>
                <p class="text-sm text-gray-500 mt-1">Define your service coverage area</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="metter_service_city" class="block text-sm font-medium text-gray-700 mb-2">
                            Primary Service City
                        </label>
                        <input type="text" name="metter_service_city" id="metter_service_city" 
                               value="{{ old('metter_service_city', $configs['metter_service_city'] ?? 'Lagos') }}" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Main city for delivery operations</p>
                    </div>

                    <div>
                        <label for="metter_service_state" class="block text-sm font-medium text-gray-700 mb-2">
                            Service State
                        </label>
                        <input type="text" name="metter_service_state" id="metter_service_state" 
                               value="{{ old('metter_service_state', $configs['metter_service_state'] ?? 'Lagos State') }}" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">State where service is available</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover" style="transition: background-color 0.2s ease;">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
