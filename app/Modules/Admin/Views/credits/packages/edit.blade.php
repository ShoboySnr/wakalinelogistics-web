@extends('Admin::layout')

@section('title', 'Edit Credit Package')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.credits.packages') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Edit Credit Package</h1>
        </div>
    </div>

    <form action="{{ route('admin.credits.packages.update', $package->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Package Name *</label>
                <input type="text" name="name" value="{{ old('name', $package->name) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (₦) *</label>
                <input type="number" name="price" value="{{ old('price', $package->price + 0) }}" required step="any" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Credits *</label>
                <input type="number" name="credits" value="{{ old('credits', $package->credits) }}" required min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus Credits</label>
                <input type="number" name="bonus_credits" value="{{ old('bonus_credits', $package->bonus_credits) }}" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Validity Days (Optional)</label>
                <input type="number" name="validity_days" value="{{ old('validity_days', $package->validity_days) }}" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $package->sort_order) }}" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
            </div>
        </div>
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">{{ old('description', $package->description) }}</textarea>
        </div>
        <div class="mt-6 space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $package->is_popular) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Popular (best value badge)</span>
            </label>
        </div>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">Update Package</button>
            <a href="{{ route('admin.credits.packages') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</a>
        </div>
    </form>
</div>
@endsection
