<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MetterConfiguration;

class MetterConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $configurations = [
            // Pricing Configurations
            [
                'key' => 'metter_base_rate',
                'value' => '3500',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Base delivery rate in Naira (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_per_km_rate',
                'value' => '100',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Cost per kilometer in Naira (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_minimum_charge',
                'value' => '2000',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Minimum delivery charge in Naira (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_maximum_charge',
                'value' => '50000',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Maximum delivery charge in Naira (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_weight_surcharge',
                'value' => '500',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Additional charge per kg above free weight limit (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_free_weight_limit',
                'value' => '5',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Free weight limit in kilograms',
                'is_active' => true,
            ],
            [
                'key' => 'metter_express_multiplier',
                'value' => '1.5',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Price multiplier for express delivery',
                'is_active' => true,
            ],
            [
                'key' => 'metter_fuel_rate',
                'value' => '200',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Fuel surcharge per delivery in Naira (₦)',
                'is_active' => true,
            ],
            
            // Delivery Configurations
            [
                'key' => 'metter_standard_delivery_time',
                'value' => '24',
                'type' => 'number',
                'category' => 'delivery',
                'description' => 'Standard delivery time in hours',
                'is_active' => true,
            ],
            [
                'key' => 'metter_express_delivery_time',
                'value' => '6',
                'type' => 'number',
                'category' => 'delivery',
                'description' => 'Express delivery time in hours',
                'is_active' => true,
            ],
            [
                'key' => 'metter_same_day_cutoff',
                'value' => '14:00',
                'type' => 'string',
                'category' => 'delivery',
                'description' => 'Cutoff time for same-day delivery',
                'is_active' => true,
            ],
            [
                'key' => 'metter_operating_hours_start',
                'value' => '08:00',
                'type' => 'string',
                'category' => 'delivery',
                'description' => 'Operating hours start time',
                'is_active' => true,
            ],
            [
                'key' => 'metter_operating_hours_end',
                'value' => '18:00',
                'type' => 'string',
                'category' => 'delivery',
                'description' => 'Operating hours end time',
                'is_active' => true,
            ],
            [
                'key' => 'metter_weekend_delivery',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'delivery',
                'description' => 'Enable weekend deliveries',
                'is_active' => true,
            ],
            
            // Zone-based Pricing
            [
                'key' => 'metter_mainland_rate',
                'value' => '3500',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Flat rate for mainland deliveries (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_island_rate',
                'value' => '5000',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Flat rate for island deliveries (₦)',
                'is_active' => true,
            ],
            [
                'key' => 'metter_inter_zone_surcharge',
                'value' => '1500',
                'type' => 'number',
                'category' => 'pricing',
                'description' => 'Additional charge for cross-zone deliveries (₦)',
                'is_active' => true,
            ],
            
            // Calculation Settings
            [
                'key' => 'metter_calculation_method',
                'value' => 'distance',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Calculation method: distance, zone, or hybrid',
                'is_active' => true,
            ],
            [
                'key' => 'metter_round_up_price',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Round up final price to nearest hundred',
                'is_active' => true,
            ],
            [
                'key' => 'metter_include_vat',
                'value' => '0',
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Include VAT in calculations',
                'is_active' => true,
            ],
            [
                'key' => 'metter_vat_rate',
                'value' => '7.5',
                'type' => 'number',
                'category' => 'general',
                'description' => 'VAT rate percentage',
                'is_active' => true,
            ],
            [
                'key' => 'metter_peak_hours_enabled',
                'value' => '0',
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Enable peak hours pricing',
                'is_active' => true,
            ],
            [
                'key' => 'metter_peak_hours_multiplier',
                'value' => '1.2',
                'type' => 'number',
                'category' => 'general',
                'description' => 'Price multiplier during peak hours',
                'is_active' => true,
            ],
            
            // API & Features
            [
                'key' => 'metter_api_enabled',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'api',
                'description' => 'Enable Metter API access',
                'is_active' => true,
            ],
            [
                'key' => 'metter_tracking_enabled',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'features',
                'description' => 'Enable real-time tracking',
                'is_active' => true,
            ],
            [
                'key' => 'metter_sms_notifications',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'features',
                'description' => 'Enable SMS notifications',
                'is_active' => true,
            ],
            [
                'key' => 'metter_email_notifications',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'features',
                'description' => 'Enable email notifications',
                'is_active' => true,
            ],
        ];

        foreach ($configurations as $config) {
            MetterConfiguration::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
