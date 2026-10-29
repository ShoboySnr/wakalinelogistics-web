<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\Bike;
use App\Modules\Admin\Models\ClientStock;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use Illuminate\Support\Collection;

class DashboardService
{
    public function stats(): array
    {
        $deliveredOn = fn () => Order::where('status', 'delivered')->whereNotNull('delivery_date');

        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'in_transit_orders' => Order::where('status', 'in_transit')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('price'),

            'today_orders' => $deliveredOn()->whereDate('delivery_date', today())->count(),
            'today_revenue' => $deliveredOn()->whereDate('delivery_date', today())->sum('price'),
            'today_pending' => Order::whereDate('created_at', today())->where('status', 'pending')->count(),
            'today_delivered' => Order::whereDate('created_at', today())->where('status', 'delivered')->count(),

            'week_orders' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'week_revenue' => $deliveredOn()->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('price'),

            'month_orders' => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'month_revenue' => $deliveredOn()->whereMonth('delivery_date', now()->month)->whereYear('delivery_date', now()->year)->sum('price'),

            'total_riders' => Rider::count(),
            'active_riders' => Rider::where('status', 'active')->count(),
            'riders_with_orders' => Rider::whereHas('orders', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'in_transit']))->count(),
        ];
    }

    public function recentOrders(int $limit = 10): Collection
    {
        return Order::with('user')->latest()->take($limit)->get();
    }

    public function expiringBikeDocs(int $days = 30): Collection
    {
        return Bike::withExpiringDocuments($days)
            ->with('assignedRider')
            ->get()
            ->map(fn ($bike) => ['bike' => $bike, 'expiring_docs' => $bike->getExpiringDocuments($days)])
            ->filter(fn ($item) => count($item['expiring_docs']) > 0)
            ->values();
    }

    public function expiredBikeDocs(): Collection
    {
        return Bike::withExpiredDocuments()
            ->with('assignedRider')
            ->get()
            ->map(fn ($bike) => ['bike' => $bike, 'expired_docs' => $bike->getExpiredDocuments()])
            ->filter(fn ($item) => count($item['expired_docs']) > 0)
            ->values();
    }

    public function upcomingMaintenance(int $days = 7): Collection
    {
        return Bike::whereNotNull('next_maintenance_date')
            ->whereBetween('next_maintenance_date', [now(), now()->addDays($days)])
            ->with('assignedRider')
            ->orderBy('next_maintenance_date')
            ->get();
    }

    public function lowStocks(): Collection
    {
        return ClientStock::with('client:id,name,company_name')
            ->where('quantity', '<=', 1)
            ->whereHas('client', fn ($q) => $q->where('pod_remittance_enabled', true))
            ->orderBy('quantity')
            ->get();
    }
}
