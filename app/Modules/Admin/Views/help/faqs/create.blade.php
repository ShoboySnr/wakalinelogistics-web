@extends('Admin::layout')

@section('title', 'Create FAQ')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.help.faqs') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Create FAQ</h1>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.help.faqs.store') }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Question *</label>
                <input type="text" name="question" value="{{ old('question') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500"
                    placeholder="Enter the FAQ question">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Answer *</label>
                <textarea name="answer" rows="6" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500"
                    placeholder="Enter the detailed answer">{{ old('answer') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <input type="text" name="category" value="{{ old('category', 'General') }}" required
                        list="categories-list"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500"
                        placeholder="e.g. General, Orders, Payments">
                    <datalist id="categories-list">
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}">
                        @endforeach
                        <option value="General">
                        <option value="Orders">
                        <option value="Payments">
                        <option value="Account">
                        <option value="Credits">
                        <option value="Delivery">
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                </div>
            </div>

            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                <span class="ml-2 text-sm text-gray-700">Active (visible to clients)</span>
            </label>
        </div>

        <div class="mt-8 flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Create FAQ
            </button>
            <a href="{{ route('admin.help.faqs') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
