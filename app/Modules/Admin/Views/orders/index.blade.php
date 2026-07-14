@extends('Admin::layout')

@section('title', 'Orders')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Orders Management</h1>
        <div class="flex gap-2">
            <button onclick="openRemitModal()" type="button"
                    class="inline-flex items-center justify-center px-4 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover whitespace-nowrap" style="transition: background-color 0.2s ease;">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Daily Remit
            </button>
            <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover whitespace-nowrap" style="transition: background-color 0.2s ease;">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Order
            </a>
        </div>
    </div>

    <!-- Daily Remit Modal -->
    <div id="remit-modal" class="fixed inset-0 z-50 hidden" aria-modal="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" onclick="closeRemitModal()"></div>

        <!-- Panel -->
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Daily Remittance</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Select a client to see outstanding cash-on-delivery amounts</p>
                    </div>
                    <button onclick="closeRemitModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Client Selector -->
                <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                    <label for="remit-client-select" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                    <select id="remit-client-select" onchange="loadRemittanceData(this.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                        <option value="">-- Select a client --</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}{{ $c->company_name ? ' (' . $c->company_name . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Results area (scrollable) -->
                <div id="remit-body" class="flex-1 overflow-y-auto">
                    <!-- Empty state -->
                    <div id="remit-empty" class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm">Select a client above to load their pending remittances</p>
                    </div>

                    <!-- Loading -->
                    <div id="remit-loading" class="hidden flex flex-col items-center justify-center py-16 text-gray-400">
                        <div class="w-8 h-8 border-4 rounded-full animate-spin mb-3" style="border-color: #f0d0d1; border-top-color: #C1666B;"></div>
                        <p class="text-sm">Loading...</p>
                    </div>

                    <!-- No pending -->
                    <div id="remit-none" class="hidden flex flex-col items-center justify-center py-16 text-gray-400">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #e8a0a4;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-500">All settled — no pending remittances for this client.</p>
                    </div>

                    <!-- Table -->
                    <div id="remit-table-wrap" class="hidden">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Delivery Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Drop-off Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Received</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fee</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">To Remit</th>
                                </tr>
                            </thead>
                            <tbody id="remit-table-body" class="bg-white divide-y divide-gray-100"></tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-700 text-right">Total to Remit</td>
                                    <td id="remit-total" class="px-4 py-3 text-sm font-bold text-right whitespace-nowrap brand-accent-text"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer actions -->
                <div id="remit-footer" class="hidden px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-3 shrink-0">
                    <span id="remit-count-label" class="text-xs text-gray-500"></span>
                    <div class="flex gap-2">
                        <button onclick="copyRemitModalSummary()" type="button"
                                id="remit-copy-btn"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy Summary
                        </button>
                        <form id="remit-mark-form" method="POST" action="" onsubmit="return confirm('Mark all listed orders as remitted?')">
                            @csrf
                            <input type="hidden" name="_order_ids_placeholder" value="">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm text-white rounded-lg brand-accent-bg brand-accent-hover" style="transition: background-color 0.2s ease;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Mark All Remitted
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Revenue Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <!-- Today's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_today'], 2) }}</p>
        </div>

        <!-- Today's Incoming Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Incoming Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['incoming_revenue_today'], 2) }}</p>
        </div>

        <!-- Today's Delivered Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">Today's Delivered</p>
            <p class="text-lg font-bold text-gray-900">{{ $stats['today_delivered'] ?? 0 }}</p>
        </div>

        <!-- This Week's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Week's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_week'], 2) }}</p>
        </div>

        <!-- This Week's Incoming Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Week's Incoming Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['incoming_revenue_week'], 2) }}</p>
        </div>

        <!-- This Month's Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 mb-1">This Month's Revenue</p>
            <p class="text-lg font-bold text-gray-900">₦{{ number_format($stats['revenue_month'], 2) }}</p>
        </div>
    </div>

    <!-- Order Status Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <!-- Total Orders -->
        <a href="{{ route('admin.orders') }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ !request('status') ? 'ring-2 ring-pink-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </a>

        <!-- Pending Orders -->
        <a href="{{ route('admin.orders', ['status' => 'pending']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'pending' ? 'ring-2 ring-yellow-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Pending</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
        </a>

        <!-- Confirmed Orders -->
        <a href="{{ route('admin.orders', ['status' => 'confirmed']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'confirmed' ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Confirmed</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['confirmed'] }}</p>
        </a>

        <!-- In Transit Orders -->
        <a href="{{ route('admin.orders', ['status' => 'in_transit']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'in_transit' ? 'ring-2 ring-purple-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">In Transit</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['in_transit'] }}</p>
        </a>

        <!-- Delivered Orders -->
        <a href="{{ route('admin.orders', ['status' => 'delivered']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'delivered' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Delivered</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['delivered'] }}</p>
        </a>

        <!-- Cancelled Orders -->
        <a href="{{ route('admin.orders', ['status' => 'cancelled']) }}" class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition-shadow cursor-pointer {{ request('status') === 'cancelled' ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Cancelled</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['cancelled'] }}</p>
        </a>
    </div>

    <!-- Client filter banner -->
    @if(request('client_id') && $clientFilter)
    <div class="rounded-lg px-4 py-3 mb-4 flex items-center justify-between" style="background-color: #fdf1f1; border: 1px solid #e8a0a4;">
        <div class="flex items-center gap-2 text-sm" style="color: #7a3b3e;">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Showing orders for client: <strong>{{ $clientFilter->name }}{{ $clientFilter->company_name ? ' (' . $clientFilter->company_name . ')' : '' }}</strong>
        </div>
        <a href="{{ route('admin.orders') }}" class="text-xs font-medium underline brand-accent-text">Clear filter</a>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.orders') }}" class="space-y-4">
            @if(request('client_id'))
            <input type="hidden" name="client_id" value="{{ request('client_id') }}">
            @endif
            @if(request('status') && request('status') !== 'all')
            <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" placeholder="Search by order #, name, phone, address, item..."
                           value="{{ request('search') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                <div class="min-w-[180px]">
                    <select name="date_filter" id="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="" {{ request('date_filter', '') == '' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                        <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>
            </div>

            <!-- Custom Date Range (shown when Custom Range is selected) -->
            <div id="custom_date_range" class="flex flex-wrap gap-4 {{ request('date_filter') == 'custom' ? '' : 'hidden' }}">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.orders') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('date_filter').addEventListener('change', function() {
            const customRange = document.getElementById('custom_date_range');
            if (this.value === 'custom') {
                customRange.classList.remove('hidden');
            } else {
                customRange.classList.add('hidden');
            }
        });
    </script>

    <!-- Orders Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                            @if($order->customer_email)
                            <div class="text-xs text-gray-400">{{ $order->customer_email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="max-w-xs">
                                <div class="truncate text-xs">From: {{ $order->pickup_address }}</div>
                                <div class="truncate text-xs">To: {{ $order->delivery_address }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            ₦{{ number_format($order->price, 2) }}
                            @if($order->amount_received !== null)
                                <div class="text-xs text-gray-400 font-normal mt-0.5">Rcvd: ₦{{ number_format($order->amount_received, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select class="status-dropdown px-2 py-1 text-xs font-semibold rounded-full border-0 focus:ring-2 focus:ring-pink-500 cursor-pointer
                                @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                                @elseif($order->status == 'in_transit') bg-purple-100 text-purple-800
                                @elseif($order->status == 'delivered') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif"
                                data-order-id="{{ $order->id }}"
                                data-current-status="{{ $order->status }}">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="in_transit" {{ $order->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="text-gray-500 hover:text-gray-700">Edit</a>
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">View</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>No orders found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($orders->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $orders->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<script>
// ── Daily Remit Modal ──────────────────────────────────────────────
let remitData = null;

function openRemitModal() {
    document.getElementById('remit-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRemitModal() {
    document.getElementById('remit-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function showRemitPanel(id) {
    document.getElementById('remit-empty').classList.add('hidden');
    document.getElementById('remit-loading').classList.add('hidden');
    document.getElementById('remit-none').classList.add('hidden');
    document.getElementById('remit-table-wrap').classList.add('hidden');
    document.getElementById('remit-footer').classList.add('hidden');
    document.getElementById(id).classList.remove('hidden');
}

async function loadRemittanceData(clientId) {
    if (!clientId) { showRemitPanel('remit-empty'); return; }

    showRemitPanel('remit-loading');

    try {
        const res = await fetch(`{{ route('admin.remittance.pod-data') }}?client_id=${clientId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (!data.orders || data.orders.length === 0) {
            showRemitPanel('remit-none');
            return;
        }

        remitData = data;

        // Build table rows
        const tbody = document.getElementById('remit-table-body');
        tbody.innerHTML = '';
        data.orders.forEach(order => {
            const tr = document.createElement('tr');
            tr.className = order.is_failed ? 'bg-red-50' : 'hover:bg-gray-50';
            const toRemitCell = order.is_failed
                ? `<td class="px-4 py-3 text-right font-semibold whitespace-nowrap text-red-600">−₦${fmt(Math.abs(order.to_remit))}</td>`
                : `<td class="px-4 py-3 text-right font-semibold whitespace-nowrap brand-accent-text">₦${fmt(order.to_remit)}</td>`;
            const receivedCell = order.is_failed
                ? `<td class="px-4 py-3 text-right text-gray-400 whitespace-nowrap italic">—</td>`
                : `<td class="px-4 py-3 text-right text-gray-900 whitespace-nowrap">₦${fmt(order.amount_received)}</td>`;
            const failedBadge = order.is_failed
                ? ` <span style="font-size:10px;background:#fee2e2;color:#b91c1c;padding:1px 5px;border-radius:4px;font-weight:600;">Failed</span>`
                : '';
            tr.innerHTML = `
                <td class="px-4 py-3 font-medium">
                    <a href="/super-admin/orders/${order.id}" target="_blank" class="hover:underline" style="color: #C1666B;">#${order.order_number}</a>${failedBadge}
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-[180px] truncate">${order.delivery_address}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">${order.date}</td>
                ${receivedCell}
                <td class="px-4 py-3 text-right text-red-600 whitespace-nowrap">−₦${fmt(order.delivery_fee)}</td>
                ${toRemitCell}
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('remit-total').textContent = '₦' + fmt(data.total_remittance);

        // Wire up mark-remitted form
        const form = document.getElementById('remit-mark-form');
        form.action = `/super-admin/clients/${data.client_id}/mark-remitted`;
        // Remove old hidden inputs, add fresh ones
        form.querySelectorAll('input[name="order_ids[]"]').forEach(el => el.remove());
        data.orders.forEach(order => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'order_ids[]';
            inp.value = order.id;
            form.appendChild(inp);
        });

        // Count label
        document.getElementById('remit-count-label').textContent =
            `${data.orders.length} order${data.orders.length > 1 ? 's' : ''} · ${data.client_name}`;

        showRemitPanel('remit-table-wrap');
        document.getElementById('remit-footer').classList.remove('hidden');

    } catch (err) {
        console.error(err);
        showRemitPanel('remit-empty');
    }
}

function fmt(num) {
    return Number(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function copyRemitModalSummary() {
    if (!remitData) return;

    const date = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
    const lines = [
        `Remittance Summary — ${remitData.client_name}`,
        `Date: ${date}`,
        '',
    ];

    remitData.orders.forEach((order, i) => {
        lines.push(`${i + 1}. ${order.delivery_address}${order.is_failed ? ' [FAILED DELIVERY]' : ''}`);
        if (order.is_failed) {
            lines.push(`   Delivery Fee charged: ₦${fmt(order.delivery_fee)} | Deducted: −₦${fmt(Math.abs(order.to_remit))}`);
        } else {
            lines.push(`   Received: ₦${fmt(order.amount_received)} | Delivery Fee: ₦${fmt(order.delivery_fee)} | Remit: ₦${fmt(order.to_remit)}`);
        }
    });

    lines.push('');
    lines.push('─'.repeat(40));
    lines.push(`Total Orders: ${remitData.orders.length}`);
    const totalRemit = remitData.total_remittance;
    lines.push(`Total to Remit: ${totalRemit < 0 ? '−₦' + fmt(Math.abs(totalRemit)) : '₦' + fmt(totalRemit)}`);

    navigator.clipboard.writeText(lines.join('\n')).then(() => {
        const btn = document.getElementById('remit-copy-btn');
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!';
        btn.style.color = '#C1666B';
        btn.style.borderColor = '#C1666B';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeRemitModal(); });
// ── End Daily Remit Modal ──────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    const statusDropdowns = document.querySelectorAll('.status-dropdown');
    
    statusDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            const currentStatus = this.dataset.currentStatus;
            const selectElement = this;
            
            // Confirm status change
            if (!confirm(`Change order status to "${newStatus.replace('_', ' ')}"?`)) {
                // Revert to previous status
                this.value = currentStatus;
                return;
            }
            
            // Disable dropdown during update
            selectElement.disabled = true;
            
            // Send AJAX request
            fetch(`/super-admin/orders/${orderId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update current status
                    selectElement.dataset.currentStatus = newStatus;
                    
                    // Update dropdown styling based on new status
                    selectElement.className = 'status-dropdown px-2 py-1 text-xs font-semibold rounded-full border-0 focus:ring-2 focus:ring-pink-500 cursor-pointer';
                    
                    if (newStatus === 'pending') {
                        selectElement.classList.add('bg-yellow-100', 'text-yellow-800');
                    } else if (newStatus === 'confirmed') {
                        selectElement.classList.add('bg-blue-100', 'text-blue-800');
                    } else if (newStatus === 'in_transit') {
                        selectElement.classList.add('bg-purple-100', 'text-purple-800');
                    } else if (newStatus === 'delivered') {
                        selectElement.classList.add('bg-green-100', 'text-green-800');
                    } else if (newStatus === 'cancelled') {
                        selectElement.classList.add('bg-red-100', 'text-red-800');
                    }
                    
                    // Show success message
                    showNotification('Order status updated successfully!', 'success');
                } else {
                    // Revert to previous status
                    selectElement.value = currentStatus;
                    showNotification(data.message || 'Failed to update order status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert to previous status
                selectElement.value = currentStatus;
                showNotification('An error occurred while updating order status', 'error');
            })
            .finally(() => {
                // Re-enable dropdown
                selectElement.disabled = false;
            });
        });
    });
    
    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>
@endsection
