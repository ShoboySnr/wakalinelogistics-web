<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Admin\Models\SubscriptionPlan;
use App\Modules\Admin\Models\CreditPackage;
use Illuminate\Support\Str;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Subscription Plans
        $plans = [
            [
                'name' => 'Weekly Starter',
                'slug' => 'weekly-starter',
                'description' => 'Perfect for occasional deliveries. Get 10% bonus credits.',
                'credits' => 50000,
                'price' => 50000,
                'billing_cycle' => 'weekly',
                'validity_days' => 7,
                'features' => [
                    '50,000 base credits',
                    '5,000 bonus credits (10%)',
                    'Valid for 7 days',
                    'Priority support',
                    'Real-time tracking',
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Monthly Pro',
                'slug' => 'monthly-pro',
                'description' => 'Best value for regular users. Get 15% bonus credits.',
                'credits' => 250000,
                'price' => 250000,
                'billing_cycle' => 'monthly',
                'validity_days' => 30,
                'features' => [
                    '250,000 base credits',
                    '37,500 bonus credits (15%)',
                    'Valid for 30 days',
                    'Priority support',
                    'Real-time tracking',
                    'Dedicated account manager',
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        // Create Credit Packages
        $packages = [
            [
                'name' => 'Starter Pack',
                'slug' => 'starter-pack',
                'description' => 'Get started with our delivery services',
                'credits' => 10000,
                'price' => 10000,
                'bonus_credits' => 0,
                'validity_days' => null, // No expiry
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic Pack',
                'slug' => 'basic-pack',
                'description' => 'Save 2% with bonus credits',
                'credits' => 25000,
                'price' => 25000,
                'bonus_credits' => 500,
                'validity_days' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Standard Pack',
                'slug' => 'standard-pack',
                'description' => 'Most popular! Save 5% with bonus credits',
                'credits' => 50000,
                'price' => 50000,
                'bonus_credits' => 2500,
                'validity_days' => null,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium Pack',
                'slug' => 'premium-pack',
                'description' => 'Save 8% with bonus credits',
                'credits' => 100000,
                'price' => 100000,
                'bonus_credits' => 8000,
                'validity_days' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Business Pack',
                'slug' => 'business-pack',
                'description' => 'Save 10% with bonus credits',
                'credits' => 250000,
                'price' => 250000,
                'bonus_credits' => 25000,
                'validity_days' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Enterprise Pack',
                'slug' => 'enterprise-pack',
                'description' => 'Maximum savings! Save 12% with bonus credits',
                'credits' => 500000,
                'price' => 500000,
                'bonus_credits' => 60000,
                'validity_days' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($packages as $packageData) {
            CreditPackage::updateOrCreate(
                ['slug' => $packageData['slug']],
                $packageData
            );
        }

        $this->command->info('Subscription plans and credit packages seeded successfully!');
    }
}
