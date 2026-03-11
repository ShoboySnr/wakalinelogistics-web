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
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'formatted_address' => $data['results'][0]['formatted_address']
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
    ): int {
        $basePrice = 2500 + ($distanceKm * 100);

        $pickupIsIsland = ZoneDetector::isIsland($pickupZone);
        $deliveryIsIsland = ZoneDetector::isIsland($deliveryZone);

        if (($pickupIsIsland && !$deliveryIsIsland) || (!$pickupIsIsland && $deliveryIsIsland)) {
            $basePrice += 500;
        }

        $deliveryLower = strtolower($deliveryAddress);

        if (str_contains($deliveryLower, 'lekki') || 
            str_contains($deliveryLower, 'ajah') || 
            str_contains($deliveryLower, 'sangotedo')) {
            $basePrice += 500;
        }

        if (str_contains($deliveryLower, 'apapa') || 
            str_contains($deliveryLower, 'ajegunle')) {
            $basePrice += 1000;
        }

        if (str_contains($deliveryLower, 'mowe') || 
            str_contains($deliveryLower, 'sango ota') || 
            str_contains($deliveryLower, 'ota')) {
            $basePrice += 1000;
        }

        return (int) (round($basePrice / 500) * 500);
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

        $pickupZone = ZoneDetector::detectZone($pickupGeo['formatted_address']);
        $deliveryZone = ZoneDetector::detectZone($deliveryGeo['formatted_address']);

        $deliveryFee = $this->calculatePrice(
            $distance,
            $pickupZone,
            $deliveryZone,
            $deliveryGeo['formatted_address']
        );

        return [
            'pickup' => $pickupGeo['formatted_address'],
            'delivery' => $deliveryGeo['formatted_address'],
            'distance_km' => $distance,
            'pickup_zone' => $pickupZone,
            'delivery_zone' => $deliveryZone,
            'delivery_fee' => $deliveryFee
        ];
    }
}
