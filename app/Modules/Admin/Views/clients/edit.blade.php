@extends('Admin::layout')

@section('title', 'Edit Client')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('admin.clients') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Edit Client</h1>
        </div>
        <p class="text-sm text-gray-500">Update client information and pickup address</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.clients.update', $client->id) }}">
            @method('PUT')
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information Section -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Basic Information</h3>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Client Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Company Name
                    </label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $client->company_name) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('company_name') border-red-500 @enderror">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Person -->
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">
                        Contact Person
                    </label>
                    <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $client->contact_person) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('contact_person') border-red-500 @enderror">
                    @error('contact_person')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Business Type -->
                <div>
                    <label for="business_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Business Type
                    </label>
                    <select name="business_type" id="business_type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('business_type') border-red-500 @enderror">
                        <option value="">Select business type</option>
                        <option value="retail" {{ old('business_type', $client->business_type) == 'retail' ? 'selected' : '' }}>Retail</option>
                        <option value="wholesale" {{ old('business_type', $client->business_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                        <option value="ecommerce" {{ old('business_type', $client->business_type) == 'ecommerce' ? 'selected' : '' }}>E-commerce</option>
                        <option value="restaurant" {{ old('business_type', $client->business_type) == 'restaurant' ? 'selected' : '' }}>Restaurant</option>
                        <option value="pharmacy" {{ old('business_type', $client->business_type) == 'pharmacy' ? 'selected' : '' }}>Pharmacy</option>
                        <option value="logistics" {{ old('business_type', $client->business_type) == 'logistics' ? 'selected' : '' }}>Logistics</option>
                        <option value="other" {{ old('business_type', $client->business_type) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('business_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Information Section -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Contact Information</h3>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Primary Phone <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alternate Phone -->
                <div>
                    <label for="alternate_phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Alternate Phone
                    </label>
                    <input type="text" name="alternate_phone" id="alternate_phone" value="{{ old('alternate_phone', $client->alternate_phone) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('alternate_phone') border-red-500 @enderror">
                    @error('alternate_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Primary Email
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alternate Email -->
                <div>
                    <label for="alternate_email" class="block text-sm font-medium text-gray-700 mb-1">
                        Alternate Email
                    </label>
                    <input type="email" name="alternate_email" id="alternate_email" value="{{ old('alternate_email', $client->alternate_email) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('alternate_email') border-red-500 @enderror">
                    @error('alternate_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Website -->
                <div class="md:col-span-2">
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                        Website
                    </label>
                    <input type="url" name="website" id="website" value="{{ old('website', $client->website) }}" placeholder="https://example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('website') border-red-500 @enderror">
                    @error('website')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address Information Section -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Address Information</h3>
                </div>

                <!-- Pickup Address -->
                <div class="md:col-span-2">
                    <label for="pickup_address" class="block text-sm font-medium text-gray-700 mb-1">
                        Pickup Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="pickup_address" id="pickup_address" rows="2" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('pickup_address') border-red-500 @enderror">{{ old('pickup_address', $client->pickup_address) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">This address will be used as the default pickup location for orders from this client</p>
                    @error('pickup_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Business Address -->
                <div class="md:col-span-2">
                    <label for="business_address" class="block text-sm font-medium text-gray-700 mb-1">
                        Business Address
                    </label>
                    <textarea name="business_address" id="business_address" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('business_address') border-red-500 @enderror">{{ old('business_address', $client->business_address) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">If different from pickup address</p>
                    @error('business_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                        City
                    </label>
                    <input type="text" name="city" id="city" value="{{ old('city', $client->city) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('city') border-red-500 @enderror">
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- State -->
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">
                        State
                    </label>
                    <input type="text" name="state" id="state" value="{{ old('state', $client->state) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('state') border-red-500 @enderror">
                    @error('state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Business Details Section -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Business Details</h3>
                </div>

                <!-- Tax ID -->
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Tax ID / Business Registration Number
                    </label>
                    <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $client->tax_id) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('tax_id') border-red-500 @enderror">
                    @error('tax_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Terms -->
                <div>
                    <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">
                        Payment Terms
                    </label>
                    <select name="payment_terms" id="payment_terms"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('payment_terms') border-red-500 @enderror">
                        <option value="prepaid" {{ old('payment_terms', $client->payment_terms) == 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                        <option value="postpaid" {{ old('payment_terms', $client->payment_terms) == 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                        <option value="credit_30" {{ old('payment_terms', $client->payment_terms) == 'credit_30' ? 'selected' : '' }}>Net 30 Days</option>
                        <option value="credit_60" {{ old('payment_terms', $client->payment_terms) == 'credit_60' ? 'selected' : '' }}>Net 60 Days</option>
                    </select>
                    @error('payment_terms')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Credit Limit -->
                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-gray-700 mb-1">
                        Credit Limit (₦)
                    </label>
                    <input type="number" step="0.01" name="credit_limit" id="credit_limit" value="{{ old('credit_limit', $client->credit_limit) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('credit_limit') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Maximum credit allowed for this client</p>
                    @error('credit_limit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Onboarded Date -->
                <div>
                    <label for="onboarded_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Onboarded Date
                    </label>
                    <input type="date" name="onboarded_date" id="onboarded_date" value="{{ old('onboarded_date', $client->onboarded_date?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('onboarded_date') border-red-500 @enderror">
                    @error('onboarded_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Additional Information Section -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b-2 border-gray-200">Additional Information</h3>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Internal Notes
                    </label>
                    <textarea name="notes" id="notes" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('notes') border-red-500 @enderror">{{ old('notes', $client->notes) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Internal notes about this client (not visible to client)</p>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Special Instructions -->
                <div class="md:col-span-2">
                    <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-1">
                        Special Instructions
                    </label>
                    <textarea name="special_instructions" id="special_instructions" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 @error('special_instructions') border-red-500 @enderror">{{ old('special_instructions', $client->special_instructions) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Any special handling or delivery instructions for this client's orders</p>
                    @error('special_instructions')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="ml-2 text-sm text-gray-700">Active (Client can be selected when creating orders)</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                    Update Client
                </button>
                <a href="{{ route('admin.clients') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
