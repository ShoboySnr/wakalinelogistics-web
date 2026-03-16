@extends('Admin::layout')

@section('title', 'Settings')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure general system preferences and company information</p>
    </div>

    @if($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($settings->isEmpty())
    <div class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No settings configured</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by initializing default settings.</p>
        <div class="mt-6">
            <a href="{{ route('admin.settings') }}?initialize=true" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white brand-accent-bg brand-accent-hover">
                Initialize Default Settings
            </a>
        </div>
    </div>
    @else
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <!-- General Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">General Settings</h2>
                <p class="text-sm text-gray-500 mt-1">General system configuration and company information</p>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($settings->has('general'))
                        @foreach($settings['general'] as $setting)
                        <div>
                            <label for="setting-{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ ucwords(str_replace('_', ' ', str_replace('general_', '', $setting->key))) }}
                            </label>
                            @if($setting->type === 'boolean')
                                <select name="settings[{{ $setting->key }}]" id="setting-{{ $setting->key }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                                    <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            @else
                                <input type="{{ $setting->type === 'number' ? 'number' : 'text' }}" 
                                       name="settings[{{ $setting->key }}]" 
                                       id="setting-{{ $setting->key }}"
                                       step="{{ $setting->type === 'number' ? '0.01' : '' }}"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                                       value="{{ $setting->value }}">
                            @endif
                            @if($setting->description)
                                <p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500">No general settings configured yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover" style="transition: background-color 0.2s ease;">
                Save Settings
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
