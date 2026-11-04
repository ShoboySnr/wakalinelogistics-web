<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use App\Modules\DeliveryCalculator\Services\DeliveryPriceService;
use Illuminate\Support\Carbon;

/**
 * Sequences a rider's undelivered orders into the order they should be done in.
 *
 * It keeps the three rules the Laravel optimiser already had — honour priority,
 * never route a drop-off before its pickup, otherwise go to the nearest stop —
 * and adds two things the dispatch problem actually needs: time windows (an
 * order due "before 12:00" is pulled forward when its ETA is at risk) and a
 * start point that is the rider's live GPS position, so a route recomputed
 * mid-run begins from where the rider actually is.
 */
class RouteOptimizationService
{
    /** Minutes of driving per km in Lagos traffic, and dwell time per stop. */
    private const MIN_PER_KM = 4.0;
    private const STOP_MINUTES = 5;

    /** A GPS fix older than this is treated as stale and ignored. */
    private const FIX_FRESH_MINUTES = 120;

    /** A deadline within this many minutes of the projected ETA is "at risk". */
    private const AT_RISK_BUFFER_MINUTES = 20;

    public function __construct(private DeliveryPriceService $pricing)
    {
    }

    public function optimize(Rider $rider, ?float $startLat = null, ?float $startLng = null): array
    {
        $orders = $rider->orders()
            ->with('client:id,name,company_name')
            ->whereIn('status', ['pending', 'confirmed', 'accepted', 'arrived_at_pickup', 'picked_up', 'in_transit', 'arrived_at_dropoff'])
            ->orderBy('created_at')
            ->get();

        $stops = $this->buildStops($orders);
        $start = $this->resolveStart($rider, $orders, $startLat, $startLng);

        if (empty($stops) || $start === null) {
            return [
                'start' => $start,
                'stops' => [],
                'summary' => ['stops' => 0, 'total_km' => 0.0, 'total_minutes' => 0, 'at_risk' => 0, 'late' => 0],
                'generated_at' => now()->toIso8601String(),
            ];
        }

        $ordered = $this->sequence($stops, $start);
        [$ordered, $summary] = $this->schedule($ordered, $start);

        return [
            'start' => $start,
            'stops' => $ordered,
            'summary' => $summary,
            'maps_url' => $this->mapsUrl($start, $ordered),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** One pickup stop (when the parcel is not yet collected) and one drop-off per order. */
    private function buildStops($orders): array
    {
        $stops = [];

        foreach ($orders as $order) {
            $pickedUp = in_array($order->status, ['picked_up', 'in_transit', 'arrived_at_dropoff'], true) || $order->pickup_date !== null;

            if (! $pickedUp) {
                $coord = $this->coordFor($order->pickup_latitude, $order->pickup_longitude, $order->pickup_address);
                if ($coord) {
                    $stops[] = $this->stop('pickup', $order, $coord);
                }
            }

            $coord = $this->coordFor($order->delivery_latitude, $order->delivery_longitude, $order->delivery_address);
            if ($coord) {
                $stops[] = $this->stop('dropoff', $order, $coord);
            }
        }

        return $stops;
    }

    private function stop(string $type, Order $order, array $coord): array
    {
        return [
            'type' => $type,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'client_name' => $order->client?->company_name ?: $order->client?->name,
            'address' => $type === 'pickup' ? $order->pickup_address : $order->delivery_address,
            'lat' => $coord['lat'],
            'lng' => $coord['lng'],
            'contact' => $type === 'pickup' ? $order->sender_name : $order->receiver_name,
            'phone' => $type === 'pickup' ? $order->sender_phone : $order->receiver_phone,
            'item_description' => $order->item_description,
            'quantity' => $order->quantity,
            'price' => $order->price !== null ? (float) $order->price : null,
            'payment_method' => $order->payment_method,
            'payment_collected_at' => $order->payment_collected_at?->toIso8601String(),
            'payment_confirmed_at' => $order->payment_confirmed_at?->toIso8601String(),
            'priority_level' => $order->priority_level ?: 'normal',
            'deadline' => $type === 'dropoff' ? $order->delivery_deadline : null,
        ];
    }

    private function resolveStart(Rider $rider, $orders, ?float $lat, ?float $lng): ?array
    {
        if ($lat !== null && $lng !== null) {
            return ['lat' => $lat, 'lng' => $lng, 'label' => 'Current location', 'source' => 'override'];
        }

        if ($rider->current_latitude !== null && $rider->current_longitude !== null
            && $rider->last_location_update
            && Carbon::parse($rider->last_location_update)->gt(now()->subMinutes(self::FIX_FRESH_MINUTES))) {
            return [
                'lat' => (float) $rider->current_latitude,
                'lng' => (float) $rider->current_longitude,
                'label' => "Rider's current location",
                'source' => 'rider_gps',
            ];
        }

        // Fall back to the first order's pickup so a route can still be drawn.
        foreach ($orders as $order) {
            $coord = $this->coordFor($order->pickup_latitude, $order->pickup_longitude, $order->pickup_address);
            if ($coord) {
                return $coord + ['label' => 'First pickup', 'source' => 'fallback'];
            }
        }

        return null;
    }

    /** Deadline-aware nearest-neighbour with the pickup-before-drop-off constraint. */
    private function sequence(array $stops, array $start): array
    {
        $route = [];
        $remaining = $stops;
        $current = ['lat' => $start['lat'], 'lng' => $start['lng']];
        $pickedUp = [];
        $elapsed = 0.0;

        // Orders whose pickup isn't in this route are already in the vehicle.
        $pickupOrderIds = array_column(array_filter($stops, fn ($s) => $s['type'] === 'pickup'), 'order_id');
        foreach ($stops as $s) {
            if ($s['type'] === 'dropoff' && ! in_array($s['order_id'], $pickupOrderIds, true)) {
                $pickedUp[$s['order_id']] = true;
            }
        }

        while (! empty($remaining)) {
            $best = null;
            $bestIndex = -1;
            $bestScore = null;

            foreach ($remaining as $i => $stop) {
                if ($stop['type'] === 'dropoff' && empty($pickedUp[$stop['order_id']])) {
                    continue; // can't deliver what isn't picked up yet
                }

                $km = $this->haversine($current['lat'], $current['lng'], $stop['lat'], $stop['lng']);
                $arrival = $elapsed + $km * self::MIN_PER_KM + self::STOP_MINUTES;

                $score = $this->score($stop, $km, $arrival);

                if ($bestScore === null || $this->better($score, $bestScore)) {
                    $bestScore = $score;
                    $best = $stop;
                    $bestIndex = $i;
                }
            }

            if ($best === null) {
                break; // only unreachable drop-offs remain (shouldn't happen)
            }

            $km = $this->haversine($current['lat'], $current['lng'], $best['lat'], $best['lng']);
            $best['leg_km'] = round($km, 2);
            $best['leg_minutes'] = (int) round($km * self::MIN_PER_KM);
            $elapsed += $best['leg_minutes'] + self::STOP_MINUTES;

            $route[] = $best;
            if ($best['type'] === 'pickup') {
                $pickedUp[$best['order_id']] = true;
            }

            $current = ['lat' => $best['lat'], 'lng' => $best['lng']];
            array_splice($remaining, $bestIndex, 1);
        }

        return $route;
    }

    /**
     * Lower is better. A deadline at risk dominates (tier 0), then priority
     * (urgent before high before normal), then plain distance.
     */
    private function score(array $stop, float $km, float $arrivalMinutes): array
    {
        $deadlineTier = 1;
        $deadlineKey = PHP_INT_MAX;

        if ($stop['deadline']) {
            $minutesToDeadline = now()->diffInMinutes(Carbon::parse($stop['deadline']), false);
            // At risk when the projected arrival lands near or past the deadline.
            if ($arrivalMinutes >= $minutesToDeadline - self::AT_RISK_BUFFER_MINUTES) {
                $deadlineTier = 0;
                $deadlineKey = $minutesToDeadline; // earliest deadline first
            }
        }

        return [
            'deadline_tier' => $deadlineTier,
            'deadline_key' => $deadlineKey,
            'priority' => -$this->priorityWeight($stop['priority_level']),
            'distance' => $km,
        ];
    }

    private function better(array $a, array $b): bool
    {
        foreach (['deadline_tier', 'deadline_key', 'priority', 'distance'] as $k) {
            if ($a[$k] < $b[$k]) {
                return true;
            }
            if ($a[$k] > $b[$k]) {
                return false;
            }
        }

        return false;
    }

    /** Lay ETAs over the sequenced stops and flag each deadline. */
    private function schedule(array $route, array $start): array
    {
        $cumulative = 0;
        $totalKm = 0.0;
        $atRisk = 0;
        $late = 0;

        foreach ($route as $i => &$stop) {
            $cumulative += ($stop['leg_minutes'] ?? 0) + self::STOP_MINUTES;
            $totalKm += $stop['leg_km'] ?? 0;
            $eta = now()->addMinutes($cumulative);

            $stop['seq'] = $i + 1;
            $stop['cumulative_minutes'] = $cumulative;
            $stop['eta'] = $eta->toIso8601String();
            $stop['eta_label'] = $eta->format('g:i A');

            $status = 'none';
            if ($stop['deadline']) {
                $deadline = Carbon::parse($stop['deadline']);
                if ($eta->gt($deadline)) {
                    $status = 'late';
                    $late++;
                } elseif ($eta->gt($deadline->copy()->subMinutes(self::AT_RISK_BUFFER_MINUTES))) {
                    $status = 'at_risk';
                    $atRisk++;
                } else {
                    $status = 'on_time';
                }
                $stop['deadline'] = $deadline->toIso8601String();
                $stop['deadline_label'] = $deadline->format('g:i A');
            }
            $stop['deadline_status'] = $status;
        }
        unset($stop);

        return [$route, [
            'stops' => count($route),
            'total_km' => round($totalKm, 2),
            'total_minutes' => $cumulative,
            'at_risk' => $atRisk,
            'late' => $late,
        ]];
    }

    private function coordFor($lat, $lng, ?string $address): ?array
    {
        if ($lat !== null && $lng !== null) {
            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        if ($address) {
            $geo = $this->pricing->geocodeAddress($address);
            if ($geo) {
                return ['lat' => $geo['lat'], 'lng' => $geo['lng']];
            }
        }

        return null;
    }

    private function priorityWeight(string $level): int
    {
        return match ($level) {
            'urgent' => 3,
            'high' => 2,
            default => 1,
        };
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)) * 1.35;
    }

    private function mapsUrl(array $start, array $stops): string
    {
        $points = [$start['lat'] . ',' . $start['lng']];
        foreach ($stops as $s) {
            $points[] = $s['lat'] . ',' . $s['lng'];
        }

        $origin = array_shift($points);
        $destination = array_pop($points) ?? $origin;

        $url = 'https://www.google.com/maps/dir/?api=1&origin=' . $origin . '&destination=' . $destination . '&travelmode=driving';
        if (! empty($points)) {
            $url .= '&waypoints=' . implode('|', $points);
        }

        return $url;
    }
}
