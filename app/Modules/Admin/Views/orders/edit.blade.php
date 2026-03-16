@extends('Admin::layout')

@section('title', 'Edit Order')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.orders.show', $order->id) }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Order Details
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-900">Edit Order #{{ $order->order_number }}</h2>
        </div>
        
        <form action="{{ route('admin.orders.update', $order->id) }}" method="POST" enctype="multipart/form-data" class="px-6 py-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Order Source Information -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Source</h3>
                </div>

                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700 mb-2">
                        Order Source <span class="text-red-500">*</span>
                    </label>
                    <select name="source" id="source" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">Select source</option>
                        <option value="whatsapp" {{ old('source', $order->source) == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="instagram" {{ old('source', $order->source) == 'instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="web" {{ old('source', $order->source) == 'web' ? 'selected' : '' }}>Website</option>
                        <option value="phone" {{ old('source', $order->source) == 'phone' ? 'selected' : '' }}>Phone Call</option>
                        <option value="walk-in" {{ old('source', $order->source) == 'walk-in' ? 'selected' : '' }}>Walk-in</option>
                        <option value="email" {{ old('source', $order->source) == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="other" {{ old('source', $order->source) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="source_contact" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Contact (Phone/Email)
                    </label>
                    <input type="text" name="source_contact" id="source_contact"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('source_contact', $order->source_contact) }}" placeholder="e.g., +234 810 000 0000 or email@example.com">
                </div>

                <div class="md:col-span-2">
                    <label for="source_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Notes
                    </label>
                    <textarea name="source_notes" id="source_notes" rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Any additional notes about the order source">{{ old('source_notes', $order->source_notes) }}</textarea>
                </div>

                <!-- Pickup Information -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Pickup Information</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="pickup_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Pickup Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="pickup_address" id="pickup_address" rows="3" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Full pickup address">{{ old('pickup_address', $order->pickup_address) }}</textarea>
                </div>

                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sender_name" id="sender_name" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_name', $order->sender_name ?? $order->customer_name) }}" placeholder="Full name">
                </div>

                <div>
                    <label for="sender_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="sender_phone" id="sender_phone" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_phone', $order->sender_phone ?? $order->customer_phone) }}" placeholder="+234 810 000 0000">
                </div>

                <div class="md:col-span-2">
                    <label for="sender_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Email Address
                    </label>
                    <input type="email" name="sender_email" id="sender_email"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_email', $order->sender_email ?? $order->customer_email) }}" placeholder="email@example.com">
                </div>

                <!-- Delivery Information -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Delivery Information</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="delivery_address" id="delivery_address" rows="3" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Full delivery address">{{ old('delivery_address', $order->delivery_address) }}</textarea>
                </div>

                <div>
                    <label for="receiver_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver's Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="receiver_name" id="receiver_name" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('receiver_name', $order->receiver_name) }}" placeholder="Full name">
                </div>

                <div>
                    <label for="receiver_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver's Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="receiver_phone" id="receiver_phone" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('receiver_phone', $order->receiver_phone) }}" placeholder="+234 810 000 0000">
                </div>

                <!-- Package Details -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Package Details</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="item_description" class="block text-sm font-medium text-gray-700 mb-2">
                        Item Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="item_description" id="item_description" rows="3" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Describe the item(s) to be delivered">{{ old('item_description', $order->item_description) }}</textarea>
                </div>

                <div>
                    <label for="item_size" class="block text-sm font-medium text-gray-700 mb-2">
                        Size
                    </label>
                    <input type="text" name="item_size" id="item_size"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('item_size', $order->item_size) }}" placeholder="Small, Medium, Large, or dimensions">
                </div>

                <div>
                    <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                        Weight (kg)
                    </label>
                    <input type="number" name="weight" id="weight" step="0.01" min="0"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('weight', $order->weight) }}" placeholder="e.g., 5.5">
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity
                    </label>
                    <input type="number" name="quantity" id="quantity" min="1"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('quantity', $order->quantity) }}" placeholder="1">
                </div>

                <div>
                    <label for="distance" class="block text-sm font-medium text-gray-700 mb-2">
                        Distance (km)
                    </label>
                    <input type="number" name="distance" id="distance" step="0.01" min="0"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('distance', $order->distance) }}" placeholder="25.00">
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Price (₦) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('price', $order->price) }}" placeholder="e.g., 5000.00">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="pending" {{ old('status', $order->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ old('status', $order->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="in_transit" {{ old('status', $order->status) == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ old('status', $order->status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ old('status', $order->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Any additional notes or special instructions">{{ old('notes', $order->notes) }}</textarea>
                </div>

                <!-- Dates -->
                <div>
                    <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Pickup Date & Time
                    </label>
                    <input type="datetime-local" name="pickup_date" id="pickup_date"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('pickup_date', $order->pickup_date ? $order->pickup_date->format('Y-m-d') : '') }}">
                </div>

                <div>
                    <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Date & Time
                    </label>
                    <input type="datetime-local" name="delivery_date" id="delivery_date"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('delivery_date', $order->delivery_date ? $order->delivery_date->format('Y-m-d') : '') }}">
                </div>

                <!-- Package Images -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Package Images</h3>
                    <p class="text-sm text-gray-500 mb-4">Upload photos of the package (optional, up to 4 images)</p>
                </div>

                <div>
                    <label for="package_image_1" class="block text-sm font-medium text-gray-700 mb-2">
                        Package Image 1
                    </label>
                    <input type="file" name="package_image_1" id="package_image_1" accept="image/*"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or GIF (Max 5MB)</p>
                </div>

                <div>
                    <label for="package_image_2" class="block text-sm font-medium text-gray-700 mb-2">
                        Package Image 2
                    </label>
                    <input type="file" name="package_image_2" id="package_image_2" accept="image/*"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or GIF (Max 5MB)</p>
                </div>

                <div>
                    <label for="package_image_3" class="block text-sm font-medium text-gray-700 mb-2">
                        Package Image 3
                    </label>
                    <input type="file" name="package_image_3" id="package_image_3" accept="image/*"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or GIF (Max 5MB)</p>
                </div>

                <div>
                    <label for="package_image_4" class="block text-sm font-medium text-gray-700 mb-2">
                        Package Image 4
                    </label>
                    <input type="file" name="package_image_4" id="package_image_4" accept="image/*"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or GIF (Max 5MB)</p>
                </div>
            </div>

            <!-- Additional Files -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Files (Optional)</h3>
                <p class="text-sm text-gray-500 mb-4">Upload any other relevant documents (invoices, receipts, etc.)</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="additional_file_1" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional File 1
                        </label>
                        <input type="file" name="additional_file_1" id="additional_file_1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        @if($order->additional_file_1)
                            <p class="mt-1 text-xs text-green-600">Current: {{ basename($order->additional_file_1) }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="additional_file_2" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional File 2
                        </label>
                        <input type="file" name="additional_file_2" id="additional_file_2"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        @if($order->additional_file_2)
                            <p class="mt-1 text-xs text-green-600">Current: {{ basename($order->additional_file_2) }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="additional_file_3" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional File 3
                        </label>
                        <input type="file" name="additional_file_3" id="additional_file_3"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        @if($order->additional_file_3)
                            <p class="mt-1 text-xs text-green-600">Current: {{ basename($order->additional_file_3) }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.orders.show', $order->id) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover" style="transition: background-color 0.2s ease;">
                    Update Order
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
