@extends('Admin::layout')

@section('title', 'Client Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.clients') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                @if($client->is_active)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                @endif
            </div>
            @if($client->company_name)
            <p class="text-sm text-gray-500">{{ $client->company_name }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.clients.edit', $client->id) }}" class="px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Edit Client
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Total Orders</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Completed</p>
            <p class="text-2xl font-bold text-green-600">{{ $completedOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900">₦{{ number_format($totalRevenue, 2) }}</p>
        </div>

        <div class="rounded-lg shadow p-6 text-white" style="background: linear-gradient(to bottom right, #C1666B, #A85559);">
            <div class="flex items-center justify-between mb-1">
                <p class="text-sm opacity-90">Credit Balance</p>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-2xl font-bold">{{ number_format($clientCredit->available_credits) }}</p>
            <p class="text-xs opacity-75 mt-1">of {{ number_format($clientCredit->total_credits) }} total</p>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Client Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Primary Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="tel:{{ $client->phone }}" class="hover:underline" style="color: #C1666B;">{{ $client->phone }}</a>
                            </dd>
                        </div>
                        @if($client->alternate_phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Alternate Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="tel:{{ $client->alternate_phone }}" class="hover:underline" style="color: #C1666B;">{{ $client->alternate_phone }}</a>
                            </dd>
                        </div>
                        @endif
                        @if($client->email)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="mailto:{{ $client->email }}" class="hover:underline" style="color: #C1666B;">{{ $client->email }}</a>
                            </dd>
                        </div>
                        @endif
                        @if($client->contact_person)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->contact_person }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Address Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Address Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pickup Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->pickup_address }}</dd>
                        </div>
                        @if($client->city || $client->state)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">City / State</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->city }}{{ $client->city && $client->state ? ', ' : '' }}{{ $client->state }}</dd>
                        </div>
                        @endif
                        @if($client->landmark)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Landmark</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->landmark }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('admin.orders') }}?client_id={{ $client->id }}" class="text-sm font-medium hover:underline" style="color: #C1666B;">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($client->orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank" class="brand-accent-text hover:underline" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($order->status === 'delivered') bg-green-100 text-green-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">₦{{ number_format($order->price, 2) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No orders yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- POD Remittance -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Pay on Delivery — Pending Remittance</h2>
                        @if($podPendingOrders->count() > 0)
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $podPendingOrders->count() }} order{{ $podPendingOrders->count() > 1 ? 's' : '' }} · Total to remit:
                            <span class="font-semibold brand-accent-text">₦{{ number_format($podTotalRemittance, 2) }}</span>
                        </p>
                        @endif
                    </div>
                    @if($podPendingOrders->count() > 0)
                    <div class="flex gap-2 shrink-0">
                        <button onclick="copyRemittanceSummary()" type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy Summary
                        </button>
                        <form method="POST" action="{{ route('admin.clients.mark-remitted', $client->id) }}"
                              onsubmit="return confirm('Mark all {{ $podPendingOrders->count() }} order(s) as remitted?')">
                            @csrf
                            @foreach($podPendingOrders as $podOrder)
                            <input type="hidden" name="order_ids[]" value="{{ $podOrder->id }}">
                            @endforeach
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white rounded-md brand-accent-bg brand-accent-hover">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Mark All Remitted
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                @if($podPendingOrders->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="pod-remittance-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Drop-off Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Received</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Delivery Fee</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">To Remit</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($podPendingOrders as $podOrder)
                            @php
                                $isFailed = $podOrder->is_failed_delivery;
                                $toRemit = $isFailed ? -$podOrder->price : max(0, $podOrder->amount_received - $podOrder->price);
                                $receivedFormatted = $isFailed ? '0.00' : number_format($podOrder->amount_received, 2);
                            @endphp
                            <tr class="{{ $isFailed ? 'bg-red-50' : 'hover:bg-gray-50' }}"
                                data-order-number="{{ $podOrder->order_number }}"
                                data-delivery-address="{{ $podOrder->delivery_address }}"
                                data-received="{{ $receivedFormatted }}"
                                data-fee="{{ number_format($podOrder->price, 2) }}"
                                data-remit="{{ number_format(abs($toRemit), 2) }}"
                                data-is-failed="{{ $isFailed ? '1' : '0' }}">
                                <td class="px-6 py-4 text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.orders.show', $podOrder->id) }}" class="brand-accent-text hover:underline" target="_blank" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
                                            #{{ $podOrder->order_number }}
                                        </a>
                                        @if($isFailed)
                                        <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-700">Failed</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                    <span class="block truncate">{{ $podOrder->delivery_address }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $podOrder->delivery_date ? $podOrder->delivery_date->format('M d, Y') : '—' }}</td>
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap {{ $isFailed ? 'text-gray-400 italic' : 'text-gray-900' }}">
                                    {{ $isFailed ? '—' : '₦' . number_format($podOrder->amount_received, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-red-600 whitespace-nowrap">−₦{{ number_format($podOrder->price, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold whitespace-nowrap {{ $isFailed ? 'text-red-600' : 'brand-accent-text' }}">
                                    {{ $isFailed ? '−₦' . number_format(abs($toRemit), 2) : '₦' . number_format($toRemit, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.orders.edit', $podOrder->id) }}" class="text-xs text-gray-500 hover:text-gray-700 underline">Edit</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-gray-700 text-right">Total to Remit</td>
                                <td class="px-6 py-3 text-sm font-bold text-right whitespace-nowrap brand-accent-text">₦{{ number_format($podTotalRemittance, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="px-6 py-8 text-center text-sm text-gray-500">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    No pending POD remittances. All cash orders have been settled.
                </div>
                @endif
            </div>

            <!-- Invoice history -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Invoices</h2>
                    <span class="text-sm text-gray-500">{{ $invoices->count() }} generated</span>
                </div>
                @if($invoices->isEmpty())
                    <div class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">No invoices generated for this client yet.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->orders_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">&#8358;{{ number_format((float) $invoice->total, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-3">
                                    <a href="{{ route('admin.invoices.preview', $invoice->id) }}" target="_blank" class="brand-accent-text hover:underline">Preview</a>
                                    <a href="{{ route('admin.invoices.download', $invoice->id) }}" class="brand-accent-text hover:underline">PDF</a>
                                    <form method="POST" action="{{ route('admin.invoices.destroy', $invoice->id) }}" class="inline"
                                          onsubmit="return confirm('Delete {{ $invoice->invoice_number }}? The orders themselves are not affected.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                </div>
                <div class="px-6 py-4 space-y-2">
                    <a href="{{ route('admin.orders.create') }}?client_id={{ $client->id }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover">
                        Create Order
                    </a>
                    <button type="button" id="openInvoiceModal"
                            class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover">
                        Generate Invoice
                    </button>
                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="block w-full px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Edit Client
                    </a>
                    <form action="{{ route('admin.clients.delete', $client->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full px-4 py-2 text-center brand-accent-bg text-white rounded-md brand-accent-hover">
                            Delete Client
                        </button>
                    </form>
                </div>
            </div>

            <!-- Business Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Business Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        @if($client->business_type)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($client->business_type) }}
                                </span>
                            </dd>
                        </div>
                        @endif
                        @if($client->payment_terms)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Payment Terms</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($client->payment_terms === 'prepaid')
                                    Prepaid
                                @elseif($client->payment_terms === 'postpaid')
                                    Postpaid
                                @elseif($client->payment_terms === 'credit_30')
                                    Net 30
                                @elseif($client->payment_terms === 'credit_60')
                                    Net 60
                                @endif
                            </dd>
                        </div>
                        @endif
                        @if($client->credit_limit)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Credit Limit</dt>
                            <dd class="mt-1 text-sm text-gray-900">₦{{ number_format($client->credit_limit, 2) }}</dd>
                        </div>
                        @endif
                        @if($client->tax_id)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tax ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->tax_id }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Additional Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->updated_at->format('M d, Y') }}</dd>
                        </div>
                        @if($client->notes)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $client->notes }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Email Verification -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Email Verification</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($client->email_verified_at)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-green-700">Email Verified</p>
                                <p class="text-xs text-gray-500">{{ $client->email_verified_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-yellow-700">Email Not Verified</p>
                        </div>

                        @if($client->email_verification_code && $client->email_verification_expires_at && $client->email_verification_expires_at->isFuture())
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-xs text-blue-700 font-semibold mb-2">Active Verification Code</p>
                                <p class="text-2xl font-black tracking-[0.35em] text-blue-900 text-center py-2">{{ $client->email_verification_code }}</p>
                                <p class="text-xs text-gray-500 text-center mt-1">
                                    Expires {{ $client->email_verification_expires_at->diffForHumans() }}
                                    ({{ $client->email_verification_expires_at->format('h:i A') }})
                                </p>
                            </div>
                        @else
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
                                <p class="text-sm text-gray-500">No active verification code</p>
                                <p class="text-xs text-gray-400 mt-1">Code was never sent or has expired</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.clients.verify-email', $client->id) }}" class="mt-3" onsubmit="return confirm('Manually verify this client\'s email? This will also credit the ₦2,000 signup bonus if not yet credited.')">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                                Verify Email Manually
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Subscription Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Subscription Plan</h2>
                    <p class="text-xs text-gray-500 mt-1">Manually assign a subscription plan to this client</p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @if($activeSubscription)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-green-800">{{ $activeSubscription->plan->name }}</p>
                                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Active</span>
                            </div>
                            <p class="text-xs text-gray-600">Credits: {{ number_format($activeSubscription->credits_used) }} / {{ number_format($activeSubscription->credits_allocated) }} used</p>
                            @if($activeSubscription->expires_at)
                            <p class="text-xs text-gray-500 mt-1">Expires: {{ $activeSubscription->expires_at->format('M d, Y') }}</p>
                            @else
                            <p class="text-xs text-gray-500 mt-1">No expiry</p>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                            <p class="text-sm text-gray-500">No active subscription</p>
                        </div>
                    @endif

                    @if($subscriptionPlans->count() > 0)
                    <form method="POST" action="{{ route('admin.clients.subscribe', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4">
                        @csrf
                        <h3 class="text-sm font-semibold text-gray-900">{{ $activeSubscription ? 'Change Subscription' : 'Assign Subscription' }}</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                            <select name="subscription_plan_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                                <option value="">Select a plan…</option>
                                @foreach($subscriptionPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->credits }} credits ({{ $plan->billing_cycle }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="starts_at" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <input type="text" name="notes" placeholder="Reason for manual subscription" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <button type="submit" onclick="return confirm('This will cancel any existing active subscription and assign the selected plan. Credits from the plan will be added immediately. Continue?')" class="w-full px-4 py-2 brand-accent-bg text-white text-sm rounded-md brand-accent-hover transition-colors">
                            Assign Plan
                        </button>
                    </form>
                    @else
                    <p class="text-xs text-gray-500 text-center">No active subscription plans available. <a href="{{ route('admin.credits.plans') }}" class="underline" style="color:#C1666B;">Create one</a></p>
                    @endif
                </div>
            </div>

            <!-- Credits Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Delivery Credits</h2>
                    <p class="text-xs text-gray-500 mt-1">Manually add delivery credits to this client</p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-xs text-green-700 font-medium">Available</p>
                            <p class="text-xl font-bold text-green-900">{{ number_format($clientCredit->available_credits) }}</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-700 font-medium">Total</p>
                            <p class="text-xl font-bold text-blue-900">{{ number_format($clientCredit->total_credits) }}</p>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <p class="text-xs text-red-700 font-medium">Used</p>
                            <p class="text-xl font-bold text-red-900">{{ number_format($clientCredit->used_credits) }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.clients.add-credits', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4">
                        @csrf
                        <h3 class="text-sm font-semibold text-gray-900">Add Credits</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Number of Credits</label>
                            <input type="number" name="credits" required min="1" placeholder="e.g. 50" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reason</label>
                            <input type="text" name="reason" required placeholder="e.g. Goodwill credit, Compensation" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 brand-accent-bg text-white text-sm rounded-md brand-accent-hover transition-colors">
                            Add Credits
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.clients.deduct-credits', $client->id) }}" class="space-y-3 border-t border-gray-200 pt-4" onsubmit="return confirm('Are you sure you want to deduct credits from this client?')">
                        @csrf
                        <h3 class="text-sm font-semibold text-red-700">Deduct Credits</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Number of Credits to Deduct</label>
                            <input type="number" name="credits" required min="1" max="{{ $clientCredit->available_credits }}" placeholder="e.g. 10" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Reason</label>
                            <input type="text" name="reason" required placeholder="e.g. Correction, Reversal" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                            Deduct Credits
                        </button>
                    </form>

                    <a href="{{ route('admin.credits.client-history', $client->id) }}" class="block w-full px-4 py-2 text-center text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 text-sm">
                        View Credit History
                    </a>
                </div>
            </div>

            <!-- Order Tracking Share Link -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Order Tracking</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @php
                        $clientShare = $client->clientShare()->where('is_active', true)->first();
                        $shareUrl = $clientShare ? config('app.frontend_url') . '/share/' . $clientShare->token : null;
                    @endphp
                    
                    @if($shareUrl)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-gray-600 mb-2 font-semibold">Active Order Tracking Link:</p>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="text" value="{{ $shareUrl }}" readonly class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md" onclick="this.select()">
                                <button onclick="copyToClipboard('{{ $shareUrl }}')" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors">
                                    Copy
                                </button>
                            </div>
                            <button onclick="disableClientShare({{ $client->id }})" class="w-full px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                                Disable Order Tracking
                            </button>
                        </div>
                    @else
                        <button onclick="generateClientShareLink()" class="block w-full px-4 py-2 text-center bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Enable Order Tracking
                        </button>
                    @endif
                </div>
            </div>

            <!-- Daily Remittance -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Daily Remittance</h2>
                    <p class="text-xs text-gray-500 mt-1">Enable to track Pay-on-Delivery remittances for this client</p>
                </div>
                <div class="px-6 py-4">
                    @if($client->pod_remittance_enabled)
                        <div class="rounded-lg p-4 mb-3" style="background-color: #fdf1f1; border: 1px solid #e8a0a4;">
                            <p class="text-sm font-semibold brand-accent-text">POD Remittance: Enabled</p>
                            <p class="text-xs text-gray-500 mt-1">This client appears in the Daily Remit modal and their cash orders are tracked for remittance.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.clients.toggle-pod-remittance', $client->id) }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 transition-colors"
                                    onclick="return confirm('Disable daily remittance for {{ $client->name }}?')">
                                Disable Daily Remittance
                            </button>
                        </form>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3">
                            <p class="text-sm text-gray-600 font-semibold">POD Remittance: <span class="text-gray-400">Disabled</span></p>
                            <p class="text-xs text-gray-500 mt-1">Enable to include this client in the Daily Remit tracking and modal.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.clients.toggle-pod-remittance', $client->id) }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-white text-sm rounded-md brand-accent-bg brand-accent-hover transition-colors">
                                Enable Daily Remittance
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- API Access Management -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">API Access</h2>
                    <p class="text-xs text-gray-500 mt-1">Manage client's API key for external integrations</p>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($client->api_enabled && $client->api_key)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-2">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-600 font-semibold">API Access: <span class="text-green-600">Enabled</span></p>
                                @if($client->api_key_generated_at)
                                <p class="text-xs text-gray-500">Generated: {{ $client->api_key_generated_at->format('M d, Y h:i A') }}</p>
                                @endif
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-xs text-gray-600 mb-2 font-semibold">API Key:</p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    <input type="password" id="apiKeyDisplay" value="{{ $client->api_key }}" readonly class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md font-mono min-w-0" onclick="this.select()">
                                    <div class="flex gap-2 flex-shrink-0">
                                        <button onclick="toggleApiKeyVisibility()" class="px-3 py-2 bg-gray-600 text-white text-xs rounded-md hover:bg-gray-700 transition-colors whitespace-nowrap">
                                            <span id="toggleText">View</span>
                                        </button>
                                        <button onclick="copyApiKey('{{ $client->api_key }}')" class="px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-colors whitespace-nowrap">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($client->api_last_used_at)
                            <p class="text-xs text-gray-500 mb-3">Last used: {{ $client->api_last_used_at->diffForHumans() }}</p>
                            @else
                            <p class="text-xs text-gray-500 mb-3">Never used</p>
                            @endif
                            
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button onclick="regenerateClientApiKey({{ $client->id }})" class="flex-1 px-4 py-2 bg-yellow-600 text-white text-xs sm:text-sm rounded-md hover:bg-yellow-700 transition-colors whitespace-nowrap">
                                    Regenerate Key
                                </button>
                                <button onclick="disableClientApiAccess({{ $client->id }})" class="flex-1 px-4 py-2 bg-red-600 text-white text-xs sm:text-sm rounded-md hover:bg-red-700 transition-colors whitespace-nowrap">
                                    Disable API Access
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">
                                <span class="font-semibold">API Access: <span class="text-red-600">Disabled</span></span><br>
                                <span class="text-xs">Enable API access to allow this client to integrate with external systems.</span>
                            </p>
                            <button onclick="enableClientApiAccess({{ $client->id }})" class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                                Enable API Access
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate invoice modal -->
<div id="invoiceModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" data-close-invoice></div>
    <div class="relative mx-auto my-6 w-full max-w-4xl px-4">
        <form method="POST" action="{{ route('admin.invoices.store', $client->id) }}" id="invoiceForm"
              class="bg-white rounded-lg shadow-xl flex flex-col" style="max-height: calc(100vh - 3rem);">
            @csrf
            <input type="hidden" name="action" id="invoiceAction" value="preview">

            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Generate Invoice</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $client->company_name ?: $client->name }}</p>
                </div>
                <button type="button" data-close-invoice class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="px-6 py-4 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice date</label>
                        <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
                        <input type="date" name="due_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
                        <input type="number" name="discount_percent" id="invDiscount" value="0" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">VAT %</label>
                        <input type="number" name="tax_percent" id="invTax" value="0" min="0" max="100" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deduction (&#8358;)</label>
                        <input type="number" name="deduction_amount" id="invDeduction" value="0" min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deduction label</label>
                        <input type="text" name="deduction_label" id="invDeductionLabel" maxlength="120"
                               placeholder="e.g. Less: POD cash held on your behalf"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <h3 class="text-sm font-semibold text-gray-900">Select orders</h3>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <input type="text" id="invSearch" placeholder="Filter by order no. or address"
                               class="px-2 py-1.5 border border-gray-300 rounded w-56 focus:outline-none focus:ring-1 focus:ring-[#C1666B]">
                        <select id="invStatusFilter" class="px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#C1666B]">
                            <option value="">All statuses</option>
                            @foreach(['pending','confirmed','in_transit','delivered','cancelled'] as $st)
                                <option value="{{ $st }}">{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-1.5 text-gray-600">
                            <input type="checkbox" id="invHideInvoiced" class="rounded border-gray-300 text-[#C1666B] focus:ring-[#C1666B]">
                            Hide already invoiced
                        </label>
                        <button type="button" id="invSelectAll" class="brand-accent-text hover:underline">Select all</button>
                        <button type="button" id="invClear" class="text-gray-500 hover:underline">Clear</button>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-md overflow-hidden">
                    <div class="max-h-72 overflow-y-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 w-10"></th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($invoiceableOrders as $o)
                                @php $already = $invoicedOrderIds->has($o->id); @endphp
                                <tr class="inv-row hover:bg-gray-50"
                                    data-status="{{ $o->status }}"
                                    data-invoiced="{{ $already ? '1' : '0' }}"
                                    data-search="{{ strtolower($o->order_number.' '.$o->pickup_address.' '.$o->delivery_address) }}">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="order_ids[]" value="{{ $o->id }}"
                                               class="inv-check rounded border-gray-300 text-[#C1666B] focus:ring-[#C1666B]"
                                               data-amount="{{ (float) $o->price }}">
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="font-medium text-gray-900">{{ $o->order_number }}</span>
                                        @if($already)
                                            <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[10px] rounded">invoiced</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ optional($o->delivery_date ?: $o->created_at)->format('d M Y') }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="px-2 py-0.5 text-xs rounded-full
                                            @if($o->status === 'delivered') bg-green-100 text-green-800
                                            @elseif($o->status === 'cancelled') bg-red-100 text-red-800
                                            @elseif($o->status === 'in_transit') bg-blue-100 text-blue-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst(str_replace('_',' ',$o->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ Str::limit($o->pickup_address, 28) }} &rarr; {{ Str::limit($o->delivery_address, 28) }}</td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">&#8358;{{ number_format((float) $o->price, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">This client has no orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment terms</label>
                        <textarea name="payment_terms" rows="3" placeholder="e.g. Payment due within 14 days."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3" placeholder="Anything else to print on the invoice."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B]"></textarea>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    <span id="invCount">0</span> selected &middot;
                    <span id="invTotalLabel">Total</span>
                    <span class="font-semibold text-gray-900">&#8358;<span id="invTotal">0.00</span></span>
                </p>
                <div class="flex gap-2">
                    <button type="button" data-close-invoice class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">Cancel</button>
                    <button type="submit" id="invPreviewBtn" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">Preview</button>
                    <button type="submit" id="invDownloadBtn" class="px-5 py-2 text-white rounded-md brand-accent-bg brand-accent-hover text-sm font-medium">Download as PDF</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const clientId = {{ $client->id }};

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function generateClientShareLink() {
    if (!confirm('This will generate a shareable link for the client to track their orders. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/generate-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order tracking link generated successfully!');
            location.reload();
        } else {
            alert('Failed to generate link. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function disableClientShare(clientId) {
    if (!confirm('This will disable the order tracking link. The client will no longer be able to access it. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/disable-share-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order tracking link disabled successfully!');
            location.reload();
        } else {
            alert('Failed to disable link. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// API Key Management Functions
function toggleApiKeyVisibility() {
    const input = document.getElementById('apiKeyDisplay');
    const toggleText = document.getElementById('toggleText');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggleText.textContent = 'Hide';
    } else {
        input.type = 'password';
        toggleText.textContent = 'View';
    }
}

function copyApiKey(apiKey) {
    navigator.clipboard.writeText(apiKey).then(() => {
        alert('API key copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy API key');
    });
}

function enableClientApiAccess(clientId) {
    if (!confirm('This will generate an API key for the client. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/enable-api-access`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API access enabled successfully!\n\nAPI Key: ' + data.api_key + '\n\nPlease save this key securely.');
            location.reload();
        } else {
            alert('Failed to enable API access. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function regenerateClientApiKey(clientId) {
    if (!confirm('WARNING: This will invalidate the current API key. Any integrations using the old key will stop working. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/regenerate-api-key`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API key regenerated successfully!\n\nNew API Key: ' + data.api_key + '\n\nPlease save this key securely.');
            location.reload();
        } else {
            alert('Failed to regenerate API key. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function disableClientApiAccess(clientId) {
    if (!confirm('This will disable API access for this client. Their integrations will stop working. Continue?')) {
        return;
    }
    
    fetch(`/super-admin/clients/${clientId}/disable-api-access`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('API access disabled successfully!');
            location.reload();
        } else {
            alert('Failed to disable API access. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function copyRemittanceSummary() {
    const clientName = "{{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}";
    const date = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

    const rows = document.querySelectorAll('#pod-remittance-table tbody tr');
    if (!rows.length) return;

    let lines = [];
    lines.push(`Remittance Summary — ${clientName}`);
    lines.push(`Date: ${date}`);
    lines.push('');

    rows.forEach((row, i) => {
        const addr = row.dataset.deliveryAddress;
        const received = row.dataset.received;
        const fee = row.dataset.fee;
        const remit = row.dataset.remit;
        const isFailed = row.dataset.isFailed === '1';
        lines.push(`${i + 1}. ${addr}${isFailed ? ' [FAILED DELIVERY]' : ''}`);
        if (isFailed) {
            lines.push(`   Delivery Fee charged: ₦${fee} | Deducted: −₦${remit}`);
        } else {
            lines.push(`   Received: ₦${received} | Delivery Fee: ₦${fee} | Remit: ₦${remit}`);
        }
    });

    lines.push('');
    lines.push('─'.repeat(40));
    lines.push(`Total Orders: ${rows.length}`);
    @php $absTotal = abs($podTotalRemittance); @endphp
    lines.push(`Total to Remit: {{ $podTotalRemittance < 0 ? '−₦' . number_format($absTotal, 2) : '₦' . number_format($absTotal, 2) }}`);

    navigator.clipboard.writeText(lines.join('\n')).then(() => {
        const btn = document.querySelector('[onclick="copyRemittanceSummary()"]');
        const original = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!';
        btn.style.color = '#C1666B';
        btn.style.borderColor = '#C1666B';
        setTimeout(() => { btn.innerHTML = original; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
    });
}

(function () {
    const modal = document.getElementById('invoiceModal');
    if (!modal) return;

    const form = document.getElementById('invoiceForm');
    const rows = Array.from(modal.querySelectorAll('.inv-row'));
    const checks = Array.from(modal.querySelectorAll('.inv-check'));
    const search = document.getElementById('invSearch');
    const statusFilter = document.getElementById('invStatusFilter');
    const hideInvoiced = document.getElementById('invHideInvoiced');
    const countEl = document.getElementById('invCount');
    const totalEl = document.getElementById('invTotal');
    const discountEl = document.getElementById('invDiscount');
    const taxEl = document.getElementById('invTax');
    const dedEl = document.getElementById('invDeduction');

    const money = (n) => n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function refreshTotals() {
        let n = 0, subtotal = 0;
        checks.forEach(c => {
            if (c.checked) { n++; subtotal += parseFloat(c.dataset.amount || '0'); }
        });
        const discount = subtotal * (parseFloat(discountEl.value || '0') / 100);
        const taxable = subtotal - discount;
        const tax = taxable * (parseFloat(taxEl.value || '0') / 100);
        const deduction = parseFloat(dedEl.value || '0');
        countEl.textContent = n;
        totalEl.textContent = money(taxable + tax - deduction);
        document.getElementById('invTotalLabel').textContent = deduction > 0 ? 'amount due' : 'total';
    }

    function applyFilters() {
        const term = (search.value || '').toLowerCase().trim();
        const status = statusFilter.value;
        rows.forEach(row => {
            let show = true;
            if (term && !row.dataset.search.includes(term)) show = false;
            if (status && row.dataset.status !== status) show = false;
            if (hideInvoiced.checked && row.dataset.invoiced === '1') show = false;
            row.style.display = show ? '' : 'none';
        });
    }

    function open() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.getElementById('openInvoiceModal').addEventListener('click', open);
    modal.querySelectorAll('[data-close-invoice]').forEach(el => el.addEventListener('click', close));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    checks.forEach(c => c.addEventListener('change', refreshTotals));
    [discountEl, taxEl, dedEl].forEach(el => el.addEventListener('input', refreshTotals));
    [search, statusFilter, hideInvoiced].forEach(el => el.addEventListener('input', applyFilters));

    // Only touch rows the current filter leaves visible.
    document.getElementById('invSelectAll').addEventListener('click', () => {
        rows.forEach(row => {
            if (row.style.display === 'none') return;
            const c = row.querySelector('.inv-check');
            if (c) c.checked = true;
        });
        refreshTotals();
    });
    document.getElementById('invClear').addEventListener('click', () => {
        checks.forEach(c => { c.checked = false; });
        refreshTotals();
    });

    // Preview opens a new tab; download must stay in this one or the browser
    // blocks the file. target is set per button, then reset.
    document.getElementById('invPreviewBtn').addEventListener('click', () => {
        document.getElementById('invoiceAction').value = 'preview';
        form.target = '_blank';
    });
    document.getElementById('invDownloadBtn').addEventListener('click', () => {
        document.getElementById('invoiceAction').value = 'download';
        form.target = '_self';
    });

    form.addEventListener('submit', e => {
        if (!checks.some(c => c.checked)) {
            e.preventDefault();
            alert('Select at least one order to invoice.');
            return;
        }
        if (form.target === '_blank') {
            setTimeout(() => { form.target = '_self'; close(); window.location.reload(); }, 400);
        }
    });

    refreshTotals();
})();

</script>
@endsection
