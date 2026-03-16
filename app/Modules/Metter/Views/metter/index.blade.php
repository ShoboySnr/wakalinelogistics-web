@extends('Admin::layout')

@section('title', 'Metter Management')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Metter Product Management</h1>
        <p class="text-sm text-gray-500 mt-1">Configure Metter delivery product features and settings</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto">
            <button type="button" onclick="showTab('features')" id="tab-features" class="metter-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Features
            </button>
            <button type="button" onclick="showTab('configurations')" id="tab-configurations" class="metter-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Configurations
            </button>
        </nav>
    </div>

    <!-- Features Tab -->
    <div id="content-features" class="tab-content hidden">
        <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-lg font-semibold text-gray-900">Metter Features</h2>
            <a href="{{ route('admin.metter.features.create') }}" class="px-4 py-2 text-center text-white rounded-md brand-accent-bg brand-accent-hover whitespace-nowrap">
                Add New Feature
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($features as $feature)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($feature->icon)
                                    <span class="mr-2">{{ $feature->icon }}</span>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $feature->name }}</div>
                                    @if($feature->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($feature->description, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.metter.features.toggle', $feature->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $feature->is_enabled ? 'brand-accent-bg' : 'bg-gray-200' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $feature->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4">
                            @if($feature->is_premium)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Premium</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Standard</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($feature->price)
                                ₦{{ number_format($feature->price, 2) }}
                            @else
                                <span class="text-gray-400">Free</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $feature->sort_order }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('admin.metter.features.edit', $feature->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <form action="{{ route('admin.metter.features.delete', $feature->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this feature?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            <p class="mt-2">No features configured yet</p>
                            <a href="{{ route('admin.metter.features.create') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-900">Add your first feature</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Configurations Tab -->
    <div id="content-configurations" class="tab-content hidden">
        <form method="POST" action="{{ route('admin.metter.config.update') }}">
            @csrf
            @method('PUT')

            @foreach($configurations as $category => $configs)
            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">{{ ucfirst($category) }} Configuration</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @foreach($configs as $config)
                    <div>
                        <label for="config-{{ $config->key }}" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ ucwords(str_replace('_', ' ', str_replace($category . '_', '', $config->key))) }}
                            @if($config->description)
                                <span class="text-xs text-gray-500 font-normal">({{ $config->description }})</span>
                            @endif
                        </label>
                        @if($config->type === 'boolean')
                            <select name="configurations[{{ $config->key }}]" id="config-{{ $config->key }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                                <option value="1" {{ $config->value == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ $config->value == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        @elseif($config->type === 'json')
                            <textarea name="configurations[{{ $config->key }}]" 
                                      id="config-{{ $config->key }}"
                                      rows="4"
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 font-mono text-sm"
                                      placeholder='{"key": "value"}'>{{ $config->value }}</textarea>
                        @else
                            <input type="{{ $config->type === 'number' ? 'number' : 'text' }}" 
                                   name="configurations[{{ $config->key }}]" 
                                   id="config-{{ $config->key }}"
                                   step="{{ $config->type === 'number' ? '0.01' : '' }}"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                                   value="{{ $config->value }}">
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                    Save Configurations
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.querySelectorAll('.metter-tab').forEach(tab => {
        tab.classList.remove('border-pink-500', 'text-pink-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add('border-pink-500', 'text-pink-600');
}

document.addEventListener('DOMContentLoaded', function() {
    showTab('features');
});
</script>
@endsection
