@extends('Admin::layout')

@section('title', 'Order Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.orders') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->order_number }}</h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Created by {{ $order->creator->name ?? 'System' }} on {{ $order->created_at->format('M d, Y \a\t h:i A') }}
                            </p>
                            @if($order->source)
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">Source:</span> 
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($order->source === 'whatsapp') bg-green-100 text-green-800
                                        @elseif($order->source === 'web') bg-blue-100 text-blue-800
                                        @elseif($order->source === 'phone') bg-purple-100 text-purple-800
                                        @elseif($order->source === 'walk-in') bg-orange-100 text-orange-800
                                        @elseif($order->source === 'email') bg-indigo-100 text-indigo-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($order->source) }}
                                    </span>
                                    @if($order->source_contact)
                                        <span class="ml-2 text-gray-500">{{ $order->source_contact }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-6">
                    <!-- Pickup Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Pickup Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pickup Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->pickup_address }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sender's Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->sender_name ?? $order->customer_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sender's Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->sender_phone ?? $order->customer_phone }}</p>
                                </div>
                            </div>
                            @if($order->sender_email || $order->customer_email)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sender's Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->sender_email ?? $order->customer_email }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Drop-off Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Drop-off Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Drop-off Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->delivery_address }}</p>
                            </div>
                            @if($order->receiver_name)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Receiver's Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->receiver_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Receiver's Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->receiver_phone }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Package Details -->
                    @if($order->item_description)
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Package Details</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Item Description</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->item_description }}</p>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                @if($order->item_size)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Size</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->item_size }}</p>
                                </div>
                                @endif
                                @if($order->weight)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Weight</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->weight }} kg</p>
                                </div>
                                @endif
                                @if($order->quantity)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $order->quantity }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Pricing & Additional Info -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Pricing & Additional Info</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Price</label>
                                <p class="mt-1 text-2xl font-bold text-gray-900">₦{{ number_format($order->price, 2) }}</p>
                            </div>
                            @if($order->distance)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Distance</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->distance }} km</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($order->notes)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $order->notes }}</p>
                    </div>
                    @endif

                    <!-- Package Images -->
                    @if($order->package_image_1 || $order->package_image_2 || $order->package_image_3 || $order->package_image_4)
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-3 pb-2 border-b">Package Images</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @if($order->package_image_1)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $order->package_image_1) }}" 
                                     alt="Package Image 1" 
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity"
                                     onclick="openImageModal('{{ asset('storage/' . $order->package_image_1) }}')">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </div>
                            </div>
                            @endif

                            @if($order->package_image_2)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $order->package_image_2) }}" 
                                     alt="Package Image 2" 
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity"
                                     onclick="openImageModal('{{ asset('storage/' . $order->package_image_2) }}')">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </div>
                            </div>
                            @endif

                            @if($order->package_image_3)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $order->package_image_3) }}" 
                                     alt="Package Image 3" 
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity"
                                     onclick="openImageModal('{{ asset('storage/' . $order->package_image_3) }}')">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </div>
                            </div>
                            @endif

                            @if($order->package_image_4)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $order->package_image_4) }}" 
                                     alt="Package Image 4" 
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity"
                                     onclick="openImageModal('{{ asset('storage/' . $order->package_image_4) }}')">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($order->pickup_date)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pickup Date</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $order->pickup_date->format('F d, Y \a\t h:i A') }}</p>
                    </div>
                    @endif

                    @if($order->delivery_date)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Drop-off Date</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $order->delivery_date->format('F d, Y \a\t h:i A') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status & Actions -->
        <div class="space-y-6">
            <!-- Current Status -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Status & Priority</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($order->status == 'confirmed') bg-blue-100 text-blue-800
                            @elseif($order->status == 'in_transit') bg-purple-100 text-purple-800
                            @elseif($order->status == 'delivered') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority Level</label>
                        @if(($order->priority_level ?? 'normal') === 'urgent')
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold bg-red-600 text-white rounded-full animate-pulse">
                            🚨 URGENT
                        </span>
                        @elseif(($order->priority_level ?? 'normal') === 'high')
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold bg-orange-100 text-orange-700 rounded-full border border-orange-300">
                            ⚡ High Priority
                        </span>
                        @else
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold bg-gray-100 text-gray-700 rounded-full">
                            Normal
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <a href="{{ route('admin.orders.edit', $order->id) }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                        Edit Order
                    </a>
                    @if($order->status === 'delivered')
                    <a href="{{ route('admin.orders.invoice', $order->id) }}" class="block w-full px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                        <svg class="inline-block w-5 h-5 mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download Invoice
                    </a>
                    @endif
                </div>
            </div>

            <!-- Assign Rider -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Assign Rider</h3>
                </div>
                <div class="px-6 py-4">
                    @if($order->rider)
                        <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm font-medium text-blue-900">Current Rider:</p>
                            <p class="text-sm text-blue-800">
                                <a href="{{ route('admin.riders.show', $order->rider->id) }}" class="brand-accent-text hover:underline font-semibold">{{ $order->rider->name }}</a>
                            </p>
                            <p class="text-xs text-blue-600">{{ $order->rider->phone }}</p>
                        </div>
                    @else
                        <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">No rider assigned yet</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('admin.orders.assign-rider', $order->id) }}">
                        @csrf
                        <select name="rider_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 mb-3">
                            <option value="">-- Select Rider --</option>
                            @foreach($riders as $rider)
                                <option value="{{ $rider->id }}" {{ $order->rider_id == $rider->id ? 'selected' : '' }}>
                                    {{ $rider->name }} - {{ $rider->phone }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                            Assign Rider
                        </button>
                    </form>
                </div>
            </div>

            <!-- Update Status -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Update Status</h3>
                </div>
                <div class="px-6 py-4">
                    <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}">
                        @csrf
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 mb-3">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="in_transit" {{ $order->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <button type="submit" class="w-full px-4 py-2 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors">
                            Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Delete Order -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Danger Zone</h3>
                </div>
                <div class="px-6 py-4">
                    <form method="POST" action="{{ route('admin.orders.delete', $order->id) }}" onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                            Delete Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img id="modalImage" src="" alt="Package Image" class="max-w-full max-h-screen rounded-lg" onclick="event.stopPropagation()">
    </div>
</div>

<script>
function openImageModal(imageSrc) {
    document.getElementById('imageModal').classList.remove('hidden');
    document.getElementById('modalImage').src = imageSrc;
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.getElementById('modalImage').src = '';
    document.body.style.overflow = 'auto';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endsection
