<?php

namespace App\Modules\DeliveryCalculator\Services;

use App\Modules\DeliveryCalculator\Helpers\ZoneDetector;
use App\Models\MetterConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeliveryPriceService
{
    private string $googleApiKey;
    private array $config;

    public function __construct()
    {
        $this->googleApiKey = config('services.google_maps.api_key');
        $this->loadConfiguration();
    }

    /**
     * Load Metter configuration from database with caching
     */
    private function loadConfiguration(): void
    {
        $this->config = Cache::remember('metter_configuration', 3600, function () {
            $configs = MetterConfiguration::where('is_active', true)->pluck('value', 'key')->toArray();
            
            // Convert string values to appropriate types
            return [
                'base_rate' => (float) ($configs['metter_base_rate'] ?? 2500),
                'per_km_rate' => (float) ($configs['metter_per_km_rate'] ?? 100),
                'minimum_charge' => (float) ($configs['metter_minimum_charge'] ?? 2500),
                'fuel_rate' => (float) ($configs['metter_fuel_rate'] ?? 700),
                'vehicle_consumption' => 12, // km per liter
                'fuel_surcharge' => 10, // percentage
                'mainland_rate' => (float) ($configs['metter_mainland_rate'] ?? 3500),
                'island_rate' => (float) ($configs['metter_island_rate'] ?? 5000),
                'inter_zone_surcharge' => (float) ($configs['metter_inter_zone_surcharge'] ?? 1500),
                'round_up_price' => (bool) ($configs['metter_round_up_price'] ?? true),
            ];
        });
    }

    /**
     * Get a configuration value
     */
    private function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function geocodeAddress(string $address): ?array
    {
        try {
            $addressWithLagos = $address;
            if (!str_contains(strtolower($address), 'lagos') && !str_contains(strtolower($address), 'nigeria')) {
                $addressWithLagos = $address . ', Lagos, Nigeria';
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $addressWithLagos,
                'key' => $this->googleApiKey,
                'components' => 'country:NG'
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                $formattedAddress = $data['results'][0]['formatted_address'];
                
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'formatted_address' => $formattedAddress,
                    'original_input' => $address
                ];
            }

            Log::error('Geocoding failed', [
                'address' => $address,
                'address_with_lagos' => $addressWithLagos,
                'status' => $data['status'] ?? 'UNKNOWN',
                'error_message' => $data['error_message'] ?? 'No error message',
                'response' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage());
            return null;
        }
    }

    public function calculateDistance(array $origin, array $destination): ?float
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$origin['lat']},{$origin['lng']}",
                'destinations' => "{$destination['lat']},{$destination['lng']}",
                'key' => $this->googleApiKey,
                'mode' => 'driving'
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && 
                !empty($data['rows'][0]['elements'][0]) &&
                $data['rows'][0]['elements'][0]['status'] === 'OK') {
                
                $distanceInMeters = $data['rows'][0]['elements'][0]['distance']['value'];
                return round($distanceInMeters / 1000, 2);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Distance calculation error: ' . $e->getMessage());
            return null;
        }
    }

    public function calculatePrice(
        float $distanceKm,
        string $pickupZone,
        string $deliveryZone,
        string $deliveryAddress
    ): array {
        // Get pricing configuration from database
        $baseFee = $this->getConfig('base_rate', 2500);
        $perKmRate = $this->getConfig('per_km_rate', 100);
        $minimumFee = $this->getConfig('minimum_charge', 2500);
        
        // Get fuel configuration from database
        $fuelPricePerLiter = $this->getConfig('fuel_rate', 700);
        $vehicleConsumption = $this->getConfig('vehicle_consumption', 12);
        $fuelSurcharge = $this->getConfig('fuel_surcharge', 10);

        // Calculate fuel cost
        $litersNeeded = $distanceKm / $vehicleConsumption;
        $fuelCost = $litersNeeded * $fuelPricePerLiter;
        $fuelCostWithSurcharge = $fuelCost * (1 + ($fuelSurcharge / 100));

        // Calculate base price (now includes fuel cost)
        $basePrice = $baseFee + $fuelCostWithSurcharge + ($distanceKm * $perKmRate);

        // Bridge crossing fee (Mainland <-> Island)
        $pickupIsIsland = ZoneDetector::isIsland($pickupZone);
        $deliveryIsIsland = ZoneDetector::isIsland($deliveryZone);
        $interZoneSurcharge = $this->getConfig('inter_zone_surcharge', 1500);

        if (($pickupIsIsland && !$deliveryIsIsland) || (!$pickupIsIsland && $deliveryIsIsland)) {
            $basePrice += $interZoneSurcharge;
        }

        $deliveryLower = strtolower($deliveryAddress);

        // Lekki/Ajah/Sangotedo premium
        if (str_contains($deliveryLower, 'lekki') || 
            str_contains($deliveryLower, 'ajah') || 
            str_contains($deliveryLower, 'sangotedo')) {
            $basePrice += 500; // Lekki premium
        }

        // Apapa/Ajegunle premium
        if (str_contains($deliveryLower, 'apapa') || 
            str_contains($deliveryLower, 'ajegunle')) {
            $basePrice += 1000; // Apapa premium
        }

        // Interstate premium (Mowe/Sango Ota)
        if (str_contains($deliveryLower, 'mowe') || 
            str_contains($deliveryLower, 'sango ota') || 
            str_contains($deliveryLower, 'ota')) {
            $basePrice += 1000; // Interstate premium
        }

        // Round to nearest 500 if round_up_price is enabled
        $roundUpPrice = $this->getConfig('round_up_price', true);
        if ($roundUpPrice) {
            $finalPrice = (int) (round($basePrice / 500) * 500);
        } else {
            $finalPrice = (int) round($basePrice);
        }

        // Ensure minimum fee
        $finalDeliveryFee = max($finalPrice, $minimumFee);
        
        // Return detailed breakdown
        return [
            'delivery_fee' => $finalDeliveryFee,
            'breakdown' => [
                'base_fee' => $baseFee,
                'fuel_cost' => (int) round($fuelCostWithSurcharge),
                'distance_cost' => (int) round($distanceKm * $perKmRate),
                'adjustments' => (int) round($basePrice - $baseFee - $fuelCostWithSurcharge - ($distanceKm * $perKmRate)),
            ]
        ];
    }

    public function processDeliveryCalculation(string $pickupAddress, string $deliveryAddress): array
    {
        $pickupGeo = $this->geocodeAddress($pickupAddress);
        if (!$pickupGeo) {
            return ['error' => 'Could not geocode pickup address'];
        }

        $deliveryGeo = $this->geocodeAddress($deliveryAddress);
        if (!$deliveryGeo) {
            return ['error' => 'Could not geocode delivery address'];
        }

        $distance = $this->calculateDistance($pickupGeo, $deliveryGeo);
        if ($distance === null) {
            return ['error' => 'Could not calculate distance'];
        }

        // Use ORIGINAL INPUT for zone detection (more accurate than Google's formatted address)
        $pickupZone = ZoneDetector::detectZone($pickupAddress);
        $deliveryZone = ZoneDetector::detectZone($deliveryAddress);

        // If zone not found in original input, try formatted address as fallback
        if ($pickupZone === 'Unknown Zone') {
            $pickupZone = ZoneDetector::detectZone($pickupGeo['formatted_address']);
        }
        if ($deliveryZone === 'Unknown Zone') {
            $deliveryZone = ZoneDetector::detectZone($deliveryGeo['formatted_address']);
        }

        $priceData = $this->calculatePrice(
            $distance,
            $pickupZone,
            $deliveryZone,
            $deliveryAddress // Use original input for premium detection too
        );

        return [
            'pickup' => $pickupAddress, // Show original input to user
            'pickup_formatted' => $pickupGeo['formatted_address'], // Keep formatted for reference
            'delivery' => $deliveryAddress, // Show original input to user
            'delivery_formatted' => $deliveryGeo['formatted_address'], // Keep formatted for reference
            'distance_km' => $distance,
            'pickup_zone' => $pickupZone,
            'delivery_zone' => $deliveryZone,
            'delivery_fee' => $priceData['delivery_fee'],
            'price_breakdown' => $priceData['breakdown']
        ];
    }

    public function processMultiPickupCalculation(array $pickupAddresses, string $deliveryAddress): array
    {
        $deliveryGeo = $this->geocodeAddress($deliveryAddress);
        if (!$deliveryGeo) {
            return ['error' => 'Could not geocode delivery address'];
        }

        $deliveryZone = ZoneDetector::detectZone($deliveryAddress);
        if ($deliveryZone === 'Unknown Zone') {
            $deliveryZone = ZoneDetector::detectZone($deliveryGeo['formatted_address']);
        }

        $pickups = [];
        $totalDistance = 0;
        $totalFee = 0;
        $failedPickups = [];

        foreach ($pickupAddresses as $index => $pickupAddress) {
            $pickupGeo = $this->geocodeAddress($pickupAddress);
            
            if (!$pickupGeo) {
                $failedPickups[] = [
                    'address' => $pickupAddress,
                    'error' => 'Could not geocode pickup address'
                ];
                continue;
            }

            $distance = $this->calculateDistance($pickupGeo, $deliveryGeo);
            if ($distance === null) {
                $failedPickups[] = [
                    'address' => $pickupAddress,
                    'error' => 'Could not calculate distance'
                ];
                continue;
            }

            $pickupZone = ZoneDetector::detectZone($pickupAddress);
            if ($pickupZone === 'Unknown Zone') {
                $pickupZone = ZoneDetector::detectZone($pickupGeo['formatted_address']);
            }

            $priceData = $this->calculatePrice(
                $distance,
                $pickupZone,
                $deliveryZone,
                $deliveryAddress
            );

            $pickups[] = [
                'address' => $pickupAddress,
                'formatted_address' => $pickupGeo['formatted_address'],
                'zone' => $pickupZone,
                'distance_km' => $distance,
                'delivery_fee' => $priceData['delivery_fee']
            ];

            $totalDistance += $distance;
            $totalFee += $priceData['delivery_fee'];
        }

        if (empty($pickups)) {
            return ['error' => 'Could not process any pickup addresses'];
        }

        return [
            'delivery' => [
                'address' => $deliveryAddress,
                'formatted_address' => $deliveryGeo['formatted_address'],
                'zone' => $deliveryZone
            ],
            'pickups' => $pickups,
            'failed_pickups' => $failedPickups,
            'summary' => [
                'total_pickups' => count($pickups),
                'failed_pickups' => count($failedPickups),
                'total_distance_km' => round($totalDistance, 2),
                'total_delivery_fee' => $totalFee,
                'currency' => 'NGN'
            ]
        ];
    }
}
