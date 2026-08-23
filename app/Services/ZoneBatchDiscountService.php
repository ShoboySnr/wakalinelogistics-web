<?php

namespace App\Services;

use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Setting;
use App\Modules\DeliveryCalculator\Helpers\ZoneDetector;

/**
 * Same-zone batch discount.
 *
 * When a fixed-pricing client already has undelivered orders heading to a
 * zone, the marginal cost of one more drop in that zone is lower — the rider
 * is going there anyway. This works out the discount that earns.
 *
 * Only orders that have not been picked up yet count (pending / confirmed).
 * Once a rider is in transit the route is committed, so a new order can no
 * longer be folded into that trip.
 */
class ZoneBatchDiscountService
{
    public const SETTING_ENABLED = 'zone_batch_discount_enabled';

    public const SETTING_TIERS = 'zone_batch_discount_tiers';

    public const SETTING_GROUP = 'pricing';

    /** Statuses where the order is still poolable into a future route. */
    public const OPEN_STATUSES = ['pending', 'confirmed'];

    /** Zone label meaning "we could not place this address" — never poolable. */
    public const UNKNOWN_ZONE = 'Unknown Zone';

    /**
     * Shipped defaults. Bands are on the *total* number of open orders in the
     * zone including the one being placed, so a client with one existing open
     * order lands in the 2-6 band on their second.
     */
    public const DEFAULT_TIERS = [
        ['min' => 2, 'max' => 6, 'percent' => 10.0],
        ['min' => 7, 'max' => 12, 'percent' => 12.0],
        ['min' => 13, 'max' => null, 'percent' => 15.0],
    ];

    public function isEnabled(): bool
    {
        return (bool) Setting::get(self::SETTING_ENABLED, false);
    }

    /**
     * Configured bands, normalised and sorted. Falls back to the defaults only
     * when nothing has been saved — an explicitly emptied config disables the
     * discount rather than silently reverting to 10%.
     */
    public function tiers(): array
    {
        $raw = Setting::get(self::SETTING_TIERS);

        if ($raw === null) {
            return self::DEFAULT_TIERS;
        }

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (! is_array($raw)) {
            return [];
        }

        return $this->normaliseTiers($raw);
    }

    /**
     * Sort by lower bound and drop anything unusable, so tier matching can
     * assume a clean ascending list.
     *
     * @return array<int, array{min:int, max:int|null, percent:float}>
     */
    public function normaliseTiers(array $raw): array
    {
        $tiers = [];

        foreach ($raw as $tier) {
            $min = (int) ($tier['min'] ?? 0);
            $max = ($tier['max'] ?? null) === null || $tier['max'] === '' ? null : (int) $tier['max'];
            $percent = (float) ($tier['percent'] ?? 0);

            if ($min < 2 || $percent <= 0 || $percent > 100) {
                continue;
            }
            if ($max !== null && $max < $min) {
                continue;
            }

            $tiers[] = ['min' => $min, 'max' => $max, 'percent' => round($percent, 2)];
        }

        usort($tiers, fn ($a, $b) => $a['min'] <=> $b['min']);

        return $tiers;
    }

    /**
     * Discount percent for a given batch size, or 0.0 when no band covers it.
     */
    public function percentForBatchSize(int $batchSize): float
    {
        foreach ($this->tiers() as $tier) {
            $withinLower = $batchSize >= $tier['min'];
            $withinUpper = $tier['max'] === null || $batchSize <= $tier['max'];

            if ($withinLower && $withinUpper) {
                return $tier['percent'];
            }
        }

        return 0.0;
    }

    /** Open orders this client already has heading to the given zone. */
    public function openOrdersInZone(Client $client, string $zone, ?int $excludeOrderId = null): int
    {
        if ($this->isPoolableZone($zone) === false) {
            return 0;
        }

        return Order::where('client_id', $client->id)
            ->where('delivery_zone', $zone)
            ->whereIn('status', self::OPEN_STATUSES)
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->count();
    }

