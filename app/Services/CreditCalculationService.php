<?php

namespace App\Services;

use App\Modules\Admin\Models\DeliveryZone;
use App\Modules\Admin\Models\CreditSetting;

class CreditCalculationService
{
    /**
     * Calculate credits required for a delivery based on pickup and dropoff locations
     */
    public function calculateCredits(
        string $pickupAddress,
        string $dropoffAddress,
        ?float $distance = null,
        ?float $weight = null
    ): array {
        // Extract area/zone from addresses
        $pickupZone = $this->detectZone($pickupAddress);
        $dropoffZone = $this->detectZone($dropoffAddress);

        // Base credits calculation
        $baseCredits = $this->getBaseCredits($pickupZone, $dropoffZone);

        // Additional credits based on distance (if provided)
        $distanceCredits = $this->calculateDistanceCredits($distance);

        // Additional credits based on weight (if provided)
        $weightCredits = $this->calculateWeightCredits($weight);

        $totalCredits = $baseCredits + $distanceCredits + $weightCredits;

        return [
            'total_credits' => $totalCredits,
            'breakdown' => [
                'base_credits' => $baseCredits,
                'distance_credits' => $distanceCredits,
                'weight_credits' => $weightCredits,
            ],
            'pickup_zone' => $pickupZone ? $pickupZone->name : 'Unknown',
            'dropoff_zone' => $dropoffZone ? $dropoffZone->name : 'Unknown',
            'estimated_price' => $this->estimatePrice($totalCredits),
        ];
    }

    /**
     * Detect delivery zone from address
     */
    protected function detectZone(string $address): ?DeliveryZone
    {
        $address = strtolower($address);

        // Try to find zone by checking if address contains any area from zones
        $zones = DeliveryZone::active()->orderBy('sort_order')->get();

        foreach ($zones as $zone) {
            if ($zone->areas) {
                foreach ($zone->areas as $area) {
                    if (stripos($address, $area) !== false) {
                        return $zone;
                    }
                }
            }
        }

        // Check for common keywords
        if (stripos($address, 'island') !== false || 
            stripos($address, 'victoria') !== false || 
            stripos($address, 'ikoyi') !== false || 
            stripos($address, 'lekki') !== false) {
            return DeliveryZone::where('code', 'LI-SAME')->first();
        }

        if (stripos($address, 'ikeja') !== false || 
            stripos($address, 'yaba') !== false || 
            stripos($address, 'surulere') !== false || 
            stripos($address, 'mainland') !== false) {
            return DeliveryZone::where('code', 'ML-SAME')->first();
        }

        // Default to base zone
        return DeliveryZone::active()->orderBy('base_credits')->first();
    }

    /**
     * Get base credits based on pickup and dropoff zones
     */
    protected function getBaseCredits(?DeliveryZone $pickupZone, ?DeliveryZone $dropoffZone): int
    {
        // If zones are unknown, use default
        if (!$pickupZone || !$dropoffZone) {
            return CreditSetting::get('credits_per_delivery', 1);
        }

        // Same zone delivery
        if ($pickupZone->id === $dropoffZone->id) {
            return $pickupZone->base_credits;
        }

        // Cross-zone delivery (Island to Mainland or vice versa)
        if (($pickupZone->code === 'LI-SAME' && $dropoffZone->code === 'ML-SAME') ||
            ($pickupZone->code === 'ML-SAME' && $dropoffZone->code === 'LI-SAME')) {
            $crossZone = DeliveryZone::where('code', 'LI-ML')->first();
            return $crossZone ? $crossZone->base_credits : 2;
        }

        // Use the higher zone's credits
        return max($pickupZone->base_credits, $dropoffZone->base_credits);
    }

    /**
     * Calculate additional credits based on distance
     */
    protected function calculateDistanceCredits(?float $distance): int
    {
        if (!$distance) {
            return 0;
        }

        // Add 1 credit for every 10km beyond 20km
        if ($distance > 20) {
            return (int) ceil(($distance - 20) / 10);
        }

        return 0;
    }

    /**
     * Calculate additional credits based on weight
     */
    protected function calculateWeightCredits(?float $weight): int
    {
        if (!$weight) {
            return 0;
        }

        // Add 1 credit for every 10kg beyond 20kg
        if ($weight > 20) {
            return (int) ceil(($weight - 20) / 10);
        }

        return 0;
    }

    /**
     * Estimate price based on credits
     */
    protected function estimatePrice(int $credits): float
    {
        // Assuming ₦1000 per credit as base
        $pricePerCredit = 1000;
        return $credits * $pricePerCredit;
    }

    /**
     * Get credit cost for a specific route
     */
    public function getCreditCostForRoute(string $pickupArea, string $dropoffArea): int
    {
        $pickupZone = DeliveryZone::findByArea($pickupArea);
        $dropoffZone = DeliveryZone::findByArea($dropoffArea);

        return $this->getBaseCredits($pickupZone, $dropoffZone);
    }

    /**
     * Get all delivery zones with their credit costs
     */
    public function getAllZonePricing(): array
    {
        $zones = DeliveryZone::active()->orderBy('sort_order')->get();

        return $zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'code' => $zone->code,
                'areas' => $zone->areas,
                'credits' => $zone->base_credits,
                'price' => $zone->base_price,
                'formatted_price' => $zone->formatted_price,
            ];
        })->toArray();
    }

    /**
     * Validate if client has enough credits for delivery
     */
    public function validateCreditsForDelivery(
        $client,
        string $pickupAddress,
        string $dropoffAddress,
        ?float $distance = null,
        ?float $weight = null
    ): array {
        $calculation = $this->calculateCredits($pickupAddress, $dropoffAddress, $distance, $weight);
        $requiredCredits = $calculation['total_credits'];
        
        $clientCredits = $client->getOrCreateCredits();
        $hasEnough = $clientCredits->hasEnoughCredits($requiredCredits);

        return [
            'has_enough_credits' => $hasEnough,
            'required_credits' => $requiredCredits,
            'available_credits' => $clientCredits->available_credits,
            'shortage' => $hasEnough ? 0 : ($requiredCredits - $clientCredits->available_credits),
            'calculation' => $calculation,
        ];
    }
}
