<?php

namespace App\Modules\DeliveryCalculator\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use App\Services\ZoneBatchDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryCalculatorApiController extends Controller
{
    private DeliveryPriceService $priceService;

    private ZoneBatchDiscountService $batchDiscount;

    public function __construct(
        DeliveryPriceService $priceService,
        ZoneBatchDiscountService $batchDiscount,
    ) {
        $this->priceService = $priceService;
        $this->batchDiscount = $batchDiscount;
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $client = $request->attributes->get('client');

        $validated = $request->validate([
            'pickup_address' => 'required',
            'dropoff_address' => 'required|string|max:500',
        ]);

        if (is_array($validated['pickup_address'])) {
            $request->validate([
                'pickup_address' => 'array|min:1|max:10',
                'pickup_address.*' => 'required|string|max:500',
            ]);

            $result = $this->priceService->processMultiPickupCalculation(
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

            // Apply client fixed zone rates to each pickup if set
            if ($client && isset($result['pickups'])) {
                $deliveryZone = $result['delivery']['zone'] ?? null;
                $fixedRate = $deliveryZone ? $client->getZoneRate($deliveryZone) : null;
                if ($fixedRate !== null) {
                    $quote = $this->batchDiscount->apply($client, $deliveryZone, (float) $fixedRate);
                    $ratePerPickup = $quote['price'];

                    foreach ($result['pickups'] as &$pickup) {
                        $pickup['delivery_fee'] = $ratePerPickup;
                    }
                    unset($pickup);
                    $result['summary']['total_delivery_fee'] = $ratePerPickup * count($result['pickups']);
                    $result['summary']['batch_discount_percent'] = $quote['zone_discount_percent'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        }

        $request->validate([
            'pickup_address' => 'string|max:500',
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

        $fixedRate = $client ? $client->getZoneRate($result['delivery_zone']) : null;
        $deliveryFee = $fixedRate ?? $result['delivery_fee'];

        // Quote the batch discount too, otherwise the quote and the eventual
        // charge disagree for a client already sending to this zone.
        $quote = $this->batchDiscount->apply(
            $fixedRate !== null ? $client : null,
            $result['delivery_zone'],
            (float) $deliveryFee,
        );
        $deliveryFee = $quote['price'];

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
                'distance_km' => (float) number_format($result['distance_km'], 2, '.', ''),
                'delivery_fee' => $deliveryFee,
                'batch_discount' => $quote['zone_discount_percent'] === null ? null : [
                    'percent' => (float) $quote['zone_discount_percent'],
                    'amount' => (float) $quote['zone_discount_amount'],
                    'base_price' => (float) $quote['base_price'],
                    'orders_in_zone' => $quote['zone_batch_size'],
                    'reason' => $quote['evaluation']['reason'],
                ],
                'currency' => 'NGN'
            ]
        ], 200);
    }

    public function quickQuote(Request $request): JsonResponse
    {
        $client = $request->attributes->get('client');

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

        $fixedRate = $client ? $client->getZoneRate($result['delivery_zone']) : null;
        $deliveryFee = $fixedRate ?? $result['delivery_fee'];

        $quote = $this->batchDiscount->apply(
            $fixedRate !== null ? $client : null,
            $result['delivery_zone'],
            (float) $deliveryFee,
        );

        return response()->json([
            'success' => true,
            'delivery_fee' => $quote['price'],
            'batch_discount_percent' => $quote['zone_discount_percent'],
            'distance_km' => (float) number_format($result['distance_km'], 2, '.', ''),
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
                    'locations' => [
                        'Ikeja', 'Alausa', 'Allen', 'Opebi', 'Oregun', 'GRA Ikeja', 'Computer Village', 'Oba Akran',
                        'Agege', 'Dopemu', 'Pen Cinema', 'Mangoro',
                        'Ogba', 'Omole Phase 1', 'Omole Phase 2',
                        'Iju', 'Iju Ishaga', 'Iju Fagba', 'Ifako', 'Ijaiye', 'Fagba', 'Abule Egba',
                        'Ojokoro', 'Akute', 'Lambe', 'Ajuwon', 'Agbado',
                        'Ikotun', 'Egbeda', 'Idimu', 'Igando', 'Ayobo', 'Ipaja', 'Iyana Ipaja',
                        'Shasha', 'Akowonjo', 'Alagbado', 'Meiran', 'Gowon Estate',
                    ]
                ],
                'Zone B' => [
                    'name' => 'Zone B',
                    'type' => 'Mainland',
                    'locations' => [
                        'Ketu', 'Alapere', 'Ikosi',
                        'Ojota', 'Ogudu', 'Kosofe',
                        'Maryland', 'Mende', 'Mafoluku',
                        'Gbagada', 'Ifako Gbagada', 'Soluyi',
                        'Magodo', 'Magodo Phase 1', 'Magodo Phase 2', 'Shangisha',
                        'Anthony', 'Anthony Village', 'Palmgroove', 'Onipanu', 'Fadeyi',
                        'Shomolu', 'Bariga', 'Akoka', 'Oworonshoki',
                        'Ilupeju', 'Mushin', 'Ladipo',
                        'Mile 12',
                    ]
                ],
                'Zone C' => [
                    'name' => 'Zone C',
                    'type' => 'Mainland',
                    'locations' => [
                        'Yaba', 'Makoko', 'Tejuosho',
                        'Surulere', 'Itire', 'Lawanson', 'Ojuelegba', 'Iponri', 'Eric Moore', 'Bode Thomas',
                        'Ebute Metta', 'Oyingbo', 'Ijora',
                    ]
                ],
                'Zone D' => [
                    'name' => 'Zone D',
                    'type' => 'Mainland',
                    'locations' => [
                        'Festac', 'Amuwo Odofin', 'Mile 2', 'Apple Junction', 'Ago Palace',
                    ]
                ],
                'Zone E' => [
                    'name' => 'Zone E',
                    'type' => 'Island',
                    'locations' => [
                        'Ikoyi', 'Banana Island', 'Parkview', 'Dolphin Estate', 'Osborne', 'Falomo', 'Obalende',
                        'Victoria Island', 'VI', 'Adeola Odeku', 'Ahmadu Bello', 'Ozumba Mbadiwe', 'Oniru',
                        'Lagos Island', 'Marina', 'CMS', 'Broad Street', 'Onikan', 'Idumota', 'Balogun',
                    ]
                ],
                'Zone F' => [
                    'name' => 'Zone F',
                    'type' => 'Island',
                    'locations' => [
                        'Lekki', 'Lekki Phase 1', 'Lekki Phase 2', 'Ikate', 'Elegushi', 'Ilasan',
                        'VGC', 'Victoria Garden City', 'Ikota', 'Eti Osa',
                        'Chevron', 'Chevron Drive', 'Orchid Road',
                        'Osapa London', 'Igbo Efon', 'Idado', 'Agungi',
                    ]
                ],
                'Zone G' => [
                    'name' => 'Zone G',
                    'type' => 'Island',
                    'locations' => [
                        'Sangotedo', 'Monastery', 'Novare Mall', 'Awoyaya', 'Abijo',
                        'Epe', 'Ibeju Lekki', 'Bogije', 'Eleko', 'Dangote Refinery', 'Lekki Free Zone',
                    ]
                ],
                'Zone H' => [
                    'name' => 'Zone H',
                    'type' => 'Interstate',
                    'locations' => [
                        'Ojodu', 'Akiode', 'Berger', 'Ojodu Berger',
                        'Warewa', 'Arepo', 'Magboro',
                    ]
                ],
                'Zone I' => [
                    'name' => 'Zone I',
                    'type' => 'Interstate',
                    'locations' => [
                        'Ikorodu', 'Agric', 'Owutu', 'Ebute Ikorodu', 'Ijede', 'Imota', 'Ibeshe',
                        'Ota', 'Agbara', 'Lusada', 'Ijoko', 'Toll Gate',
                        'Ogijo',
                    ]
                ],
                'Zone J' => [
                    'name' => 'Zone J',
                    'type' => 'Mainland',
                    'locations' => [
                        'Satellite Town', 'Alaba', 'Ojo', 'Okokomaiko',
                        'Badagry', 'Ajangbadi', 'Iyana Iba',
                    ]
                ],
                'Zone K' => [
                    'name' => 'Zone K',
                    'type' => 'Island',
                    'locations' => [
                        'Ajah', 'Badore', 'Abraham Adesanya', 'Lekki Gardens',
                        'Alpha Beach',
                    ]
                ],
                'Zone L' => [
                    'name' => 'Zone L',
                    'type' => 'Mainland',
                    'locations' => [
                        'Oshodi', 'Bolade', 'Shogunle',
                        'Isolo', 'Okota', 'Ejigbo', 'Cele', 'Ajao Estate', 'Bucknor',
                        'Apapa', 'Ajegunle', 'Kirikiri', 'Orile', 'Tincan',
                    ]
                ],
            ]
        ], 200);
    }

    public function getPricingRules(): JsonResponse
    {
        $baseRate = $this->priceService->getConfig('base_rate', 2500);
        $perKmRate = $this->priceService->getConfig('per_km_rate', 100);
        $minimumCharge = $this->priceService->getConfig('minimum_charge', 2500);
        $interZoneSurcharge = $this->priceService->getConfig('inter_zone_surcharge', 1500);

        return response()->json([
            'success' => true,
            'pricing' => [
                'base_fee' => $baseRate,
                'minimum_charge' => $minimumCharge,
                'distance_pricing' => [
                    'type' => 'tiered',
                    'tiers' => [
                        ['from_km' => 0,  'to_km' => 10,  'rate_per_km' => round($perKmRate * 1.00, 2)],
                        ['from_km' => 10, 'to_km' => 20,  'rate_per_km' => round($perKmRate * 0.60, 2)],
                        ['from_km' => 20, 'to_km' => 35,  'rate_per_km' => round($perKmRate * 0.50, 2)],
                        ['from_km' => 35, 'to_km' => 50,  'rate_per_km' => round($perKmRate * 0.45, 2)],
                        ['from_km' => 50, 'to_km' => null, 'rate_per_km' => round($perKmRate * 0.40, 2)],
                    ]
                ],
                'adjustments' => [
                    'bridge_crossing' => [
                        'description' => 'Mainland to Island or Island to Mainland',
                        'fee' => $interZoneSurcharge
                    ],
                    'lekki_premium' => [
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
            'service' => 'Waka Line Logistics Delivery Calculator API',
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String()
        ], 200);
    }
}
