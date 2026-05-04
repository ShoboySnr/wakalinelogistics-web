@extends('Admin::layout')

@section('title', 'Business Intelligence & Analytics')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Business Intelligence & Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">AI-powered insights and recommendations to grow your business</p>
            <p class="text-xs text-gray-400 mt-1">
                Showing data from {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            </p>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="mb-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('admin.business-intelligence') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <select name="date_range" id="dateRangeSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-[#C1666B] focus:border-[#C1666B]" onchange="toggleCustomDates()">
                    <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                    <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ $dateRange == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <div id="customDates" class="flex gap-3 {{ $dateRange == 'custom' ? '' : 'hidden' }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-[#C1666B] focus:border-[#C1666B]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-[#C1666B] focus:border-[#C1666B]">
                </div>
            </div>

            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors whitespace-nowrap">
                Apply Filter
            </button>
            @if($dateRange != 'this_month')
            <a href="{{ route('admin.business-intelligence') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 whitespace-nowrap">
                Reset
            </a>
            @endif
        </form>
    </div>

    <script>
    function toggleCustomDates() {
        const select = document.getElementById('dateRangeSelect');
        const customDates = document.getElementById('customDates');
        if (select.value === 'custom') {
            customDates.classList.remove('hidden');
        } else {
            customDates.classList.add('hidden');
        }
    }
    </script>

    <!-- Financial Health Overview -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Financial Health</h2>
            <a href="{{ route('admin.expenses') }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                View Expenses
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-5 border-l-4 
                @if(($analytics['financial_health']['status'] ?? '') == 'excellent') border-green-500
                @elseif(($analytics['financial_health']['status'] ?? '') == 'good') border-blue-500
                @elseif(($analytics['financial_health']['status'] ?? '') == 'fair') border-yellow-500
                @else border-red-500
                @endif">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Profit Margin</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $analytics['financial_health']['profit_margin'] ?? 0 }}%</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Status: <span class="font-semibold capitalize">{{ $analytics['financial_health']['status'] ?? 'N/A' }}</span>
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Current Profit</p>
                        <p class="text-2xl font-bold {{ ($analytics['financial_health']['current_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            ₦{{ number_format(abs($analytics['financial_health']['current_profit'] ?? 0), 2) }}
                        </p>
                        <p class="text-xs {{ ($analytics['financial_health']['profit_growth'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            {{ ($analytics['financial_health']['profit_growth'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($analytics['financial_health']['profit_growth'] ?? 0) }}% vs last month
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Revenue Growth</p>
                        <p class="text-2xl font-bold {{ ($analytics['financial_health']['revenue_growth'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                            {{ ($analytics['financial_health']['revenue_growth'] ?? 0) >= 0 ? '+' : '' }}{{ $analytics['financial_health']['revenue_growth'] ?? 0 }}%
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Month over month</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Break-Even Orders</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $analytics['financial_health']['break_even_orders'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Orders needed to break even</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Trends Chart -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">6-Month Performance Trends</h2>
            <canvas id="growthChart" height="80"></canvas>
        </div>
    </div>

    <!-- Operational Efficiency & Customer Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Operational Efficiency -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Operational Efficiency</h2>
                <a href="{{ route('admin.orders') }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    View All Orders
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Delivery Success Rate</span>
                    <span class="text-lg font-bold text-gray-900">{{ $analytics['operational_efficiency']['delivery_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Cancellation Rate</span>
                    <span class="text-lg font-bold {{ ($analytics['operational_efficiency']['cancellation_rate'] ?? 0) > 10 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $analytics['operational_efficiency']['cancellation_rate'] ?? 0 }}%
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Avg Delivery Time</span>
                    <span class="text-lg font-bold text-gray-900">{{ $analytics['operational_efficiency']['avg_delivery_time_hours'] ?? 0 }}h</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Rider Utilization</span>
                    <span class="text-lg font-bold text-gray-900">{{ $analytics['operational_efficiency']['rider_utilization_rate'] ?? 0 }}%</span>
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm font-medium text-blue-900">Efficiency Score</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1">{{ $analytics['operational_efficiency']['efficiency_score'] ?? 0 }}/100</p>
                </div>
            </div>
        </div>

        <!-- Customer Insights -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Customer Insights</h2>
                <a href="{{ route('admin.clients') }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    View All Clients
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Total Customers</span>
                    <span class="text-lg font-bold text-gray-900">{{ number_format($analytics['customer_insights']['total_customers'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Repeat Customer Rate</span>
                    <span class="text-lg font-bold text-gray-900">{{ $analytics['customer_insights']['repeat_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Avg Order Value</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($analytics['customer_insights']['avg_order_value'] ?? 0, 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Customer Lifetime Value</span>
                    <span class="text-lg font-bold text-gray-900">₦{{ number_format($analytics['customer_insights']['customer_lifetime_value'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Indicators -->
    @if(count($analytics['risk_indicators']) > 0)
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Risk Indicators</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($analytics['risk_indicators'] as $risk)
            <div class="bg-white rounded-lg shadow p-4 border-l-4 flex flex-col h-full
                @if($risk['severity'] == 'critical') border-red-600
                @elseif($risk['severity'] == 'high') border-orange-500
                @else border-yellow-500
                @endif">
                <div class="flex items-start mb-2">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 
                            @if($risk['severity'] == 'critical') text-red-600
                            @elseif($risk['severity'] == 'high') text-orange-500
                            @else text-yellow-500
                            @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($risk['severity'] == 'critical') bg-red-100 text-red-800
                            @elseif($risk['severity'] == 'high') bg-orange-100 text-orange-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ ucfirst($risk['severity']) }}
                        </span>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 mb-2">{{ $risk['message'] }}</p>
                    <p class="text-xs text-gray-600">💡 {{ $risk['recommendation'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- AI Recommendations -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">🤖 AI-Powered Recommendations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($analytics['ai_recommendations'] as $recommendation)
            <div class="bg-white rounded-lg shadow p-5 flex flex-col h-full">
                <div class="flex items-center mb-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                        @if($recommendation['priority'] == 'high') bg-red-100 text-red-800
                        @elseif($recommendation['priority'] == 'medium') bg-yellow-100 text-yellow-800
                        @else bg-blue-100 text-blue-800
                        @endif">
                        {{ ucfirst($recommendation['priority']) }}
                    </span>
                    <span class="ml-2 text-xs text-gray-500">{{ $recommendation['category'] }}</span>
                </div>
                
                <h3 class="text-base font-semibold text-gray-900 mb-2">{{ $recommendation['title'] }}</h3>
                <p class="text-sm text-gray-600 mb-3">{{ $recommendation['description'] }}</p>
                
                <div class="mb-3 flex-grow">
                    <p class="text-xs font-medium text-gray-700 mb-2">Actions:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($recommendation['actions'] as $action)
                        <li class="text-xs text-gray-600">{{ $action }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-auto p-2 bg-green-50 rounded-lg">
                    <p class="text-xs font-medium text-green-900">💰 Impact:</p>
                    <p class="text-xs text-green-700">{{ $recommendation['potential_impact'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Top Customers -->
    @if(count($analytics['customer_insights']['top_customers']) > 0)
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Top 5 Customers</h2>
                <a href="{{ route('admin.orders') }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    View All Orders
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($analytics['customer_insights']['top_customers'] as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $customer->customer_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.orders') }}?search={{ $customer->customer_phone }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $customer->customer_phone }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.orders') }}?search={{ $customer->customer_phone }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $customer->order_count }} orders
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₦{{ number_format($customer->total_spent, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- ADVANCED ANALYTICS SECTION -->
    <div class="mt-8 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">🔬 Advanced Analytics & Deep Insights</h1>
        <p class="text-sm text-gray-500">Comprehensive data-driven analysis for strategic decision making</p>
    </div>

    <!-- Bike Investment Analysis -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg shadow-lg p-6 border-2 border-green-200">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🏍️ Bike Investment Analysis</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-600">Current Fleet</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $advancedAnalytics['bike_investment_analysis']['current_riders'] }} Bikes</p>
                    <p class="text-xs text-gray-500 mt-1">{{ round($advancedAnalytics['bike_investment_analysis']['current_orders_per_rider'], 1) }} orders/bike</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-600">Investment Required</p>
                    <p class="text-3xl font-bold text-green-600">₦{{ number_format($advancedAnalytics['bike_investment_analysis']['bike_investment']['bike_cost']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Per bike + ₦{{ number_format($advancedAnalytics['bike_investment_analysis']['bike_investment']['monthly_operating_cost']) }}/month</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow">
                    <p class="text-sm text-gray-600">First Year ROI</p>
                    <p class="text-3xl font-bold {{ $advancedAnalytics['bike_investment_analysis']['bike_investment']['first_year_roi'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $advancedAnalytics['bike_investment_analysis']['bike_investment']['first_year_roi'] }}%
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Break-even: {{ $advancedAnalytics['bike_investment_analysis']['bike_investment']['months_to_recover_investment'] }} months</p>
                </div>
            </div>

            <div class="bg-white rounded-lg p-5 mb-4">
                <h3 class="font-semibold text-gray-900 mb-3">💡 Investment Recommendation:</h3>
                <p class="text-sm text-gray-700 mb-4">{{ $advancedAnalytics['bike_investment_analysis']['recommendation'] }}</p>
                
                <h4 class="font-semibold text-gray-900 mb-2 text-sm">Growth Scenarios:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($advancedAnalytics['bike_investment_analysis']['scenarios'] as $scenario => $data)
                    <div class="border rounded p-3 {{ $scenario == 'moderate' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                        <p class="text-xs font-semibold text-gray-700 uppercase">{{ $scenario }}</p>
                        <p class="text-sm mt-1"><strong>+{{ $data['additional_bikes'] }} bikes</strong></p>
                        <p class="text-xs text-gray-600">Revenue: ₦{{ number_format($data['expected_monthly_revenue']) }}/mo</p>
                        <p class="text-xs {{ $data['expected_monthly_profit'] > 0 ? 'text-green-600' : 'text-red-600' }}">Profit: ₦{{ number_format($data['expected_monthly_profit']) }}/mo</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Capacity & Forecasting -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Capacity Analysis -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📊 Capacity Analysis</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-600">Theoretical Capacity</span>
                    <span class="font-bold">{{ $advancedAnalytics['capacity_analysis']['theoretical_capacity'] }} orders</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-sm text-gray-600">Actual Orders</span>
                    <span class="font-bold">{{ $advancedAnalytics['capacity_analysis']['actual_orders'] }} orders</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                    <span class="text-sm text-gray-600">Utilization Rate</span>
                    <span class="font-bold text-blue-600">{{ $advancedAnalytics['capacity_analysis']['utilization_rate'] }}%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                    <span class="text-sm text-gray-600">Unused Capacity</span>
                    <span class="font-bold text-green-600">{{ $advancedAnalytics['capacity_analysis']['unutilized_capacity'] }} orders</span>
                </div>
                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm font-semibold text-yellow-900">Status: {{ $advancedAnalytics['capacity_analysis']['capacity_status'] }}</p>
                    <p class="text-xs text-yellow-700 mt-1">{{ $advancedAnalytics['capacity_analysis']['recommendation'] }}</p>
                </div>
            </div>
        </div>

        <!-- Revenue Forecast -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📈 Revenue Forecast (Next 3 Months)</h2>
            <div class="mb-3">
                <p class="text-sm text-gray-600">Growth Trend: <span class="font-semibold capitalize {{ $advancedAnalytics['revenue_forecasting']['trend'] == 'growing' ? 'text-green-600' : ($advancedAnalytics['revenue_forecasting']['trend'] == 'declining' ? 'text-red-600' : 'text-gray-600') }}">{{ $advancedAnalytics['revenue_forecasting']['trend'] }}</span></p>
                <p class="text-sm text-gray-600">Avg Growth Rate: <span class="font-semibold">{{ $advancedAnalytics['revenue_forecasting']['average_growth_rate'] }}%</span></p>
            </div>
            <div class="space-y-2">
                @foreach($advancedAnalytics['revenue_forecasting']['forecast'] as $forecast)
                <div class="p-3 bg-gray-50 rounded">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">{{ $forecast['month'] }}</span>
                        <span class="font-bold text-green-600">₦{{ number_format($forecast['forecasted_revenue'], 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Confidence: {{ $forecast['confidence'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Customer Segmentation -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">👥 Customer Segmentation Analysis</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">{{ $advancedAnalytics['customer_segmentation']['segments']['vip_customers'] }}</p>
                    <p class="text-xs text-gray-600">VIP Customers</p>
                    <p class="text-xs text-purple-600">10+ orders, ₦100k+</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ $advancedAnalytics['customer_segmentation']['segments']['regular_customers'] }}</p>
                    <p class="text-xs text-gray-600">Regular</p>
                    <p class="text-xs text-blue-600">5-9 orders</p>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-600">{{ $advancedAnalytics['customer_segmentation']['segments']['occasional_customers'] }}</p>
                    <p class="text-xs text-gray-600">Occasional</p>
                    <p class="text-xs text-yellow-600">2-4 orders</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-2xl font-bold text-gray-600">{{ $advancedAnalytics['customer_segmentation']['segments']['one_time_customers'] }}</p>
                    <p class="text-xs text-gray-600">One-Time</p>
                    <p class="text-xs text-gray-600">1 order only</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm font-semibold text-green-900">VIP Revenue Contribution</p>
                    <p class="text-3xl font-bold text-green-600">{{ $advancedAnalytics['customer_segmentation']['vip_revenue_contribution'] }}%</p>
                </div>
                <div class="p-4 bg-red-50 rounded-lg">
                    <p class="text-sm font-semibold text-red-900">Churn Risk</p>
                    <p class="text-2xl font-bold text-red-600">{{ $advancedAnalytics['customer_segmentation']['churn_risk']['count'] }} customers</p>
                    <p class="text-xs text-red-600">Potential loss: ₦{{ number_format($advancedAnalytics['customer_segmentation']['churn_risk']['potential_lost_revenue']) }}/mo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Breakdown & Cash Flow -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Expense Breakdown -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">💰 Detailed Expense Breakdown</h2>
            <p class="text-sm text-gray-600 mb-3">Total: ₦{{ number_format($advancedAnalytics['expense_breakdown']['total_expenses']) }}</p>
            <div class="space-y-2">
                @foreach($advancedAnalytics['expense_breakdown']['breakdown'] as $expense)
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">{{ ucfirst($expense['category']) }}</span>
                            <span class="text-sm font-bold">{{ $expense['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $expense['percentage'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">₦{{ number_format($expense['amount']) }} ({{ $expense['count'] }} transactions)</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Cash Flow Projection -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">💵 Cash Flow Projection</h2>
            <div class="mb-4 p-4 {{ $advancedAnalytics['cash_flow_projection']['current_monthly_cash_flow'] > 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg">
                <p class="text-sm text-gray-600">Current Monthly Cash Flow</p>
                <p class="text-2xl font-bold {{ $advancedAnalytics['cash_flow_projection']['current_monthly_cash_flow'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    ₦{{ number_format($advancedAnalytics['cash_flow_projection']['current_monthly_cash_flow']) }}
                </p>
                <p class="text-xs text-gray-600 mt-1">Runway: {{ $advancedAnalytics['cash_flow_projection']['cash_runway'] }}</p>
            </div>
            <div class="space-y-2">
                @foreach(array_slice($advancedAnalytics['cash_flow_projection']['projections'], 0, 3) as $projection)
                <div class="p-2 bg-gray-50 rounded text-xs">
                    <div class="flex justify-between">
                        <span class="font-medium">{{ $projection['month'] }}</span>
                        <span class="font-bold {{ $projection['projected_profit'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            ₦{{ number_format($projection['projected_profit']) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Market Opportunity & Competitive Position -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Market Opportunity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">🎯 Market Opportunity</h2>
            <div class="space-y-3">
                <div class="p-3 bg-blue-50 rounded">
                    <p class="text-sm font-semibold text-blue-900">Addressable Market</p>
                    <p class="text-xs text-blue-700">{{ $advancedAnalytics['market_opportunity']['addressable_market_size'] }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold mb-2">Growth Drivers:</p>
                    <ul class="space-y-1">
                        @foreach($advancedAnalytics['market_opportunity']['growth_potential'] as $driver => $description)
                        <li class="text-xs text-gray-600">✓ {{ $description }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold mb-2">Untapped Segments:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($advancedAnalytics['market_opportunity']['untapped_segments'] as $segment)
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">{{ $segment }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- SWOT Analysis -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">⚖️ Competitive Position (SWOT)</h2>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 bg-green-50 rounded">
                    <p class="font-semibold text-green-900 mb-2">Strengths</p>
                    @foreach($advancedAnalytics['competitive_positioning']['strengths'] as $strength)
                    <p class="text-green-700">✓ {{ $strength }}</p>
                    @endforeach
                </div>
                <div class="p-3 bg-red-50 rounded">
                    <p class="font-semibold text-red-900 mb-2">Weaknesses</p>
                    @foreach($advancedAnalytics['competitive_positioning']['weaknesses'] as $weakness)
                    <p class="text-red-700">✗ {{ $weakness }}</p>
                    @endforeach
                </div>
                <div class="p-3 bg-blue-50 rounded">
                    <p class="font-semibold text-blue-900 mb-2">Opportunities</p>
                    @foreach($advancedAnalytics['competitive_positioning']['opportunities'] as $opportunity)
                    <p class="text-blue-700">→ {{ $opportunity }}</p>
                    @endforeach
                </div>
                <div class="p-3 bg-yellow-50 rounded">
                    <p class="font-semibold text-yellow-900 mb-2">Threats</p>
                    @foreach($advancedAnalytics['competitive_positioning']['threats'] as $threat)
                    <p class="text-yellow-700">⚠ {{ $threat }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Scenarios -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">🚀 Growth Scenarios & Strategic Options</h2>
            <p class="text-sm text-gray-600 mb-2">Based on current performance and ₦1.6M bike cost (₦145k/month operating cost per bike)</p>
            
            <!-- Current Status Alert -->
            @php
                $currentRevenue = $analytics['financial_health']['revenue'] ?? 0;
                $currentExpenses = $analytics['financial_health']['expenses'] ?? 0;
                $currentProfit = $currentRevenue - $currentExpenses;
            @endphp
            
            @if($currentProfit < 0)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm font-semibold text-red-900">⚠️ Current Status: Operating at a Loss</p>
                <p class="text-xs text-red-700 mt-1">
                    Current Period: Revenue ₦{{ number_format($currentRevenue, 2) }} - Expenses ₦{{ number_format($currentExpenses, 2) }} = 
                    <span class="font-bold">Loss of ₦{{ number_format(abs($currentProfit), 2) }}</span>
                </p>
                <p class="text-xs text-red-600 mt-2">
                    💡 Growth scenarios show how adding bikes + revenue growth can turn this around. Focus on increasing orders and reducing costs first.
                </p>
            </div>
            @elseif($currentProfit < $currentRevenue * 0.1)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm font-semibold text-yellow-900">⚠️ Current Status: Low Profit Margin</p>
                <p class="text-xs text-yellow-700 mt-1">
                    Current Profit: ₦{{ number_format($currentProfit, 2) }} ({{ round(($currentProfit / max($currentRevenue, 1)) * 100, 2) }}% margin)
                </p>
            </div>
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($advancedAnalytics['growth_scenarios'] as $scenario => $data)
                <div class="border-2 rounded-lg p-5 {{ $scenario == 'moderate' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    <h3 class="font-bold text-lg capitalize mb-3 {{ $scenario == 'moderate' ? 'text-blue-900' : 'text-gray-900' }}">
                        {{ $scenario }} {{ $scenario == 'moderate' ? '⭐ Recommended' : '' }}
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="pb-2 border-b">
                            <p class="text-xs text-gray-500">Revenue Growth</p>
                            <p class="font-bold text-green-600">{{ $data['revenue_growth'] }}</p>
                        </div>
                        <div class="pb-2 border-b">
                            <p class="text-xs text-gray-500">Total Investment</p>
                            <p class="font-bold">{{ $data['additional_investment'] }}</p>
                        </div>
                        <div class="pb-2 border-b">
                            <p class="text-xs text-gray-500">Bikes Added</p>
                            <p class="font-bold">{{ $data['bikes_added'] }} bike(s)</p>
                        </div>
                        <div class="pb-2 border-b">
                            <p class="text-xs text-gray-500">Expected Monthly Revenue</p>
                            <p class="font-bold text-blue-600">₦{{ number_format($data['expected_monthly_revenue'], 2) }}</p>
                        </div>
                        <div class="pb-2 border-b">
                            <p class="text-xs text-gray-500">Expected Monthly Expenses</p>
                            <p class="font-bold text-orange-600">₦{{ number_format($data['expected_monthly_expenses'], 2) }}</p>
                        </div>
                        <div class="pb-2 border-b {{ $data['expected_monthly_profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} -mx-2 px-2 py-2">
                            <p class="text-xs text-gray-700 font-semibold">Expected Monthly Profit</p>
                            <p class="font-bold text-lg {{ $data['expected_monthly_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ₦{{ number_format($data['expected_monthly_profit'], 2) }}
                            </p>
                            @if(isset($data['incremental_profit']))
                            <p class="text-xs {{ $data['incremental_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                                {{ $data['incremental_profit'] >= 0 ? '+' : '' }}₦{{ number_format($data['incremental_profit'], 2) }} vs current
                            </p>
                            @endif
                        </div>
                        <div class="pt-2">
                            <p class="text-xs text-gray-500 font-semibold">Timeline & Payback</p>
                            <p class="text-xs text-gray-700 mt-1"><strong>Implementation:</strong> {{ $data['timeline'] }}</p>
                            <p class="text-xs mt-1">
                                <strong>Payback Period:</strong> 
                                @if(is_numeric($data['roi_months']))
                                    <span class="text-blue-600 font-semibold">{{ $data['roi_months'] }} months</span>
                                @else
                                    <span class="text-red-600 font-semibold">{{ $data['roi_months'] }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('growthChart').getContext('2d');
    const growthData = @json($analytics['growth_metrics']);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: growthData.months,
            datasets: [
                {
                    label: 'Revenue (₦)',
                    data: growthData.revenue,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expenses (₦)',
                    data: growthData.expenses,
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Profit (₦)',
                    data: growthData.profit,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₦' + context.parsed.y.toLocaleString();
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₦' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
