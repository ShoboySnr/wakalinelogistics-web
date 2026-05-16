@extends('Admin::layout')

@section('title', 'Edit Subscription Plan')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.credits.plans') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Edit Subscription Plan</h1>
        </div>
        <p class="text-sm text-gray-500">Update plan details</p>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.credits.plans.update', $plan->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Plan Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (₦) *</label>
                <input type="number" name="price" value="{{ old('price', $plan->price) }}" required step="0.01" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>

            <!-- Credits -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Credits *</label>
                <input type="number" name="credits" value="{{ old('credits', $plan->credits) }}" required min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>

            <!-- Billing Cycle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle *</label>
                <select name="billing_cycle" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                    <option value="daily" {{ old('billing_cycle', $plan->billing_cycle) == 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ old('billing_cycle', $plan->billing_cycle) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ old('billing_cycle', $plan->billing_cycle) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                    <option value="one-time" {{ old('billing_cycle', $plan->billing_cycle) == 'one-time' ? 'selected' : '' }}>One-time</option>
                </select>
            </div>

            <!-- Validity Days -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Validity Days (Optional)</label>
                <input type="number" name="validity_days" value="{{ old('validity_days', $plan->validity_days) }}" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>

            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
        </div>

        <!-- Description -->
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">{{ old('description', $plan->description) }}</textarea>
        </div>

        <!-- Features -->
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Features (one per line)</label>
            <textarea name="features_text" rows="5"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500"
                placeholder="Priority support&#10;Free delivery insurance&#10;Dedicated account manager">{{ old('features_text', is_array($plan->features) ? implode("\n", $plan->features) : '') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Enter each feature on a new line</p>
        </div>

        <!-- Checkboxes -->
        <div class="mt-6 space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Active (visible to clients)</span>
            </label>

            <label class="flex items-center">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Featured (highlight as popular)</span>
            </label>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Update Plan
            </button>
            <a href="{{ route('admin.credits.plans') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection
