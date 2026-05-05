@extends('Admin::layout')

@section('title', 'Credit Settings')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Credit Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure credit system behavior</p>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('admin.credits.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($settings as $group => $groupSettings)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 capitalize">{{ str_replace('_', ' ', $group) }} Settings</h2>
            
            <div class="space-y-4">
                @foreach($groupSettings as $setting)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                    </label>
                    
                    @if($setting->description)
                    <p class="text-xs text-gray-500 mb-2">{{ $setting->description }}</p>
                    @endif

                    @if($setting->type === 'boolean')
                        <label class="flex items-center">
                            <input type="checkbox" name="{{ $setting->key }}" value="1" 
                                {{ $setting->value ? 'checked' : '' }}
                                class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm text-gray-700">Enable</span>
                        </label>
                    @elseif($setting->type === 'integer')
                        <input type="number" name="{{ $setting->key }}" value="{{ $setting->value }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                    @elseif($setting->type === 'decimal')
                        <input type="number" name="{{ $setting->key }}" value="{{ $setting->value }}" step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                    @else
                        <input type="text" name="{{ $setting->key }}" value="{{ $setting->value }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                Save Settings
            </button>
            <a href="{{ route('admin.credits') }}" class="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
