<?php

namespace App\Services;

use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusinessIntelligenceService
{
    private $startDate;
    private $endDate;

    /**
     * Get comprehensive business analytics
     */
    public function getBusinessAnalytics($startDate = null, $endDate = null): array
    {
        $this->startDate = $startDate ?? now()->startOfMonth();
        $this->endDate = $endDate ?? now()->endOfMonth();

        return [
            'financial_health' => $this->getFinancialHealth(),
            'operational_efficiency' => $this->getOperationalEfficiency(),
            'customer_insights' => $this->getCustomerInsights(),
            'rider_performance' => $this->getRiderPerformance(),
            'growth_metrics' => $this->getGrowthMetrics(),
            'risk_indicators' => $this->getRiskIndicators(),
            'ai_recommendations' => $this->getAIRecommendations(),
            'date_range' => [
                'start' => $this->startDate->format('Y-m-d'),
                'end' => $this->endDate->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Financial Health Analysis
     */
    private function getFinancialHealth(): array
    {
        // Current period data (based on selected date range)
        $currentRevenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->sum('price');

        $currentExpenses = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        // Calculate previous period (same duration before start date)
        $periodDays = $this->startDate->diffInDays($this->endDate);
        $prevStartDate = $this->startDate->copy()->subDays($periodDays + 1);
        $prevEndDate = $this->startDate->copy()->subDay();

        // Previous period data
        $lastMonthRevenue = Order::whereBetween('delivery_date', [$prevStartDate, $prevEndDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->sum('price');

        $lastMonthExpenses = Expense::whereBetween('expense_date', [$prevStartDate, $prevEndDate])
            ->sum('amount');

        $currentProfit = $currentRevenue - $currentExpenses;
        $lastMonthProfit = $lastMonthRevenue - $lastMonthExpenses;

        $profitMargin = $currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0;
        $profitGrowth = $lastMonthProfit > 0 ? (($currentProfit - $lastMonthProfit) / $lastMonthProfit) * 100 : 0;

        // Break-even analysis
        $avgOrderValue = Order::where('status', 'delivered')->avg('price') ?? 0;
        $breakEvenOrders = $avgOrderValue > 0 ? ceil($currentExpenses / $avgOrderValue) : 0;

        return [
            'current_revenue' => $currentRevenue,
            'current_expenses' => $currentExpenses,
            'current_profit' => $currentProfit,
            'profit_margin' => round($profitMargin, 2),
            'profit_growth' => round($profitGrowth, 2),
            'break_even_orders' => $breakEvenOrders,
            'revenue_growth' => $lastMonthRevenue > 0 ? round((($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) : 0,
            'expense_ratio' => $currentRevenue > 0 ? round(($currentExpenses / $currentRevenue) * 100, 2) : 0,
            'status' => $this->getFinancialStatus($profitMargin, $profitGrowth),
        ];
    }

    /**
     * Operational Efficiency Metrics
     */
    private function getOperationalEfficiency(): array
    {
        // Filter orders by date range
        $totalOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        $deliveredOrders = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->count();
        $cancelledOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'cancelled')
            ->count();

        $deliveryRate = $totalOrders > 0 ? ($deliveredOrders / $totalOrders) * 100 : 0;
        $cancellationRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;

        // Average delivery time (orders with both pickup and delivery dates in date range)
        $avgDeliveryTime = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('pickup_date')
            ->whereNotNull('delivery_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, pickup_date, delivery_date)) as avg_hours')
            ->value('avg_hours') ?? 0;

        // Orders per rider
        $activeRiders = Rider::where('status', 'active')->count();
        $ordersPerRider = $activeRiders > 0 ? $deliveredOrders / $activeRiders : 0;

        // Utilization rate
        $ridersWithOrders = Rider::whereHas('orders', function($q) {
            $q->whereIn('status', ['pending', 'confirmed', 'in_transit']);
        })->count();
        $utilizationRate = $activeRiders > 0 ? ($ridersWithOrders / $activeRiders) * 100 : 0;

        return [
            'delivery_rate' => round($deliveryRate, 2),
            'cancellation_rate' => round($cancellationRate, 2),
            'avg_delivery_time_hours' => round($avgDeliveryTime, 1),
            'orders_per_rider' => round($ordersPerRider, 1),
            'rider_utilization_rate' => round($utilizationRate, 2),
            'total_active_riders' => $activeRiders,
            'efficiency_score' => $this->calculateEfficiencyScore($deliveryRate, $cancellationRate, $utilizationRate),
        ];
    }

    /**
     * Customer Insights
     */
    private function getCustomerInsights(): array
    {
        // Unique customers in date range (based on phone number)
        $totalCustomers = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('customer_phone')
            ->count('customer_phone');
        
        // Repeat customers in date range
        $repeatCustomers = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $repeatRate = $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;

        // Average order value in date range
        $avgOrderValue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->avg('price') ?? 0;

        // Customer lifetime value (avg orders per customer * avg order value)
        $totalOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        $avgOrdersPerCustomer = $totalCustomers > 0 ? $totalOrders / $totalCustomers : 0;
        $customerLifetimeValue = $avgOrdersPerCustomer * $avgOrderValue;

        // Top customers in date range
        $topCustomers = Order::select('customer_name', 'customer_phone', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(price) as total_spent'))
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->groupBy('customer_phone', 'customer_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Registered clients
        $registeredClients = Client::count();

        return [
            'total_customers' => $totalCustomers,
            'repeat_customers' => $repeatCustomers,
            'repeat_rate' => round($repeatRate, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'customer_lifetime_value' => round($customerLifetimeValue, 2),
            'registered_clients' => $registeredClients,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Rider Performance Analysis
     */
    private function getRiderPerformance(): array
    {
        // Get top riders with their delivery counts and revenue
        $topRiders = Rider::select('riders.*')
            ->withCount(['orders as delivered_orders' => function($q) {
                $q->where('status', 'delivered');
            }])
            ->leftJoin('orders', function($join) {
                $join->on('riders.id', '=', 'orders.rider_id')
                     ->where('orders.status', '=', 'delivered');
            })
            ->selectRaw('COALESCE(SUM(orders.price), 0) as total_revenue')
            ->where('riders.status', 'active')
            ->groupBy('riders.id', 'riders.name', 'riders.phone', 'riders.email', 'riders.status', 'riders.created_at', 'riders.updated_at')
            ->orderByDesc('delivered_orders')
            ->limit(5)
            ->get();

        $underperformingRiders = Rider::withCount(['orders as delivered_orders' => function($q) {
            $q->where('status', 'delivered')
              ->where('created_at', '>=', now()->subMonth());
        }])
        ->where('status', 'active')
        ->having('delivered_orders', '<', 5)
        ->get();

        // Calculate average deliveries per rider
        $activeRiders = Rider::where('status', 'active')->count();
        $totalDeliveries = Order::where('status', 'delivered')->whereNotNull('rider_id')->count();
        $avgDeliveriesPerRider = $activeRiders > 0 ? round($totalDeliveries / $activeRiders, 1) : 0;

        return [
            'top_performers' => $topRiders,
            'underperforming_riders' => $underperformingRiders,
            'avg_deliveries_per_rider' => $avgDeliveriesPerRider,
        ];
    }

    /**
     * Growth Metrics
     */
    private function getGrowthMetrics(): array
    {
        $months = [];
        $revenue = [];
        $orders = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('M Y');
            
            $monthRevenue = Order::whereMonth('delivery_date', $date->month)
                ->whereYear('delivery_date', $date->year)
                ->where('status', 'delivered')
                ->whereNotNull('delivery_date')
                ->sum('price');

            $monthOrders = Order::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $monthExpenses = Expense::whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');

            $months[] = $month;
            $revenue[] = $monthRevenue;
            $orders[] = $monthOrders;
            $expenses[] = $monthExpenses;
        }

        return [
            'months' => $months,
            'revenue' => $revenue,
            'orders' => $orders,
            'expenses' => $expenses,
            'profit' => array_map(fn($r, $e) => $r - $e, $revenue, $expenses),
        ];
    }

    /**
     * Risk Indicators
     */
    private function getRiskIndicators(): array
    {
        $risks = [];

        // High cancellation rate (in selected date range)
        $totalOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        $cancelledOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'cancelled')
            ->count();
        $cancellationRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;
        
        if ($cancellationRate > 10) {
            $risks[] = [
                'type' => 'high_cancellation',
                'severity' => 'high',
                'message' => "Cancellation rate is " . round($cancellationRate, 1) . "% (above 10% threshold)",
                'recommendation' => 'Investigate reasons for cancellations and improve customer communication',
            ];
        }

        // Low profit margin (in selected date range)
        $revenue = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->sum('price');
        $expenses = Expense::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');
        $profitMargin = $revenue > 0 ? (($revenue - $expenses) / $revenue) * 100 : 0;

        if ($profitMargin < 20 && $revenue > 0) {
            $risks[] = [
                'type' => 'low_profit_margin',
                'severity' => $profitMargin < 10 ? 'critical' : 'medium',
                'message' => "Profit margin is " . round($profitMargin, 1) . "% (below 20% healthy threshold)",
                'recommendation' => 'Review pricing strategy and reduce operational costs',
            ];
        }

        // Inactive riders (current status, not date-dependent)
        $inactiveRiders = Rider::where('status', 'inactive')->count();
        $totalRiders = Rider::count();
        if ($totalRiders > 0 && ($inactiveRiders / $totalRiders) > 0.3) {
            $risks[] = [
                'type' => 'high_rider_inactivity',
                'severity' => 'medium',
                'message' => "{$inactiveRiders} riders are inactive (" . round(($inactiveRiders / $totalRiders) * 100, 1) . "%)",
                'recommendation' => 'Engage with inactive riders or recruit new ones',
            ];
        }

        // Expense growth (compare to previous period)
        $periodDays = $this->startDate->diffInDays($this->endDate);
        $prevStartDate = $this->startDate->copy()->subDays($periodDays + 1);
        $prevEndDate = $this->startDate->copy()->subDay();
        
        $previousExpenses = Expense::whereBetween('expense_date', [$prevStartDate, $prevEndDate])
            ->sum('amount');
        
        if ($previousExpenses > 0 && $expenses > $previousExpenses * 1.2) {
            $increasePercent = round((($expenses - $previousExpenses) / $previousExpenses) * 100, 1);
            $risks[] = [
                'type' => 'expense_spike',
                'severity' => 'medium',
                'message' => "Expenses increased by {$increasePercent}% compared to previous period",
                'recommendation' => 'Review and categorize expenses to identify cost drivers',
            ];
        }

        // Low delivery rate
        $deliveredOrders = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->count();
        $deliveryRate = $totalOrders > 0 ? ($deliveredOrders / $totalOrders) * 100 : 0;
        
        if ($deliveryRate < 85 && $totalOrders > 0) {
            $risks[] = [
                'type' => 'low_delivery_rate',
                'severity' => $deliveryRate < 70 ? 'critical' : 'high',
                'message' => "Delivery success rate is " . round($deliveryRate, 1) . "% (below 85% threshold)",
                'recommendation' => 'Improve rider training and optimize delivery routes',
            ];
        }

        // Revenue decline
        $previousRevenue = Order::whereBetween('delivery_date', [$prevStartDate, $prevEndDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivery_date')
            ->sum('price');
        
        if ($previousRevenue > 0 && $revenue < $previousRevenue * 0.85) {
            $declinePercent = round((($previousRevenue - $revenue) / $previousRevenue) * 100, 1);
            $risks[] = [
                'type' => 'revenue_decline',
                'severity' => $revenue < $previousRevenue * 0.7 ? 'critical' : 'high',
                'message' => "Revenue declined by {$declinePercent}% compared to previous period",
                'recommendation' => 'Analyze market conditions, increase marketing efforts, and review pricing strategy',
            ];
        }

        // Low order volume
        if ($totalOrders < 10 && $totalOrders > 0) {
            $risks[] = [
                'type' => 'low_order_volume',
                'severity' => 'high',
                'message' => "Only {$totalOrders} orders in selected period - critically low volume",
                'recommendation' => 'Urgently increase marketing, offer promotions, and reach out to existing customers',
            ];
        }

        // No orders (business inactivity)
        if ($totalOrders == 0) {
            $risks[] = [
                'type' => 'no_orders',
                'severity' => 'critical',
                'message' => "No orders recorded in selected period - business appears inactive",
                'recommendation' => 'Immediate action required: verify operations, launch marketing campaigns, contact previous customers',
            ];
        }

        // High expense-to-revenue ratio
        if ($revenue > 0 && ($expenses / $revenue) > 0.8) {
            $expenseRatio = round(($expenses / $revenue) * 100, 1);
            $risks[] = [
                'type' => 'high_expense_ratio',
                'severity' => $expenseRatio > 90 ? 'critical' : 'high',
                'message' => "Expenses are {$expenseRatio}% of revenue - unsustainable cost structure",
                'recommendation' => 'Conduct thorough expense audit, negotiate with suppliers, and eliminate non-essential costs',
            ];
        }

        // Negative profit
        $profit = $revenue - $expenses;
        if ($profit < 0 && $revenue > 0) {
            $loss = abs($profit);
            $risks[] = [
                'type' => 'operating_loss',
                'severity' => 'critical',
                'message' => "Operating at a loss of ₦" . number_format($loss, 2) . " in selected period",
                'recommendation' => 'Critical: Reduce expenses immediately, increase prices, or scale down operations',
            ];
        }

        // Few or no repeat customers
        $repeatCustomers = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $uniqueCustomers = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('customer_phone')
            ->count('customer_phone');
        $repeatRate = $uniqueCustomers > 0 ? ($repeatCustomers / $uniqueCustomers) * 100 : 0;
        
        if ($repeatRate < 15 && $uniqueCustomers >= 10) {
            $risks[] = [
                'type' => 'low_customer_retention',
                'severity' => 'high',
                'message' => "Only " . round($repeatRate, 1) . "% of customers are repeat customers - poor retention",
                'recommendation' => 'Implement loyalty program, improve service quality, and follow up with customers',
            ];
        }

        // Slow delivery times
        $avgDeliveryHours = Order::whereBetween('delivery_date', [$this->startDate, $this->endDate])
            ->where('status', 'delivered')
            ->whereNotNull('pickup_date')
            ->whereNotNull('delivery_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, pickup_date, delivery_date)) as avg_hours')
            ->value('avg_hours') ?? 0;
        
        if ($avgDeliveryHours > 48) {
            $risks[] = [
                'type' => 'slow_delivery',
                'severity' => $avgDeliveryHours > 72 ? 'high' : 'medium',
                'message' => "Average delivery time is " . round($avgDeliveryHours, 1) . " hours - too slow for customer satisfaction",
                'recommendation' => 'Optimize routes, add more riders, and improve dispatch efficiency',
            ];
        }

        // Insufficient riders for demand
        $activeRiders = Rider::where('status', 'active')->count();
        $ordersPerRider = $activeRiders > 0 ? $totalOrders / $activeRiders : 0;
        
        if ($ordersPerRider > 20 && $totalOrders > 0) {
            $risks[] = [
                'type' => 'rider_shortage',
                'severity' => $ordersPerRider > 30 ? 'high' : 'medium',
                'message' => "Riders are overloaded with " . round($ordersPerRider, 1) . " orders each - risk of burnout and delays",
                'recommendation' => 'Recruit additional riders urgently to maintain service quality',
            ];
        }

        // No active riders
        if ($activeRiders == 0) {
            $risks[] = [
                'type' => 'no_riders',
                'severity' => 'critical',
                'message' => "No active riders available - cannot fulfill orders",
                'recommendation' => 'Critical: Activate existing riders or recruit new ones immediately',
            ];
        }

        // Cash flow warning (expenses without revenue)
        if ($expenses > 0 && $revenue == 0) {
            $risks[] = [
                'type' => 'cash_flow_crisis',
                'severity' => 'critical',
                'message' => "Incurring expenses (₦" . number_format($expenses, 2) . ") with zero revenue - cash flow crisis",
                'recommendation' => 'Stop non-essential spending, focus on revenue generation, consider emergency funding',
            ];
        }

        // Pending orders piling up
        $pendingOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'pending')
            ->count();
        
        if ($pendingOrders > $deliveredOrders && $pendingOrders > 5) {
            $risks[] = [
                'type' => 'pending_backlog',
                'severity' => 'high',
                'message' => "{$pendingOrders} pending orders - backlog building up",
                'recommendation' => 'Assign riders immediately, clear backlog, and improve order processing speed',
            ];
        }

        // Single customer dependency
        if ($uniqueCustomers > 0) {
            $topCustomerOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
                ->select('customer_phone', DB::raw('COUNT(*) as order_count'))
                ->groupBy('customer_phone')
                ->orderByDesc('order_count')
                ->first();
            
            if ($topCustomerOrders && $totalOrders > 0) {
                $dependencyRate = ($topCustomerOrders->order_count / $totalOrders) * 100;
                if ($dependencyRate > 40) {
                    $risks[] = [
                        'type' => 'customer_concentration',
                        'severity' => 'medium',
                        'message' => "One customer accounts for " . round($dependencyRate, 1) . "% of orders - high dependency risk",
                        'recommendation' => 'Diversify customer base to reduce business risk from losing a single client',
                    ];
                }
            }
        }

        return $risks;
    }

    /**
     * AI-Powered Recommendations
     */
    private function getAIRecommendations(): array
    {
        $analytics = [
            'financial' => $this->getFinancialHealth(),
            'operational' => $this->getOperationalEfficiency(),
            'customer' => $this->getCustomerInsights(),
        ];

        $recommendations = [];

        // Revenue optimization
        if ($analytics['financial']['profit_margin'] < 25) {
            $recommendations[] = [
                'category' => 'Revenue Optimization',
                'priority' => 'high',
                'title' => 'Increase Profit Margins',
                'description' => 'Your profit margin is ' . $analytics['financial']['profit_margin'] . '%. Consider increasing prices by 10-15% or reducing operational costs.',
                'actions' => [
                    'Review and adjust pricing for different delivery zones',
                    'Negotiate better rates with fuel suppliers',
                    'Optimize routes to reduce fuel consumption',
                ],
                'potential_impact' => 'Could increase monthly profit by ₦' . number_format($analytics['financial']['current_revenue'] * 0.1, 2),
            ];
        }

        // Customer retention
        if ($analytics['customer']['repeat_rate'] < 40) {
            $recommendations[] = [
                'category' => 'Customer Retention',
                'priority' => 'high',
                'title' => 'Improve Customer Retention',
                'description' => 'Only ' . $analytics['customer']['repeat_rate'] . '% of customers are repeat customers. Implement loyalty programs.',
                'actions' => [
                    'Launch a loyalty program with discounts for repeat customers',
                    'Send follow-up messages after deliveries',
                    'Offer referral bonuses',
                ],
                'potential_impact' => 'Increasing repeat rate to 50% could add ₦' . number_format($analytics['financial']['current_revenue'] * 0.25, 2) . ' monthly',
            ];
        }

        // Operational efficiency
        if ($analytics['operational']['delivery_rate'] < 90) {
            $recommendations[] = [
                'category' => 'Operational Excellence',
                'priority' => 'medium',
                'title' => 'Improve Delivery Success Rate',
                'description' => 'Current delivery rate is ' . $analytics['operational']['delivery_rate'] . '%. Target should be above 95%.',
                'actions' => [
                    'Provide better training for riders',
                    'Implement real-time tracking for all deliveries',
                    'Improve communication with customers',
                ],
                'potential_impact' => 'Reducing failed deliveries could save ₦' . number_format($analytics['financial']['current_expenses'] * 0.05, 2) . ' monthly',
            ];
        }

        // Rider utilization
        if ($analytics['operational']['rider_utilization_rate'] < 70) {
            $recommendations[] = [
                'category' => 'Resource Optimization',
                'priority' => 'medium',
                'title' => 'Optimize Rider Utilization',
                'description' => 'Rider utilization is at ' . $analytics['operational']['rider_utilization_rate'] . '%. You may have excess capacity.',
                'actions' => [
                    'Increase marketing to get more orders',
                    'Consider reducing rider count temporarily',
                    'Expand service areas to utilize idle riders',
                ],
                'potential_impact' => 'Better utilization could reduce costs by ₦' . number_format($analytics['financial']['current_expenses'] * 0.15, 2) . ' monthly',
            ];
        }

        // Cancellation rate
        if ($analytics['operational']['cancellation_rate'] > 5) {
            $recommendations[] = [
                'category' => 'Customer Experience',
                'priority' => 'high',
                'title' => 'Reduce Order Cancellations',
                'description' => 'Cancellation rate is ' . $analytics['operational']['cancellation_rate'] . '%. High cancellations hurt revenue and reputation.',
                'actions' => [
                    'Improve order confirmation process',
                    'Send SMS reminders before pickup',
                    'Offer flexible rescheduling options',
                    'Investigate common cancellation reasons',
                ],
                'potential_impact' => 'Reducing cancellations by 50% could save ₦' . number_format($analytics['financial']['current_revenue'] * 0.05, 2) . ' monthly',
            ];
        }

        // Expense management
        if ($analytics['financial']['expense_ratio'] > 70) {
            $recommendations[] = [
                'category' => 'Cost Management',
                'priority' => 'high',
                'title' => 'Control Operating Expenses',
                'description' => 'Expenses are ' . $analytics['financial']['expense_ratio'] . '% of revenue. This is too high for sustainable growth.',
                'actions' => [
                    'Audit all recurring expenses monthly',
                    'Negotiate bulk discounts with suppliers',
                    'Implement fuel-efficient driving practices',
                    'Review and eliminate unnecessary subscriptions',
                ],
                'potential_impact' => 'Reducing expenses by 15% could save ₦' . number_format($analytics['financial']['current_expenses'] * 0.15, 2) . ' monthly',
            ];
        }

        // Customer lifetime value
        if ($analytics['customer']['customer_lifetime_value'] < $analytics['customer']['avg_order_value'] * 3) {
            $recommendations[] = [
                'category' => 'Customer Value',
                'priority' => 'medium',
                'title' => 'Increase Customer Lifetime Value',
                'description' => 'Average customer only orders ' . round($analytics['customer']['customer_lifetime_value'] / max($analytics['customer']['avg_order_value'], 1), 1) . ' times. Encourage repeat business.',
                'actions' => [
                    'Create subscription packages for regular customers',
                    'Offer volume discounts for multiple orders',
                    'Send personalized offers based on order history',
                    'Implement a points-based rewards system',
                ],
                'potential_impact' => 'Doubling customer lifetime value could add ₦' . number_format($analytics['customer']['total_customers'] * $analytics['customer']['avg_order_value'], 2) . ' in revenue',
            ];
        }

        // Peak time optimization
        $recommendations[] = [
            'category' => 'Operations',
            'priority' => 'medium',
            'title' => 'Optimize Peak Hours',
            'description' => 'Maximize efficiency during high-demand periods.',
            'actions' => [
                'Analyze order patterns to identify peak hours',
                'Schedule more riders during busy periods',
                'Offer off-peak discounts to balance demand',
                'Pre-position riders in high-demand areas',
            ],
            'potential_impact' => 'Better scheduling could increase daily capacity by 20%',
        ];

        // Technology adoption
        $recommendations[] = [
            'category' => 'Technology',
            'priority' => 'medium',
            'title' => 'Leverage Technology',
            'description' => 'Automate processes to reduce costs and improve service.',
            'actions' => [
                'Implement automated SMS notifications',
                'Use GPS tracking for all deliveries',
                'Enable online payment options',
                'Create a customer mobile app',
            ],
            'potential_impact' => 'Automation could reduce operational costs by 10-15%',
        ];

        // Rider performance
        if ($analytics['operational']['orders_per_rider'] < 10) {
            $recommendations[] = [
                'category' => 'Team Performance',
                'priority' => 'medium',
                'title' => 'Boost Rider Productivity',
                'description' => 'Average rider completes ' . round($analytics['operational']['orders_per_rider'], 1) . ' orders. This can be improved.',
                'actions' => [
                    'Set daily delivery targets with incentives',
                    'Provide route optimization training',
                    'Reward top performers monthly',
                    'Implement performance dashboards for riders',
                ],
                'potential_impact' => 'Increasing productivity by 30% could handle ₦' . number_format($analytics['financial']['current_revenue'] * 0.3, 2) . ' more revenue',
            ];
        }

        // Pricing strategy
        $recommendations[] = [
            'category' => 'Pricing',
            'priority' => 'low',
            'title' => 'Dynamic Pricing Strategy',
            'description' => 'Implement smart pricing to maximize revenue.',
            'actions' => [
                'Charge premium rates during peak hours',
                'Offer discounts for advance bookings',
                'Create tiered pricing based on distance',
                'Add surcharges for urgent deliveries',
            ],
            'potential_impact' => 'Smart pricing could increase revenue by 8-12%',
        ];

        // Customer feedback
        $recommendations[] = [
            'category' => 'Quality',
            'priority' => 'low',
            'title' => 'Collect Customer Feedback',
            'description' => 'Use customer insights to improve service quality.',
            'actions' => [
                'Send post-delivery satisfaction surveys',
                'Monitor and respond to online reviews',
                'Create a customer feedback portal',
                'Act on common complaints quickly',
            ],
            'potential_impact' => 'Better service quality increases retention by 15-20%',
        ];

        // Marketing and branding
        $recommendations[] = [
            'category' => 'Marketing',
            'priority' => 'medium',
            'title' => 'Strengthen Brand Presence',
            'description' => 'Build brand awareness to attract more customers.',
            'actions' => [
                'Create engaging social media content',
                'Run targeted Facebook/Instagram ads',
                'Partner with influencers for promotions',
                'Sponsor local community events',
            ],
            'potential_impact' => 'Strong marketing could increase orders by 25-40%',
        ];

        // Business partnerships
        $recommendations[] = [
            'category' => 'Partnerships',
            'priority' => 'medium',
            'title' => 'Develop Strategic Partnerships',
            'description' => 'Partner with businesses for steady revenue streams.',
            'actions' => [
                'Approach e-commerce stores for delivery contracts',
                'Partner with restaurants for food delivery',
                'Offer corporate delivery packages',
                'Join delivery aggregator platforms',
            ],
            'potential_impact' => 'B2B partnerships could add ₦' . number_format($analytics['financial']['current_revenue'] * 0.5, 2) . ' monthly',
        ];

        // Data analytics
        $recommendations[] = [
            'category' => 'Analytics',
            'priority' => 'low',
            'title' => 'Use Data for Decisions',
            'description' => 'Make data-driven decisions to optimize operations.',
            'actions' => [
                'Track key metrics daily (this dashboard!)',
                'Identify profitable vs unprofitable routes',
                'Analyze customer behavior patterns',
                'Forecast demand for better planning',
            ],
            'potential_impact' => 'Data-driven decisions improve efficiency by 15-25%',
        ];

        // Seasonal planning
        $recommendations[] = [
            'category' => 'Planning',
            'priority' => 'low',
            'title' => 'Prepare for Seasonal Peaks',
            'description' => 'Plan ahead for high-demand periods.',
            'actions' => [
                'Hire temporary riders for festive seasons',
                'Stock up on supplies before peak periods',
                'Launch seasonal promotional campaigns',
                'Adjust pricing for high-demand periods',
            ],
            'potential_impact' => 'Seasonal planning prevents lost revenue opportunities',
        ];

        // Customer service
        $recommendations[] = [
            'category' => 'Service',
            'priority' => 'medium',
            'title' => 'Enhance Customer Support',
            'description' => 'Excellent support builds customer loyalty.',
            'actions' => [
                'Set up WhatsApp Business for quick responses',
                'Create FAQ section on website',
                'Train staff on customer service best practices',
                'Respond to inquiries within 30 minutes',
            ],
            'potential_impact' => 'Better support increases customer satisfaction by 30%',
        ];

        // Fleet management
        if ($analytics['operational']['total_active_riders'] > 5) {
            $recommendations[] = [
                'category' => 'Fleet',
                'priority' => 'low',
                'title' => 'Optimize Fleet Management',
                'description' => 'Maintain vehicles properly to reduce downtime.',
                'actions' => [
                    'Schedule regular vehicle maintenance',
                    'Track fuel consumption per rider',
                    'Implement vehicle inspection checklists',
                    'Consider leasing vs buying vehicles',
                ],
                'potential_impact' => 'Proper maintenance reduces breakdown costs by 20%',
            ];
        }

        // Competitive advantage
        $recommendations[] = [
            'category' => 'Competitive Edge',
            'priority' => 'medium',
            'title' => 'Build Competitive Advantages',
            'description' => 'Differentiate your service from competitors.',
            'actions' => [
                'Offer same-day delivery guarantee',
                'Provide real-time order tracking',
                'Accept multiple payment methods',
                'Create a unique value proposition',
                'Specialize in specific delivery niches',
            ],
            'potential_impact' => 'Strong differentiation attracts 20-30% more customers',
        ];

        return $recommendations;
    }

    /**
     * Helper: Calculate financial status
     */
    private function getFinancialStatus(float $profitMargin, float $profitGrowth): string
    {
        if ($profitMargin >= 30 && $profitGrowth > 10) return 'excellent';
        if ($profitMargin >= 20 && $profitGrowth > 0) return 'good';
        if ($profitMargin >= 10) return 'fair';
        if ($profitMargin > 0) return 'poor';
        return 'critical';
    }

    /**
     * Helper: Calculate efficiency score
     */
    private function calculateEfficiencyScore(float $deliveryRate, float $cancellationRate, float $utilizationRate): float
    {
        $score = ($deliveryRate * 0.4) + ((100 - $cancellationRate) * 0.3) + ($utilizationRate * 0.3);
        return round($score, 1);
    }
}
