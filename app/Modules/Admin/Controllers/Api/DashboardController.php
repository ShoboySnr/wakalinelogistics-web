<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Resources\OrderResource;
use App\Modules\Admin\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboard): JsonResponse
    {
        $bike = fn ($bike) => [
            'id' => $bike->id,
            'bike_number' => $bike->bike_number,
            'rider' => $bike->assignedRider?->name,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $dashboard->stats(),
                'recent_orders' => OrderResource::collection($dashboard->recentOrders()),
                'low_stocks' => $dashboard->lowStocks()->map(fn ($stock) => [
                    'id' => $stock->id,
                    'client_id' => $stock->client_id,
                    'client_name' => $stock->client->name . ($stock->client->company_name ? " ({$stock->client->company_name})" : ''),
                    'product_name' => $stock->product_name,
                    'quantity' => $stock->quantity,
                ])->values(),
                'upcoming_maintenance' => $dashboard->upcomingMaintenance()->map(fn ($b) => $bike($b) + [
                    'next_maintenance_date' => $b->next_maintenance_date,
                ])->values(),
                'expired_bike_docs' => $dashboard->expiredBikeDocs()->map(fn ($item) => $bike($item['bike']) + [
                    'documents' => collect($item['expired_docs'])->pluck('name')->values(),
                ])->values(),
                'expiring_bike_docs' => $dashboard->expiringBikeDocs()->map(fn ($item) => $bike($item['bike']) + [
                    'documents' => collect($item['expiring_docs'])->pluck('name')->values(),
                    'days_remaining' => collect($item['expiring_docs'])->min('days_remaining'),
                ])->values(),
            ],
        ]);
    }
}
