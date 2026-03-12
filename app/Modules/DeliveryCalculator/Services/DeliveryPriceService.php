<?php

namespace App\Modules\DeliveryCalculator\Services;

use App\Modules\DeliveryCalculator\Helpers\ZoneDetector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryPriceService
{
    private string $googleApiKey;

    public function __construct()
    {
        $this->googleApiKey = config('services.google_maps.api_key');
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
        // Get pricing configuration
        $baseFee = config('delivery_pricing.base_fee', 2500);
        $perKmRate = config('delivery_pricing.per_km_rate', 100);
        $adjustments = config('delivery_pricing.adjustments', []);
        $roundTo = config('delivery_pricing.round_to_nearest', 500);
        $minimumFee = config('delivery_pricing.minimum_fee', 2500);
        
        // Get fuel configuration
        $fuelPricePerLiter = config('delivery_pricing.fuel.price_per_liter', 700);
        $vehicleConsumption = config('delivery_pricing.fuel.vehicle_consumption', 12);
        $fuelSurcharge = config('delivery_pricing.fuel.fuel_surcharge_percentage', 10);

        // Calculate fuel cost
        $litersNeeded = $distanceKm / $vehicleConsumption;
        $fuelCost = $litersNeeded * $fuelPricePerLiter;
        $fuelCostWithSurcharge = $fuelCost * (1 + ($fuelSurcharge / 100));

        // Calculate base price (now includes fuel cost)
        $basePrice = $baseFee + $fuelCostWithSurcharge + ($distanceKm * $perKmRate);

        // Bridge crossing fee (Mainland <-> Island)
        $pickupIsIsland = ZoneDetector::isIsland($pickupZone);
        $deliveryIsIsland = ZoneDetector::isIsland($deliveryZone);

        if (($pickupIsIsland && !$deliveryIsIsland) || (!$pickupIsIsland && $deliveryIsIsland)) {
            $basePrice += $adjustments['bridge_crossing'] ?? 500;
        }

        $deliveryLower = strtolower($deliveryAddress);

        // Lekki/Ajah/Sangotedo premium
        if (str_contains($deliveryLower, 'lekki') || 
            str_contains($deliveryLower, 'ajah') || 
            str_contains($deliveryLower, 'sangotedo')) {
            $basePrice += $adjustments['lekki_premium'] ?? 500;
        }

        // Apapa/Ajegunle premium
        if (str_contains($deliveryLower, 'apapa') || 
            str_contains($deliveryLower, 'ajegunle')) {
            $basePrice += $adjustments['apapa_premium'] ?? 1000;
        }

        // Interstate premium (Mowe/Sango Ota)
        if (str_contains($deliveryLower, 'mowe') || 
            str_contains($deliveryLower, 'sango ota') || 
            str_contains($deliveryLower, 'ota')) {
            $basePrice += $adjustments['interstate_premium'] ?? 1000;
        }

        // Round to nearest configured amount
        $finalPrice = (int) (round($basePrice / $roundTo) * $roundTo);

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
}
