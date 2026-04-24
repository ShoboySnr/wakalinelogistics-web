<?php

namespace App\Services;

use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedAnalyticsService
{
    private $startDate;
    private $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ?? now()->startOfMonth();
        $this->endDate = $endDate ?? now()->endOfMonth();
    }

    /**
     * Get comprehensive advanced analytics
     */
    public function getAdvancedAnalytics(): array
    {
        return [
            'capacity_analysis' => $this->getCapacityAnalysis(),
            'revenue_forecasting' => $this->getRevenueForecast(),
            'bike_investment_analysis' => $this->getBikeInvestmentAnalysis(),
            'route_efficiency' => $this->getRouteEfficiencyAnalysis(),
            'peak_hours_analysis' => $this->getPeakHoursAnalysis(),
            'customer_segmentation' => $this->getCustomerSegmentation(),
            'expense_breakdown' => $this->getDetailedExpenseBreakdown(),
            'profitability_by_zone' => $this->getProfitabilityByZone(),
            'rider_performance_deep' => $this->getDeepRiderPerformance(),
            'market_opportunity' => $this->getMarketOpportunityAnalysis(),
            'cash_flow_projection' => $this->getCashFlowProjection(),
            'competitive_positioning' => $this->getCompetitivePositioning(),
            'operational_bottlenecks' => $this->getOperationalBottlenecks(),
            'growth_scenarios' => $this->getGrowthScenarios(),
        ];
    }

    /**
     * Capacity Analysis - How much can we handle vs current load
     */
    private function getCapacityAnalysis(): array
    {
        $activeRiders = Rider::where('status', 'active')->count();
        $totalOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        
        // Assumptions for Lagos bike logistics
        $ordersPerRiderPerDay = 8; // Conservative estimate for Lagos traffic
        $workingDaysInPeriod = $this->startDate->diffInDays($this->endDate);
        
        $theoreticalCapacity = $activeRiders * $ordersPerRiderPerDay * $workingDaysInPeriod;
        $actualUtilization = $theoreticalCapacity > 0 ? ($totalOrders / $theoreticalCapacity) * 100 : 0;
        $unutilizedCapacity = max(0, $theoreticalCapacity - $totalOrders);
        
        return [
            'active_riders' => $activeRiders,
            'theoretical_capacity' => round($theoreticalCapacity, 2),
            'actual_orders' => $totalOrders,
            'utilization_rate' => round($actualUtilization, 2),
            'unutilized_capacity' => round($unutilizedCapacity, 2),
            'capacity_status' => $this->getCapacityStatus($actualUtilization),
            'recommendation' => $this->getCapacityRecommendation($actualUtilization, $activeRiders),
        ];
    }

    /**
     * Revenue Forecasting - Predict next 3 months
     */
    private function getRevenueForecast(): array
    {
        // Get last 6 months of revenue data
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Order::whereMonth('delivery_date', $date->month)
                ->whereYear('delivery_date', $date->year)
                ->where('status', 'delivered')
                ->sum('price');
            $monthlyRevenue[] = $revenue;
        }

        // Simple linear regression for trend
        $avgGrowthRate = $this->calculateGrowthRate($monthlyRevenue);
        $lastMonthRevenue = end($monthlyRevenue);
        
        // Forecast next 3 months
        $forecast = [];
        for ($i = 1; $i <= 3; $i++) {
            $forecastedRevenue = $lastMonthRevenue * pow(1 + ($avgGrowthRate / 100), $i);
            $forecast[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'forecasted_revenue' => round($forecastedRevenue, 2),
                'confidence' => $this->getConfidenceLevel($monthlyRevenue),
            ];
        }

        return [
            'historical_data' => $monthlyRevenue,
            'average_growth_rate' => round($avgGrowthRate, 2),
            'forecast' => $forecast,
            'trend' => $avgGrowthRate > 0 ? 'growing' : ($avgGrowthRate < 0 ? 'declining' : 'stable'),
        ];
    }

    /**
     * Bike Investment Analysis - Should you buy more bikes?
     */
    private function getBikeInvestmentAnalysis(): array
    {
        $activeRiders = Rider::where('status', 'active')->count();
        $totalOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        $revenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->sum('price');
        $expenses = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])->sum('amount');
        
        // Lagos-specific costs (in Naira)
        $bikePrice = 1600000; // Bike price in Lagos
        $monthlyBikeMaintenance = 25000; // Fuel, repairs, insurance
        $riderSalary = 120000; // Monthly salary for rider
        $avgRevenuePerOrder = $totalOrders > 0 ? $revenue / $totalOrders : 5000;
        
        // Calculate if adding a bike makes sense
        $ordersPerRider = $activeRiders > 0 ? $totalOrders / $activeRiders : 0;
        $additionalOrdersNeeded = 120; // Orders per month to break even on new bike
        
        $monthlyOperatingCost = $monthlyBikeMaintenance + $riderSalary;
        $breakEvenOrders = ceil($monthlyOperatingCost / $avgRevenuePerOrder);
        $monthsToRecoverInvestment = ceil($bikePrice / ($avgRevenuePerOrder * $additionalOrdersNeeded - $monthlyOperatingCost));
        
        // ROI Calculation
        $yearlyRevenueFromNewBike = $avgRevenuePerOrder * $additionalOrdersNeeded * 12;
        $yearlyOperatingCost = $monthlyOperatingCost * 12;
        $yearlyProfit = $yearlyRevenueFromNewBike - $yearlyOperatingCost;
        $roi = $bikePrice > 0 ? (($yearlyProfit - $bikePrice) / $bikePrice) * 100 : 0;
        
        return [
            'current_riders' => $activeRiders,
            'current_orders_per_rider' => round($ordersPerRider, 2),
            'bike_investment' => [
                'bike_cost' => $bikePrice,
                'monthly_operating_cost' => $monthlyOperatingCost,
                'break_even_orders_per_month' => $breakEvenOrders,
                'months_to_recover_investment' => $monthsToRecoverInvestment,
                'first_year_roi' => round($roi, 2),
            ],
            'recommendation' => $this->getBikeInvestmentRecommendation($ordersPerRider, $roi, $activeRiders),
            'scenarios' => [
                'conservative' => [
                    'additional_bikes' => 1,
                    'expected_monthly_revenue' => round($avgRevenuePerOrder * 100, 2),
                    'expected_monthly_profit' => round(($avgRevenuePerOrder * 100) - $monthlyOperatingCost, 2),
                ],
                'moderate' => [
                    'additional_bikes' => 2,
                    'expected_monthly_revenue' => round($avgRevenuePerOrder * 200, 2),
                    'expected_monthly_profit' => round(($avgRevenuePerOrder * 200) - ($monthlyOperatingCost * 2), 2),
                ],
                'aggressive' => [
                    'additional_bikes' => 3,
                    'expected_monthly_revenue' => round($avgRevenuePerOrder * 300, 2),
                    'expected_monthly_profit' => round(($avgRevenuePerOrder * 300) - ($monthlyOperatingCost * 3), 2),
                ],
            ],
        ];
    }

    /**
     * Route Efficiency Analysis
     */
    private function getRouteEfficiencyAnalysis(): array
    {
        // Analyze delivery times
        $deliveryTimes = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('pickup_date')
            ->whereNotNull('delivery_date')
            ->selectRaw('TIMESTAMPDIFF(HOUR, pickup_date, delivery_date) as delivery_hours')
            ->get()
            ->pluck('delivery_hours');

        $avgDeliveryTime = $deliveryTimes->avg() ?? 0;
        $fastestDelivery = $deliveryTimes->min() ?? 0;
        $slowestDelivery = $deliveryTimes->max() ?? 0;
        
        // Get total deliveries for efficiency calculation
        $totalDeliveries = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->count();

        return [
            'average_delivery_time' => round($avgDeliveryTime, 2),
            'fastest_delivery' => round($fastestDelivery, 2),
            'slowest_delivery' => round($slowestDelivery, 2),
            'total_deliveries' => $totalDeliveries,
            'efficiency_score' => $this->calculateEfficiencyScore($avgDeliveryTime),
            'recommendations' => $this->getRouteOptimizationRecommendations($avgDeliveryTime),
        ];
    }

    /**
     * Peak Hours Analysis
     */
    private function getPeakHoursAnalysis(): array
    {
        $hourlyOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('order_count', 'hour')
            ->toArray();

        $peakHours = [];
        $maxOrders = max($hourlyOrders ?: [0]);
        
        foreach ($hourlyOrders as $hour => $count) {
            if ($count >= $maxOrders * 0.7) { // 70% of peak
                $peakHours[] = $hour;
            }
        }

        return [
            'hourly_distribution' => $hourlyOrders,
            'peak_hours' => $peakHours,
            'peak_hour_orders' => $maxOrders,
            'off_peak_average' => round(array_sum($hourlyOrders) / max(count($hourlyOrders), 1), 2),
            'recommendations' => $this->getPeakHourRecommendations($peakHours, $maxOrders),
        ];
    }

    /**
     * Customer Segmentation - RFM Analysis
     */
    private function getCustomerSegmentation(): array
    {
        $customers = Order::select('customer_phone', 'customer_name',
            DB::raw('COUNT(*) as frequency'),
            DB::raw('MAX(created_at) as last_order_date'),
            DB::raw('SUM(price) as total_spent'))
            ->groupBy('customer_phone', 'customer_name')
            ->get();

        $segments = [
            'vip_customers' => $customers->where('frequency', '>=', 10)->where('total_spent', '>=', 100000)->count(),
            'regular_customers' => $customers->where('frequency', '>=', 5)->where('frequency', '<', 10)->count(),
            'occasional_customers' => $customers->where('frequency', '>=', 2)->where('frequency', '<', 5)->count(),
            'one_time_customers' => $customers->where('frequency', '=', 1)->count(),
        ];

        $topCustomers = $customers->sortByDesc('total_spent')->take(10)->values();

        return [
            'total_customers' => $customers->count(),
            'segments' => $segments,
            'top_10_customers' => $topCustomers,
            'vip_revenue_contribution' => $this->calculateVIPContribution($customers),
            'churn_risk' => $this->identifyChurnRisk($customers),
            'recommendations' => $this->getCustomerSegmentRecommendations($segments),
        ];
    }

    /**
     * Detailed Expense Breakdown
     */
    private function getDetailedExpenseBreakdown(): array
    {
        $expensesByCategory = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get();

        $totalExpenses = $expensesByCategory->sum('total');
        
        $breakdown = [];
        foreach ($expensesByCategory as $expense) {
            $percentage = $totalExpenses > 0 ? ($expense->total / $totalExpenses) * 100 : 0;
            $breakdown[] = [
                'category' => $expense->category,
                'amount' => $expense->total,
                'count' => $expense->count,
                'percentage' => round($percentage, 2),
                'avg_per_transaction' => $expense->count > 0 ? round($expense->total / $expense->count, 2) : 0,
            ];
        }

        return [
            'total_expenses' => $totalExpenses,
            'breakdown' => collect($breakdown)->sortByDesc('amount')->values()->toArray(),
            'largest_category' => collect($breakdown)->sortByDesc('amount')->first(),
            'cost_reduction_opportunities' => $this->identifyCostReductionOpportunities($breakdown),
        ];
    }

    /**
     * Profitability by Zone/Location
     */
    private function getProfitabilityByZone(): array
    {
        // Since we don't have zone/location columns, analyze by customer distribution
        $customerDistribution = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->select('customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(price) as revenue'))
            ->groupBy('customer_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $totalRevenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->sum('price');

        return [
            'top_revenue_sources' => $customerDistribution,
            'total_revenue' => $totalRevenue,
            'revenue_concentration' => $customerDistribution->count() > 0 ? 
                round(($customerDistribution->first()->revenue ?? 0) / max($totalRevenue, 1) * 100, 2) : 0,
            'recommendations' => [
                'Diversify customer base to reduce dependency',
                'Expand to new customer segments',
                'Focus marketing on high-value customer profiles',
            ],
        ];
    }

    /**
     * Deep Rider Performance Analysis
     */
    private function getDeepRiderPerformance(): array
    {
        $riders = Rider::where('status', 'active')
            ->withCount(['orders as total_deliveries' => function($q) {
                $q->where('status', 'delivered')
                  ->whereBetween('delivery_date', [$this->startDate, $this->endDate]);
            }])
            ->with(['orders' => function($q) {
                $q->where('status', 'delivered')
                  ->whereBetween('delivery_date', [$this->startDate, $this->endDate])
                  ->select('rider_id', DB::raw('SUM(price) as revenue'));
            }])
            ->get();

        $topPerformers = $riders->sortByDesc('total_deliveries')->take(5);
        $underPerformers = $riders->where('total_deliveries', '<', 20);

        return [
            'total_active_riders' => $riders->count(),
            'top_performers' => $topPerformers->values(),
            'underperformers' => $underPerformers->values(),
            'average_deliveries' => round($riders->avg('total_deliveries'), 2),
            'performance_gap' => $this->calculatePerformanceGap($riders),
            'training_needs' => $this->identifyTrainingNeeds($underPerformers),
        ];
    }

    /**
     * Market Opportunity Analysis
     */
    private function getMarketOpportunityAnalysis(): array
    {
        // Lagos logistics market insights
        $currentRevenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->sum('price');

        return [
            'current_market_share' => 'Small player in Lagos market',
            'addressable_market_size' => 'Lagos logistics market: ₦50B+ annually',
            'growth_potential' => [
                'e_commerce_boom' => 'Online shopping growing 40% YoY in Nigeria',
                'food_delivery' => 'Food delivery market expanding rapidly',
                'corporate_contracts' => 'B2B logistics underserved',
                'last_mile_delivery' => 'High demand for same-day delivery',
            ],
            'untapped_segments' => [
                'Pharmaceuticals delivery',
                'Document courier services',
                'Grocery delivery partnerships',
                'Inter-state express delivery',
            ],
            'recommendations' => $this->getMarketExpansionRecommendations(),
        ];
    }

    /**
     * Cash Flow Projection
     */
    private function getCashFlowProjection(): array
    {
        $monthlyRevenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->sum('price');
        $monthlyExpenses = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        $projections = [];
        for ($i = 1; $i <= 6; $i++) {
            $projectedRevenue = $monthlyRevenue * (1 + (0.05 * $i)); // 5% monthly growth assumption
            $projectedExpenses = $monthlyExpenses * (1 + (0.03 * $i)); // 3% expense growth
            
            $projections[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'projected_revenue' => round($projectedRevenue, 2),
                'projected_expenses' => round($projectedExpenses, 2),
                'projected_profit' => round($projectedRevenue - $projectedExpenses, 2),
                'cumulative_profit' => round(($projectedRevenue - $projectedExpenses) * $i, 2),
            ];
        }

        return [
            'current_monthly_cash_flow' => $monthlyRevenue - $monthlyExpenses,
            'projections' => $projections,
            'cash_runway' => $this->calculateCashRunway($monthlyRevenue, $monthlyExpenses),
            'recommendations' => $this->getCashFlowRecommendations($monthlyRevenue, $monthlyExpenses),
        ];
    }

    /**
     * Competitive Positioning
     */
    private function getCompetitivePositioning(): array
    {
        return [
            'strengths' => [
                'Local knowledge of Lagos routes',
                'Flexible pricing options',
                'Growing customer base',
                'Bike logistics (faster in traffic)',
            ],
            'weaknesses' => [
                'Limited fleet size',
                'No mobile app yet',
                'Limited brand awareness',
                'Cash-heavy operations',
            ],
            'opportunities' => [
                'Partner with e-commerce platforms',
                'Expand to new Lagos zones',
                'Offer subscription packages',
                'Technology adoption (tracking, payments)',
            ],
            'threats' => [
                'Established competitors (Gokada, MAX, etc.)',
                'Fuel price volatility',
                'Regulatory changes',
                'Economic downturn affecting demand',
            ],
            'competitive_advantages' => $this->identifyCompetitiveAdvantages(),
        ];
    }

    /**
     * Operational Bottlenecks
     */
    private function getOperationalBottlenecks(): array
    {
        $pendingOrders = Order::where('status', 'pending')->count();
        $avgProcessingTime = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('pickup_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, pickup_date)) as avg_hours')
            ->value('avg_hours') ?? 0;

        return [
            'pending_orders_backlog' => $pendingOrders,
            'average_order_processing_time' => round($avgProcessingTime, 2),
            'identified_bottlenecks' => $this->identifyBottlenecks($pendingOrders, $avgProcessingTime),
            'solutions' => $this->proposeBottleneckSolutions(),
        ];
    }

    /**
     * Growth Scenarios
     */
    private function getGrowthScenarios(): array
    {
        $currentRevenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->sum('price');
        $currentExpenses = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        // Lagos-specific costs
        $bikePrice = 1600000; // Current bike price
        $monthlyBikeMaintenance = 25000;
        $riderSalary = 120000;
        $monthlyOperatingCostPerBike = $monthlyBikeMaintenance + $riderSalary;
        $marketingBudget = 500000; // Marketing investment
        $techInvestment = 1000000; // App, tracking, automation

        // Get current profit/loss status
        $currentProfit = $currentRevenue - $currentExpenses;
        $currentMargin = $currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0;
        
        // Conservative: 1 bike, minimal marketing
        $conservative_bikes = 1;
        $conservative_investment = ($bikePrice * $conservative_bikes) + 200000; // 1 bike + basic marketing
        $conservative_additionalExpenses = ($monthlyOperatingCostPerBike * $conservative_bikes); // Full monthly cost for new bike
        $conservative_revenue = $currentRevenue * 1.15; // 15% growth with 1 bike
        $conservative_newExpenses = $currentExpenses + $conservative_additionalExpenses;
        
        // Moderate: 2-3 bikes, good marketing
        $moderate_bikes = 2;
        $moderate_investment = ($bikePrice * $moderate_bikes) + $marketingBudget;
        $moderate_additionalExpenses = ($monthlyOperatingCostPerBike * $moderate_bikes) + ($marketingBudget * 0.1); // Monthly marketing
        $moderate_revenue = $currentRevenue * 1.35; // 35% growth with 2 bikes + marketing
        $moderate_newExpenses = $currentExpenses + $moderate_additionalExpenses;
        
        // Aggressive: 4-5 bikes, full marketing + tech
        $aggressive_bikes = 4;
        $aggressive_investment = ($bikePrice * $aggressive_bikes) + $marketingBudget + $techInvestment;
        $aggressive_additionalExpenses = ($monthlyOperatingCostPerBike * $aggressive_bikes) + ($marketingBudget * 0.15) + ($techInvestment * 0.05); // Monthly costs
        $aggressive_revenue = $currentRevenue * 1.75; // 75% growth with 4 bikes + marketing + tech
        $aggressive_newExpenses = $currentExpenses + $aggressive_additionalExpenses;

        // Calculate payback periods
        $conservative_newProfit = $conservative_revenue - $conservative_newExpenses;
        $conservative_incrementalProfit = $conservative_newProfit - $currentProfit;
        $conservative_payback = $conservative_incrementalProfit > 0 ? 
            ceil($conservative_investment / $conservative_incrementalProfit) : 
            'N/A - Not profitable';
        
        $moderate_newProfit = $moderate_revenue - $moderate_newExpenses;
        $moderate_incrementalProfit = $moderate_newProfit - $currentProfit;
        $moderate_payback = $moderate_incrementalProfit > 0 ? 
            ceil($moderate_investment / $moderate_incrementalProfit) : 
            'N/A - Not profitable';
        
        $aggressive_newProfit = $aggressive_revenue - $aggressive_newExpenses;
        $aggressive_incrementalProfit = $aggressive_newProfit - $currentProfit;
        $aggressive_payback = $aggressive_incrementalProfit > 0 ? 
            ceil($aggressive_investment / $aggressive_incrementalProfit) : 
            'N/A - Not profitable';

        return [
            'conservative' => [
                'revenue_growth' => '15% increase',
                'additional_investment' => '₦' . number_format($conservative_investment) . ' (' . $conservative_bikes . ' bike + basic marketing)',
                'bikes_added' => $conservative_bikes,
                'investment_breakdown' => [
                    'bikes' => $bikePrice * $conservative_bikes,
                    'marketing' => 200000,
                    'total' => $conservative_investment,
                ],
                'expected_monthly_revenue' => round($conservative_revenue, 2),
                'expected_monthly_expenses' => round($conservative_newExpenses, 2),
                'expected_monthly_profit' => round($conservative_newProfit, 2),
                'incremental_profit' => round($conservative_incrementalProfit, 2),
                'timeline' => '3-6 months to implement',
                'roi_months' => $conservative_payback,
            ],
            'moderate' => [
                'revenue_growth' => '35% increase',
                'additional_investment' => '₦' . number_format($moderate_investment) . ' (' . $moderate_bikes . ' bikes + marketing)',
                'bikes_added' => $moderate_bikes,
                'investment_breakdown' => [
                    'bikes' => $bikePrice * $moderate_bikes,
                    'marketing' => $marketingBudget,
                    'total' => $moderate_investment,
                ],
                'expected_monthly_revenue' => round($moderate_revenue, 2),
                'expected_monthly_expenses' => round($moderate_newExpenses, 2),
                'expected_monthly_profit' => round($moderate_newProfit, 2),
                'incremental_profit' => round($moderate_incrementalProfit, 2),
                'timeline' => '6-12 months to implement',
                'roi_months' => $moderate_payback,
            ],
            'aggressive' => [
                'revenue_growth' => '75% increase',
                'additional_investment' => '₦' . number_format($aggressive_investment) . ' (' . $aggressive_bikes . ' bikes + marketing + tech)',
                'bikes_added' => $aggressive_bikes,
                'investment_breakdown' => [
                    'bikes' => $bikePrice * $aggressive_bikes,
                    'marketing' => $marketingBudget,
                    'technology' => $techInvestment,
                    'total' => $aggressive_investment,
                ],
                'expected_monthly_revenue' => round($aggressive_revenue, 2),
                'expected_monthly_expenses' => round($aggressive_newExpenses, 2),
                'expected_monthly_profit' => round($aggressive_newProfit, 2),
                'incremental_profit' => round($aggressive_incrementalProfit, 2),
                'timeline' => '12-18 months to implement',
                'roi_months' => $aggressive_payback,
            ],
        ];
    }

    // Helper methods
    private function getCapacityStatus($utilization): string
    {
        if ($utilization < 50) return 'Underutilized - Need more orders';
        if ($utilization < 75) return 'Good - Room for growth';
        if ($utilization < 90) return 'Optimal - Well balanced';
        return 'Overloaded - Need more riders';
    }

    private function getCapacityRecommendation($utilization, $riders): string
    {
        if ($utilization > 90) {
            return "URGENT: Add " . ceil($riders * 0.3) . " more riders immediately to handle demand";
        } elseif ($utilization < 50) {
            return "Focus on marketing to increase orders. Current capacity can handle 2x more orders";
        }
        return "Maintain current fleet size. Monitor weekly for changes";
    }

    private function calculateGrowthRate($data): float
    {
        if (count($data) < 2) return 0;
        
        $growthRates = [];
        for ($i = 1; $i < count($data); $i++) {
            if ($data[$i-1] > 0) {
                $growthRates[] = (($data[$i] - $data[$i-1]) / $data[$i-1]) * 100;
            }
        }
        
        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    private function getConfidenceLevel($data): string
    {
        $variance = $this->calculateVariance($data);
        if ($variance < 1000000) return 'High';
        if ($variance < 5000000) return 'Medium';
        return 'Low';
    }

    private function calculateVariance($data): float
    {
        $mean = array_sum($data) / max(count($data), 1);
        $variance = 0;
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        return $variance / max(count($data), 1);
    }

    private function getBikeInvestmentRecommendation($ordersPerRider, $roi, $riders): string
    {
        $roiRounded = round($roi, 2);
        if ($ordersPerRider > 15 && $roi > 50) {
            return "STRONG BUY: Add 2-3 bikes immediately. High demand and excellent ROI of {$roiRounded}%";
        } elseif ($ordersPerRider > 10 && $roi > 30) {
            return "BUY: Add 1-2 bikes. Good ROI of {$roiRounded}% and growing demand";
        } elseif ($ordersPerRider < 5) {
            return "HOLD: Focus on increasing orders first before adding bikes. Current riders underutilized";
        }
        return "MONITOR: Current capacity adequate. Review monthly";
    }

    private function calculateEfficiencyScore($avgTime): int
    {
        if ($avgTime < 12) return 95;
        if ($avgTime < 24) return 85;
        if ($avgTime < 48) return 70;
        return 50;
    }

    private function getRouteOptimizationRecommendations($avgTime): array
    {
        $recommendations = [];
        if ($avgTime > 24) {
            $recommendations[] = 'Implement route optimization software';
            $recommendations[] = 'Pre-position riders in high-demand areas';
            $recommendations[] = 'Offer incentives for faster deliveries';
        }
        return $recommendations;
    }

    private function getPeakHourRecommendations($peakHours, $maxOrders): array
    {
        return [
            'Schedule more riders during ' . implode(', ', array_map(fn($h) => $h . ':00', $peakHours)),
            'Offer off-peak discounts (10-15% off) to balance demand',
            'Pre-assign orders during peak hours to reduce delays',
        ];
    }

    private function calculateVIPContribution($customers): float
    {
        $vipRevenue = $customers->where('frequency', '>=', 10)->sum('total_spent');
        $totalRevenue = $customers->sum('total_spent');
        return $totalRevenue > 0 ? round(($vipRevenue / $totalRevenue) * 100, 2) : 0;
    }

    private function identifyChurnRisk($customers): array
    {
        $atRisk = $customers->filter(function($customer) {
            $daysSinceLastOrder = now()->diffInDays($customer->last_order_date);
            return $daysSinceLastOrder > 60 && $customer->frequency > 3;
        });

        return [
            'count' => $atRisk->count(),
            'potential_lost_revenue' => round($atRisk->sum('total_spent') / 12, 2),
        ];
    }

    private function getCustomerSegmentRecommendations($segments): array
    {
        return [
            "VIP customers ({$segments['vip_customers']}): Offer exclusive benefits, priority service",
            "Regular customers ({$segments['regular_customers']}): Launch loyalty program to increase frequency",
            "Occasional customers ({$segments['occasional_customers']}): Send targeted promotions",
            "One-time customers ({$segments['one_time_customers']}): Win-back campaign with discount",
        ];
    }

    private function identifyCostReductionOpportunities($breakdown): array
    {
        $opportunities = [];
        foreach ($breakdown as $expense) {
            if ($expense['percentage'] > 30) {
                $opportunities[] = "Negotiate bulk discounts for {$expense['category']} (currently {$expense['percentage']}% of expenses)";
            }
        }
        return $opportunities;
    }

    private function calculatePerformanceGap($riders): array
    {
        $deliveries = $riders->pluck('total_deliveries');
        return [
            'top_performer' => $deliveries->max(),
            'average' => round($deliveries->avg(), 2),
            'bottom_performer' => $deliveries->min(),
            'gap_percentage' => $deliveries->avg() > 0 ? round((($deliveries->max() - $deliveries->min()) / $deliveries->avg()) * 100, 2) : 0,
        ];
    }

    private function identifyTrainingNeeds($underPerformers): array
    {
        return [
            'Route optimization training',
            'Customer service excellence',
            'Time management skills',
            'Lagos traffic navigation',
        ];
    }

    private function getMarketExpansionRecommendations(): array
    {
        return [
            'Partner with Jumia, Konga for e-commerce deliveries',
            'Approach restaurants for food delivery contracts',
            'Target corporate clients for document delivery',
            'Expand to Lekki, VI, Ajah (high-value zones)',
        ];
    }

    private function calculateCashRunway($revenue, $expenses): string
    {
        $monthlyBurn = $expenses - $revenue;
        if ($monthlyBurn <= 0) return 'Profitable - No runway concern';
        
        // Assuming ₦2M in reserves (adjust based on actual)
        $reserves = 2000000;
        $months = ceil($reserves / $monthlyBurn);
        return "{$months} months at current burn rate";
    }

    private function getCashFlowRecommendations($revenue, $expenses): array
    {
        $profit = $revenue - $expenses;
        if ($profit < 0) {
            return [
                'CRITICAL: Reduce expenses by 20% immediately',
                'Increase prices by 10-15%',
                'Focus on high-margin orders only',
            ];
        }
        return [
            'Build 3-month cash reserve',
            'Reinvest 30% of profits in growth',
            'Maintain emergency fund for bike repairs',
        ];
    }

    private function identifyCompetitiveAdvantages(): array
    {
        return [
            'Bikes navigate Lagos traffic faster than cars',
            'Lower operating costs than van/truck logistics',
            'Personalized service for local customers',
            'Flexible and agile operations',
        ];
    }

    private function identifyBottlenecks($pending, $processingTime): array
    {
        $bottlenecks = [];
        if ($pending > 20) {
            $bottlenecks[] = 'Order backlog - Need more riders or better dispatch';
        }
        if ($processingTime > 2) {
            $bottlenecks[] = 'Slow order processing - Automate order assignment';
        }
        return $bottlenecks;
    }

    private function proposeBottleneckSolutions(): array
    {
        return [
            'Implement automated order dispatch system',
            'Use WhatsApp Business API for instant notifications',
            'Create rider mobile app for real-time updates',
            'Set up central dispatch hub in strategic location',
        ];
    }
}
