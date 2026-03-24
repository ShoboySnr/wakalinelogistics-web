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

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity
                    </label>
                    <input type="number" name="quantity" id="quantity" min="1"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('quantity', 1) }}" placeholder="1">
                </div>

                <div>
                    <label for="distance" class="block text-sm font-medium text-gray-700 mb-2">
                        Distance (km)
                    </label>
                    <input type="number" name="distance" id="distance" step="0.01" min="0"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('distance') }}" placeholder="25.00">
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Price (₦) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           value="{{ old('price') }}" placeholder="5000.00">
                </div>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('client_id');
    const pickupAddress = document.getElementById('pickup_address');
    const senderName = document.getElementById('sender_name');
    const senderPhone = document.getElementById('sender_phone');
    const senderEmail = document.getElementById('sender_email');

    clientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            // Auto-fill pickup details from selected client
            pickupAddress.value = selectedOption.dataset.address || '';
            senderName.value = selectedOption.dataset.name || '';
            senderPhone.value = selectedOption.dataset.phone || '';
            senderEmail.value = selectedOption.dataset.email || '';
            
            // Highlight the fields that were auto-filled
            [pickupAddress, senderName, senderPhone, senderEmail].forEach(field => {
                if (field.value) {
                    field.classList.add('bg-green-50', 'border-green-300');
                    setTimeout(() => {
                        field.classList.remove('bg-green-50', 'border-green-300');
                    }, 2000);
                }
            });
        } else {
            // Clear fields if no client selected
            pickupAddress.value = '';
            senderName.value = '';
            senderPhone.value = '';
            senderEmail.value = '';
        }
    });
});
</script>
@endsection
