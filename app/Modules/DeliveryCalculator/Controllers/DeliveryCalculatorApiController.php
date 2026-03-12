<?php

namespace App\Modules\DeliveryCalculator\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryCalculatorApiController extends Controller
{
    private DeliveryPriceService $priceService;

    public function __construct(DeliveryPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
        ]);

        $result = $this->priceService->processDeliveryCalculation(
            $validated['pickup_address'],
            $validated['dropoff_address']
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'message' => 'Failed to calculate delivery price'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pickup' => [
                    'address' => $result['pickup'],
                    'formatted_address' => $result['pickup_formatted'] ?? $result['pickup'],
                    'zone' => $result['pickup_zone']
                ],
                'delivery' => [
                    'address' => $result['delivery'],
                    'formatted_address' => $result['delivery_formatted'] ?? $result['delivery'],
                    'zone' => $result['delivery_zone']
                ],
                'distance_km' => $result['distance_km'],
                'delivery_fee' => $result['delivery_fee'],
                'currency' => 'NGN'
            ]
        ], 200);
    }

    public function quickQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
        ]);

        $result = $this->priceService->processDeliveryCalculation(
            $validated['pickup_address'],
            $validated['dropoff_address']
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'delivery_fee' => $result['delivery_fee'],
            'distance_km' => $result['distance_km'],
            'currency' => 'NGN'
        ], 200);
    }

    public function getZones(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'zones' => [
                'Zone A' => [
                    'name' => 'Zone A',
                    'type' => 'Mainland',
                    'locations' => ['Agege', 'Ogba', 'Ikeja', 'Iju', 'Abule Egba', 'Fagba', 'Iju Ishaga', 'Iju Fagba', 'Ifako', 'Ijaiye']
                ],
                'Zone B' => [
                    'name' => 'Zone B',
                    'type' => 'Mainland',
                    'locations' => ['Ketu', 'Ojota', 'Maryland', 'Gbagada', 'Ogudu', 'Alapere', 'Magodo', 'Omole']
                ],
                'Zone C' => [
                    'name' => 'Zone C',
                    'type' => 'Mainland',
                    'locations' => ['Yaba', 'Surulere', 'Isolo', 'Festac', 'Oshodi', 'Mushin', 'Ikotun', 'Egbeda', 'Amuwo Odofin']
                ],
                'Zone D' => [
                    'name' => 'Zone D',
                    'type' => 'Island',
                    'locations' => ['Ikoyi', 'Victoria Island', 'VI', 'Lagos Island', 'Onikan', 'Marina', 'Falomo']
                ],
                'Zone E' => [
                    'name' => 'Zone E',
                    'type' => 'Island',
                    'locations' => ['Lekki', 'Ajah', 'Sangotedo', 'Chevron', 'VGC', 'Ikate', 'Oniru', 'Jakande', 'Eti Osa', 'Abraham Adesanya']
                ],
                'Zone F' => [
                    'name' => 'Zone F',
                    'type' => 'Interstate',
                    'locations' => ['Ikorodu', 'Mowe', 'Sango Ota', 'Ota', 'Agbara', 'Arepo', 'Berger', 'Magboro']
                ]
            ]
        ], 200);
    }

    public function getPricingRules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'pricing' => [
                'base_fee' => 2500,
                'per_km_rate' => 100,
                'adjustments' => [
                    'bridge_crossing' => [
                        'description' => 'Mainland to Island or Island to Mainland',
                        'fee' => 500
                    ],
                    'lekki_toll' => [
                        'description' => 'Delivery to Lekki, Ajah, or Sangotedo',
                        'fee' => 500
                    ],
                    'apapa_congestion' => [
                        'description' => 'Delivery to Apapa or Ajegunle',
                        'fee' => 1000
                    ],
                    'interstate' => [
                        'description' => 'Delivery to Mowe, Sango Ota, or Ota',
                        'fee' => 1000
                    ]
                ],
                'rounding' => 'Rounded to nearest 500 Naira',
                'currency' => 'NGN'
            ]
        ], 200);
    }

    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'Wakaline Delivery Calculator API',
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String()
        ], 200);
    }
}
