<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $settings = [
            // General System Settings
            [
                'key' => 'general_currency',
                'value' => 'NGN',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Currency code',
            ],
            [
                'key' => 'general_currency_symbol',
                'value' => '₦',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Currency symbol',
            ],
            [
                'key' => 'general_company_name',
                'value' => 'Wakaline Logistics',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Company name',
            ],
            [
                'key' => 'general_company_email',
                'value' => 'info@wakalinelogistics.com',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Company contact email',
            ],
            [
                'key' => 'general_company_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Company contact phone',
            ],
            [
                'key' => 'general_timezone',
                'value' => 'Africa/Lagos',
                'type' => 'string',
                'group' => 'general',
                'description' => 'System timezone',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
