<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Metter\Models\MetterConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MetterApiController extends Controller
{
    private const ZONES = [
        'A' => [
            'ikeja', 'alausa', 'allen', 'oregun', 'agege', 'ogba', 'omole', 'iju',
            'abule egba', 'egbeda', 'idimu', 'ikotun', 'igando', 'ayobo', 'ipaja',
            'command', 'alagbado', 'dopemu', 'akute', 'lambe', 'kay farms', 'alimosho',
            'ifako', 'ijaiye', 'mangoro', 'meiran',
        ],
        'B' => [
            'ketu', 'ojota', 'maryland', 'gbagada', 'ogudu', 'magodo', 'anthony',
            'palmgroove', 'palm grove', 'shomolu', 'bariga', 'ilupeju', 'mile 12',
            'alapere', 'kosofe', 'fadeyi', 'jibowu', 'onipanu', 'mile12',
        ],
        'C' => [
            'yaba', 'surulere', 'isolo', 'festac', 'oshodi', 'mushin', 'apapa',
            'ajegunle', 'ebute metta', 'satellite town', 'alaba', 'badagry', 'ejigbo',
            'okota', 'ago palace', 'ojuelegba', 'lawanson', 'costain', 'okokomaiko',
            'aguda', 'itire', 'coker', 'iganmu', 'ijora', 'otto',
        ],
        'D' => [
            'ikoyi', 'victoria island', 'lagos island', 'marina', ' cms', 'adeola odeku',
            'ozumba mbadiwe', 'bourdillon', 'banana island', 'parkview', 'obalende',
            'falomo', 'balogun', 'idumota', 'tinubu', 'bar beach', 'awolowo',
            'broad street', 'carter bridge', 'epetedo', 'igbosere',
        ],
        'E' => [
            'lekki', 'ajah', 'sangotedo', 'vgc', 'chevron', 'ikate', 'oniru',
            'osapa', 'ikota', 'elegushi', 'epe', 'awoyaya', 'bogije', 'alpha beach',
            'ilasan', 'igbo efon', 'lakowe', 'ibeju', 'jakande', 'orchid',
            'chisco', 'thomas estate', 'igbo-efon', 'ajiwe', 'abraham adesanya',
        ],
        'F' => [
            'ikorodu', 'mowe', 'sango ota', 'agbara', 'arepo', 'redemption camp',
            'sagamu', 'ogijo', 'imota', 'ibafo', 'magboro', 'igbogbo', 'ijede',
            'berger', 'kara', 'simawa', 'lusada', 'ogun', 'ifo', 'ojodu',
            'odogunyan', 'igbolomu',
        ],
    ];

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'pickup_address'  => 'required|string|min:3|max:500',
            'dropoff_address' => 'required|string|min:3|max:500',
        ]);

        $pickup  = $this->geocode($validated['pickup_address']);
        $dropoff = $this->geocode($validated['dropoff_address']);

        if (!$pickup) {
            return response()->json([
                'success' => false,
                'message' => 'Could not find pickup location. Try a more specific address like "Ikeja, Lagos".',
            ], 422);
        }

        if (!$dropoff) {
            return response()->json([
                'success' => false,
                'message' => 'Could not find dropoff location. Try a more specific address like "Lekki Phase 1, Lagos".',
            ], 422);
        }

        $straightLineKm = $this->haversineDistance(
            (float) $pickup['lat'], (float) $pickup['lon'],
            (float) $dropoff['lat'], (float) $dropoff['lon']
        );

        // Road distance is typically ~35% more than straight-line
        $distanceKm = round($straightLineKm * 1.35, 1);

        $pickupZone  = $this->detectZone($pickup['display_name'] . ' ' . $validated['pickup_address']);
        $dropoffZone = $this->detectZone($dropoff['display_name'] . ' ' . $validated['dropoff_address']);

        $fee = $this->calculateFee($distanceKm, $pickupZone, $dropoffZone);

        return response()->json([
            'success' => true,
            'data'    => [
                'pickup' => [
                    'address'           => $validated['pickup_address'],
                    'formatted_address' => $this->formatAddress($pickup['display_name']),
                    'zone'              => "Zone {$pickupZone}",
                ],
                'delivery' => [
                    'address'           => $validated['dropoff_address'],
                    'formatted_address' => $this->formatAddress($dropoff['display_name']),
                    'zone'              => "Zone {$dropoffZone}",
                ],
                'distance_km'  => $distanceKm,
                'delivery_fee' => $fee,
                'currency'     => 'NGN',
            ],
        ]);
    }

    public function quote(Request $request)
    {
        $validated = $request->validate([
            'pickup_address'  => 'required|string|min:3|max:500',
            'dropoff_address' => 'required|string|min:3|max:500',
        ]);

        $pickup  = $this->geocode($validated['pickup_address']);
        $dropoff = $this->geocode($validated['dropoff_address']);

        if (!$pickup || !$dropoff) {
            return response()->json([
                'success' => false,
                'message' => 'Could not resolve one or more addresses.',
            ], 422);
        }

        $distanceKm = round($this->haversineDistance(
            (float) $pickup['lat'], (float) $pickup['lon'],
            (float) $dropoff['lat'], (float) $dropoff['lon']
        ) * 1.35, 1);

        $pickupZone  = $this->detectZone($pickup['display_name'] . ' ' . $validated['pickup_address']);
        $dropoffZone = $this->detectZone($dropoff['display_name'] . ' ' . $validated['dropoff_address']);
        $fee         = $this->calculateFee($distanceKm, $pickupZone, $dropoffZone);

        return response()->json([
            'success'      => true,
            'delivery_fee' => $fee,
            'distance_km'  => $distanceKm,
            'currency'     => 'NGN',
        ]);
    }

    private function geocode(string $address): ?array
    {
        $cacheKey = 'nominatim_' . md5(strtolower(trim($address)));

        return Cache::remember($cacheKey, 86400, function () use ($address) {
            try {
                $query = str_contains(strtolower($address), 'nigeria') ? $address : "{$address}, Nigeria";

                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'WakaLineLogistics/1.0 (wakalinelogistics@gmail.com)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q'               => $query,
                        'format'          => 'json',
                        'limit'           => 1,
                        'countrycodes'    => 'ng',
                        'accept-language' => 'en',
                        'addressdetails'  => '0',
                    ]);

                if (!$response->successful()) return null;

                $results = $response->json();
                return !empty($results) ? $results[0] : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $earthRadius * 2 * asin(sqrt($a));
    }

    private function detectZone(string $address): string
    {
        $lower = strtolower($address);
        foreach (self::ZONES as $zone => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    return $zone;
                }
            }
        }
        return 'A';
    }

    private function formatAddress(string $displayName): string
    {
        $parts = explode(', ', $displayName);
        // Keep first 4 segments max to avoid overly long strings
        return count($parts) > 4 ? implode(', ', array_slice($parts, 0, 4)) : $displayName;
    }

    private function calculateFee(float $distanceKm, string $pickupZone, string $dropoffZone): int
    {
        $config = Cache::remember('metter_configuration', 3600, function () {
            return MetterConfiguration::where('is_active', true)->get()
                ->mapWithKeys(fn($c) => [$c->key => MetterConfiguration::castValue($c->value, $c->type)])
                ->toArray();
        });

        $baseRate   = (float) ($config['metter_base_rate']            ?? 2000);
        $perKmRate  = (float) ($config['metter_per_km_rate']           ?? 100);
        $minCharge  = (float) ($config['metter_minimum_charge']        ?? 2000);
        $maxCharge  = (float) ($config['metter_maximum_charge']        ?? 50000);
        $interZone  = (float) ($config['metter_inter_zone_surcharge']  ?? 1500);
        $islandRate = (float) ($config['metter_island_rate']           ?? 5000);
        $roundUp    =         ($config['metter_round_up_price']        ?? true);

        $islandZones     = ['D', 'E'];
        $isPickupIsland  = in_array($pickupZone, $islandZones);
        $isDropoffIsland = in_array($dropoffZone, $islandZones);

        $fee = $baseRate + ($distanceKm * $perKmRate);

        // Island surcharge when either end is on the island
        if ($isPickupIsland || $isDropoffIsland) {
            $fee += $islandRate;
        }

        // Cross-zone surcharge
        if ($pickupZone !== $dropoffZone) {
            $fee += $interZone;
        }

        // Extra surcharge for interstate Zone F
        if ($pickupZone === 'F' || $dropoffZone === 'F') {
            $fee += $interZone;
        }

        $fee = max($minCharge, min($maxCharge, $fee));

        if ($roundUp) {
            $fee = ceil($fee / 100) * 100;
        }

        return (int) $fee;
    }
}
