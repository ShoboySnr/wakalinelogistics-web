@extends('Client::layouts.app')

@section('title', 'Order Details')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="{{ route('client.orders') }}" class="text-gray-400 hover:text-gray-500">Orders</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="ml-4 text-sm font-medium text-gray-500">{{ $order->order_number }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
                Order {{ $order->order_number }}
            </h2>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Order Status</h3>
                <p class="mt-1 text-sm text-gray-500">Created on {{ $order->created_at->format('F d, Y') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-semibold rounded-full 
                @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                @elseif($order->status == 'in_transit') bg-purple-100 text-purple-800
                @elseif($order->status == 'delivered') bg-green-100 text-green-800
                @else bg-red-100 text-red-800
                @endif">
                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Sender Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Sender Information</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->sender_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->sender_phone }}</dd>
                </div>
                @if($order->sender_email)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->sender_email }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Pickup Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->pickup_address }}</dd>
                </div>
                @if($order->pickup_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Pickup Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($order->pickup_date)->format('F d, Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Receiver Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Receiver Information</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->receiver_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->receiver_phone }}</dd>
                </div>
                @if($order->receiver_email)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->receiver_email }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Delivery Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->delivery_address }}</dd>
                </div>
                @if($order->delivery_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Delivery Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($order->delivery_date)->format('F d, Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Item Details -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Item Details</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $order->item_description ?? 'N/A' }}</dd>
            </div>
            @if($order->item_weight)
            <div>
                <dt class="text-sm font-medium text-gray-500">Weight</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $order->item_weight }} kg</dd>
            </div>
            @endif
            @if($order->item_quantity)
            <div>
                <dt class="text-sm font-medium text-gray-500">Quantity</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $order->item_quantity }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Order Details -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $order->order_number }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Price</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold text-green-600">₦{{ number_format($order->price, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($order->priority ?? 'normal') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $order->created_at->format('M d, Y h:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $order->updated_at->format('M d, Y h:i A') }}</dd>
            </div>
        </dl>
    </div>

    <!-- Special Instructions -->
    @if($order->special_instructions)
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Special Instructions</h3>
        <p class="text-sm text-gray-700">{{ $order->special_instructions }}</p>
    </div>
    @endif

    <!-- Actions -->
    <div class="flex justify-between">
        <a href="{{ route('client.orders') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Back to Orders
        </a>
    </div>
</div>
@endsection