    /**
     * Work out the discount for an order about to be placed.
     *
     * @return array{
     *     applies: bool,
     *     percent: float,
     *     batch_size: int,
     *     existing_open_orders: int,
     *     zone: string,
     *     reason: string
     * }
     */
    public function evaluate(?Client $client, ?string $zone, ?float $basePrice): array
    {
        $result = [
            'applies' => false,
            'percent' => 0.0,
            'batch_size' => 0,
            'existing_open_orders' => 0,
            'zone' => (string) $zone,
            'reason' => '',
        ];

        if (! $this->isEnabled()) {
            return ['reason' => 'Batch discount is disabled.'] + $result;
        }
        if (! $client) {
            return ['reason' => 'Order has no client attached.'] + $result;
        }
        if (! $this->isPoolableZone($zone)) {
            return ['reason' => 'Delivery zone could not be determined.'] + $result;
        }
        // The discount comes off a fixed zone rate. A client without a rate for
        // this zone is priced by distance, where there is no agreed figure to
        // discount from.
        if ($client->getZoneRate($zone) === null) {
            return ['reason' => "Client has no fixed rate for {$zone}."] + $result;
        }
        if ($basePrice === null || $basePrice <= 0) {
            return ['reason' => 'No base price to discount.'] + $result;
        }

        $existing = $this->openOrdersInZone($client, $zone);
        $batchSize = $existing + 1; // this order counts towards its own band
        $percent = $this->percentForBatchSize($batchSize);

        $result['zone'] = $zone;
        $result['existing_open_orders'] = $existing;
        $result['batch_size'] = $batchSize;

        if ($percent <= 0) {
            $result['reason'] = $existing === 0
                ? "First open order to {$zone} — nothing to pool with."
                : "No discount band covers a batch of {$batchSize}.";

            return $result;
        }

        $result['applies'] = true;
        $result['percent'] = $percent;
        $result['reason'] = sprintf(
            '%s%% off — %d open order%s already heading to %s.',
            rtrim(rtrim(number_format($percent, 2), '0'), '.'),
            $existing,
            $existing === 1 ? '' : 's',
            $zone,
        );

        return $result;
    }

    /**
     * Apply the discount to a base price and return the order fields to persist.
     *
     * @return array{
     *     price: float,
     *     base_price: float,
     *     zone_discount_percent: float|null,
     *     zone_discount_amount: float|null,
     *     zone_batch_size: int|null,
     *     evaluation: array
     * }
     */
    public function apply(?Client $client, ?string $zone, float $basePrice): array
    {
        $evaluation = $this->evaluate($client, $zone, $basePrice);

        if (! $evaluation['applies']) {
            return [
                'price' => round($basePrice, 2),
                'base_price' => round($basePrice, 2),
                'zone_discount_percent' => null,
                'zone_discount_amount' => null,
                'zone_batch_size' => null,
                'evaluation' => $evaluation,
            ];
        }

        $discount = round($basePrice * ($evaluation['percent'] / 100), 2);

        return [
            'price' => round($basePrice - $discount, 2),
            'base_price' => round($basePrice, 2),
            'zone_discount_percent' => $evaluation['percent'],
            'zone_discount_amount' => $discount,
            'zone_batch_size' => $evaluation['batch_size'],
            'evaluation' => $evaluation,
        ];
    }

    /** Resolve a zone from a raw address string. */
    public function detectZone(?string $address): ?string
    {
        if (! $address) {
            return null;
        }

        return ZoneDetector::detectZone($address);
    }

    /**
     * An unresolved zone must never pool. Two addresses that both failed
     * detection have nothing in common, and treating them as a match would
     * discount unrelated deliveries.
     */
    public function isPoolableZone(?string $zone): bool
    {
        return $zone !== null
            && $zone !== ''
            && $zone !== self::UNKNOWN_ZONE;
    }
}
