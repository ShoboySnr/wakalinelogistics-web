@extends('Client::layouts.app')

@section('title', 'Create New Order')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
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
                        <span class="ml-4 text-sm font-medium text-gray-500">Create Order</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
            Create New Order
        </h2>
        <p class="mt-1 text-sm text-gray-500">Fill in the details below to create a new delivery order.</p>
    </div>

    <form action="{{ route('client.orders.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Sender Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Sender Information</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700">Name *</label>
                    <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('sender_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sender_phone" class="block text-sm font-medium text-gray-700">Phone *</label>
                    <input type="tel" name="sender_phone" id="sender_phone" value="{{ old('sender_phone') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('sender_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="sender_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="sender_email" id="sender_email" value="{{ old('sender_email') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">
                    @error('sender_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="pickup_address" class="block text-sm font-medium text-gray-700">Pickup Address *</label>
                    <textarea name="pickup_address" id="pickup_address" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#C1666B] focus:border-[#C1666B] sm:text-sm">{{ old('pickup_address') }}</textarea>
                    @error('pickup_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Receiver Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Receiver Information</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="receiver_name" class="block text-sm font-medium text-gray-700">Name *</label>
                    <input type="text" name="receiver_name" id="receiver_name" value="{{ old('receiver_name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('receiver_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="receiver_phone" class="block text-sm font-medium text-gray-700">Phone *</label>
                    <input type="tel" name="receiver_phone" id="receiver_phone" value="{{ old('receiver_phone') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('receiver_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="receiver_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="receiver_email" id="receiver_email" value="{{ old('receiver_email') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('receiver_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="delivery_address" class="block text-sm font-medium text-gray-700">Delivery Address *</label>
                    <textarea name="delivery_address" id="delivery_address" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">{{ old('delivery_address') }}</textarea>
                    @error('delivery_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Item Details -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Item Details</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="item_description" class="block text-sm font-medium text-gray-700">Description *</label>
                    <textarea name="item_description" id="item_description" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">{{ old('item_description') }}</textarea>
                    @error('item_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="item_weight" class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                    <input type="number" name="item_weight" id="item_weight" step="0.01" value="{{ old('item_weight') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('item_weight')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="item_quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" name="item_quantity" id="item_quantity" value="{{ old('item_quantity', 1) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('item_quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Delivery Price (₦) *</label>
                    <input type="number" name="price" id="price" step="0.01" value="{{ old('price') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <select name="priority" id="priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="express" {{ old('priority') == 'express' ? 'selected' : '' }}>Express</option>
                    </select>
                    @error('priority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pickup_date" class="block text-sm font-medium text-gray-700">Pickup Date</label>
                    <input type="date" name="pickup_date" id="pickup_date" value="{{ old('pickup_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('pickup_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="delivery_date" class="block text-sm font-medium text-gray-700">Expected Delivery Date</label>
                    <input type="date" name="delivery_date" id="delivery_date" value="{{ old('delivery_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    @error('delivery_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="special_instructions" class="block text-sm font-medium text-gray-700">Special Instructions</label>
                    <textarea name="special_instructions" id="special_instructions" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm">{{ old('special_instructions') }}</textarea>
                    @error('special_instructions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('client.orders') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white brand-accent-bg brand-accent-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1666B]">
                Create Order
            </button>
        </div>
    </form>
</div>
@endsection
