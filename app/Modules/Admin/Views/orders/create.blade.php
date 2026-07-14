@extends('Admin::layout')

@section('title', 'Create Order')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.orders') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Orders
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-900">Create New Order</h2>
        </div>
        
        <form action="{{ route('admin.orders.store') }}" method="POST" enctype="multipart/form-data" class="px-6 py-6">
            @csrf

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
                <div class="md:col-span-2 bg-pink-50 border-2 rounded-lg p-4 mb-4">
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Client (Optional)
                        </label>
                        <select name="client_id" id="client_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white">
                            <option value="">-- Select a client --</option>
                            @foreach(\App\Modules\Admin\Models\Client::where('is_active', true)->orderBy('name')->get() as $client)
                                <option value="{{ $client->id }}" data-name="{{ $client->name }}" data-phone="{{ $client->phone }}" data-email="{{ $client->email }}" data-address="{{ $client->pickup_address }}">
                                    {{ $client->name }} ({{ $client->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

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
                        <option value="whatsapp" {{ old('source') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="instagram" {{ old('source') == 'instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="web" {{ old('source') == 'web' ? 'selected' : '' }}>Website</option>
                        <option value="phone" {{ old('source') == 'phone' ? 'selected' : '' }}>Phone Call</option>
                        <option value="walk-in" {{ old('source') == 'walk-in' ? 'selected' : '' }}>Walk-in</option>
                        <option value="email" {{ old('source') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="source_contact" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Contact (Phone/Email)
                    </label>
                    <input type="text" name="source_contact" id="source_contact"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('source_contact') }}" placeholder="e.g., +234 810 000 0000 or email@example.com">
                </div>

                <div class="md:col-span-2">
                    <label for="source_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Source Notes
                    </label>
                    <textarea name="source_notes" id="source_notes" rows="2"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="Additional information about the order source">{{ old('source_notes') }}</textarea>
                </div>

                <!-- Pickup Information -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Pickup Information</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="pickup_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Pickup Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="pickup_address" id="pickup_address" rows="2" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                              placeholder="123 Main Street, Lagos">{{ old('pickup_address') }}</textarea>
                </div>

                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sender_name" id="sender_name" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_name') }}" placeholder="John Doe">
                </div>

                <div>
                    <label for="sender_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="sender_phone" id="sender_phone" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_phone') }}" placeholder="+234 810 000 0000">
                </div>

                <div class="md:col-span-2">
                    <label for="sender_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Sender's Email Address
                    </label>
                    <input type="email" name="sender_email" id="sender_email"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('sender_email') }}" placeholder="sender@example.com">
                </div>

                <!-- Drop-off Information -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Drop-off Information</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Drop-off Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="delivery_address" id="delivery_address" rows="2" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                              placeholder="16, Computer Village, Ikeja">{{ old('delivery_address') }}</textarea>
                </div>

                <div>
                    <label for="receiver_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver's Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="receiver_name" id="receiver_name" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('receiver_name') }}" placeholder="Jane Smith">
                </div>

                <div>
                    <label for="receiver_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver's Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="receiver_phone" id="receiver_phone" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('receiver_phone') }}" placeholder="+234 810 000 0000">
                </div>

                <!-- Package Details -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Package Details</h3>
                </div>

                <div class="md:col-span-2">
                    <label for="item_description" class="block text-sm font-medium text-gray-700 mb-2">
                        Item Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="item_description" id="item_description" rows="2" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                              placeholder="Electronics, Documents, Clothing, etc.">{{ old('item_description') }}</textarea>
                </div>

                <!-- Quantity, Size, Weight Row -->
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity
                        </label>
                        <input type="number" name="quantity" id="quantity" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               value="{{ old('quantity', 1) }}" placeholder="1">
                    </div>

                    <div>
                        <label for="item_size" class="block text-sm font-medium text-gray-700 mb-2">
                            Size
                        </label>
                        <input type="text" name="item_size" id="item_size"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               value="{{ old('item_size') }}" placeholder="Small, Medium, Large, or dimensions">
                    </div>

                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                            Weight (kg)
                        </label>
                        <input type="number" name="weight" id="weight" step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               value="{{ old('weight') }}" placeholder="5.00">
                    </div>
                </div>

                <div class="md:col-span-2 bg-pink-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold mb-3 brand-accent-text">Price Calculator</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="calc_pickup" class="block text-sm font-medium text-gray-700 mb-2">
                                Pickup Location
                            </label>
                            <input type="text" id="calc_pickup" autocomplete="off"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                                   placeholder="">
                        </div>
                        <div>
                            <label for="calc_dropoff" class="block text-sm font-medium text-gray-700 mb-2">
                                Drop-off Location
                            </label>
                            <input type="text" id="calc_dropoff" autocomplete="off"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                                   placeholder="">
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Price (₦) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="price" id="price" step="0.01" min="0" required
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                       value="{{ old('price') }}" placeholder="Auto-calculated">
                                <button type="button" id="calculate-price-btn" class="absolute right-2 top-1/2 transform -translate-y-1/2 px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    Calculate
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Auto-calculated or editable</p>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <select name="payment_method" id="payment_method"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                                onchange="toggleAmountReceived(this.value)">
                            <option value="">-- Select --</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash (Pay on Delivery)</option>
                            <option value="credits" {{ old('payment_method') == 'credits' ? 'selected' : '' }}>Credits</option>
                            <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                            <option value="subscription" {{ old('payment_method') == 'subscription' ? 'selected' : '' }}>Subscription</option>
                        </select>
                    </div>
                    <div id="amount_received_wrapper" class="{{ old('payment_method') == 'cash' ? '' : 'hidden' }}">
                        <label for="amount_received" class="block text-sm font-medium text-gray-700 mb-2">Amount Received (₦)</label>
                        <input type="number" name="amount_received" id="amount_received" step="0.01" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('amount_received') }}"
                               placeholder="Cash collected from customer">
                    </div>
                </div>

                <!-- Remittance -->
                <div id="remittance_wrapper" class="md:col-span-2 {{ old('payment_method') == 'cash' && old('amount_received') ? '' : 'hidden' }}">
                    <div class="rounded-lg p-4" style="background-color: #fdf1f1; border: 1px solid #e8a0a4;">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="mark_remitted" id="mark_remitted" value="1"
                                   class="w-4 h-4 rounded focus:ring-pink-500" style="accent-color: #C1666B;"
                                   {{ old('mark_remitted') ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Mark as Remitted</span>
                        </label>
                        <p class="mt-1 ml-7 text-xs text-gray-500">Check this if the cash has already been handed over.</p>
                    </div>
                </div>

                <!-- Failed Delivery -->
                <div class="md:col-span-2">
                    <div class="rounded-lg p-4 border border-red-200 bg-red-50">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_failed_delivery" id="is_failed_delivery" value="1"
                                   class="w-4 h-4 rounded focus:ring-red-500" style="accent-color: #dc2626;"
                                   {{ old('is_failed_delivery') ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-red-700">Failed Delivery</span>
                        </label>
                    </div>
                </div>

                <!-- Status & Priority Row -->
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="in_transit" {{ old('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="delivered" {{ old('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        </select>
                    </div>

                    <div>
                        <label for="priority_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority Level <span class="text-red-500">*</span>
                        </label>
                        <select name="priority_level" id="priority_level" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="normal" {{ old('priority_level', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority_level') == 'high' ? 'selected' : '' }}>High Priority</option>
                            <option value="urgent" {{ old('priority_level') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">High priority and urgent orders will be visited first in the route</p>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                              placeholder="Any special instructions or notes...">{{ old('notes') }}</textarea>
                </div>

                <!-- Dates -->
                <div>
                    <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Pickup Date & Time
                    </label>
                    <input type="datetime-local" name="pickup_date" id="pickup_date"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('pickup_date') }}">
                </div>

                <div>
                    <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Drop-off Date & Time
                    </label>
                    <input type="datetime-local" name="delivery_date" id="delivery_date"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('delivery_date') }}">
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
                    </div>

                    <div>
                        <label for="additional_file_2" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional File 2
                        </label>
                        <input type="file" name="additional_file_2" id="additional_file_2"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                    </div>

                    <div>
                        <label for="additional_file_3" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional File 3
                        </label>
                        <input type="file" name="additional_file_3" id="additional_file_3"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.orders') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg brand-accent-bg brand-accent-hover" style="transition: background-color 0.2s ease;">
                    Create Order
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/nominatim-autocomplete.js"></script>
<script>
function toggleAmountReceived(value) {
    const wrapper = document.getElementById('amount_received_wrapper');
    const remitWrapper = document.getElementById('remittance_wrapper');
    if (value === 'cash') {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
        document.getElementById('amount_received').value = '';
        remitWrapper.classList.add('hidden');
        document.getElementById('mark_remitted').checked = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Show remittance checkbox when amount_received is filled
    document.getElementById('amount_received').addEventListener('input', function() {
        const remitWrapper = document.getElementById('remittance_wrapper');
        if (this.value && parseFloat(this.value) > 0) {
            remitWrapper.classList.remove('hidden');
        } else {
            remitWrapper.classList.add('hidden');
            document.getElementById('mark_remitted').checked = false;
        }
    });

    const clientSelect = document.getElementById('client_id');
    const pickupAddressInput = document.getElementById('pickup_address');
    const deliveryAddressInput = document.getElementById('delivery_address');
    const calcPickupInput = document.getElementById('calc_pickup');
    const calcDropoffInput = document.getElementById('calc_dropoff');
    const senderName = document.getElementById('sender_name');
    const senderPhone = document.getElementById('sender_phone');
    const senderEmail = document.getElementById('sender_email');
    const priceInput = document.getElementById('price');
    const calculateBtn = document.getElementById('calculate-price-btn');

    // Autocomplete on main form fields — also syncs into calculator and auto-calculates price
    nominatimAutocomplete(pickupAddressInput, function(address) {
        calcPickupInput.value = address;
        if (calcPickupInput.value && calcDropoffInput.value) calculatePrice();
    });
    nominatimAutocomplete(deliveryAddressInput, function(address) {
        calcDropoffInput.value = address;
        if (calcPickupInput.value && calcDropoffInput.value) calculatePrice();
    });

    // Autocomplete on standalone calculator fields (for trying a different route)
    nominatimAutocomplete(calcPickupInput, function(address) {
        if (calcPickupInput.value && calcDropoffInput.value) calculatePrice();
    });
    nominatimAutocomplete(calcDropoffInput, function(address) {
        if (calcPickupInput.value && calcDropoffInput.value) calculatePrice();
    });

    // Manual calculate button
    calculateBtn.addEventListener('click', function() {
        if (!calcPickupInput.value || !calcDropoffInput.value) {
            alert('Please enter both pickup and dropoff locations in the calculator fields first');
            return;
        }
        calculatePrice();
    });

    // Calculate price function
    async function calculatePrice() {
        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calculating...';

        try {
            const response = await fetch('/metter/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    pickup_address: calcPickupInput.value,
                    dropoff_address: calcDropoffInput.value
                })
            });

            const result = await response.json();

            if (result.success && result.data) {
                // Update price only
                priceInput.value = result.data.delivery_fee;
                
                // Flash success
                priceInput.classList.add('bg-green-50', 'border-green-500');
                setTimeout(() => {
                    priceInput.classList.remove('bg-green-50', 'border-green-500');
                }, 2000);
            } else {
                alert(result.message || 'Could not calculate price. Please try again.');
            }
        } catch (error) {
            console.error('Price calculation error:', error);
            alert('Error calculating price. Please try again.');
        } finally {
            calculateBtn.disabled = false;
            calculateBtn.textContent = 'Calculate';
        }
    }

    // Client auto-fill
    clientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            pickupAddressInput.value = selectedOption.dataset.address || '';
            senderName.value = selectedOption.dataset.name || '';
            senderPhone.value = selectedOption.dataset.phone || '';
            senderEmail.value = selectedOption.dataset.email || '';
            
            [pickupAddressInput, senderName, senderPhone, senderEmail].forEach(field => {
                if (field.value) {
                    field.classList.add('bg-green-50', 'border-green-300');
                    setTimeout(() => {
                        field.classList.remove('bg-green-50', 'border-green-300');
                    }, 2000);
                }
            });
        } else {
            pickupAddressInput.value = '';
            senderName.value = '';
            senderPhone.value = '';
            senderEmail.value = '';
        }
    });
});
</script>
@endsection
