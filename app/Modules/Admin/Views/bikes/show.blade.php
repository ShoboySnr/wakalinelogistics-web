@extends('Admin::layout')

@section('title', 'Bike Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.bikes') }}" class="hover:text-gray-900">Bikes</a>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900">{{ $bike->bike_number }}</span>
        </div>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $bike->bike_number }}</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.bikes.edit', $bike->id) }}" class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition-colors">
                    Edit Bike
                </a>
                @if($bike->assignedRider)
                    <form method="POST" action="{{ route('admin.bikes.unassign-rider', $bike->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors">
                            Unassign Rider
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Document Status Alert -->
    @if($bike->hasExpiredDocuments())
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Expired Documents</h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($bike->getExpiredDocuments() as $doc)
                            <li>{{ $doc['name'] }} expired {{ $doc['days_overdue'] }} days ago ({{ $doc['expiry_date']->format('M d, Y') }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @elseif($bike->hasExpiringDocuments(30))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Documents Expiring Soon</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($bike->getExpiringDocuments(30) as $doc)
                            <li>{{ $doc['name'] }} expires in {{ $doc['days_remaining'] }} days ({{ $doc['expiry_date']->format('M d, Y') }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bike Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->bike_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Plate Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->plate_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Brand</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->brand ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Model</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->model ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Color</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->color ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Year</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->year ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Engine Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->engine_number ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Chassis Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->chassis_number ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($bike->status == 'active')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @elseif($bike->status == 'maintenance')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Maintenance</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Documents -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Documents</h2>
                <div class="space-y-4">
                    @php
                        $documents = [
                            ['name' => 'Registration', 'file' => $bike->registration_document, 'expiry' => $bike->registration_expiry_date],
                            ['name' => 'Insurance', 'file' => $bike->insurance_document, 'expiry' => $bike->insurance_expiry_date],
                            ['name' => 'Roadworthiness', 'file' => $bike->roadworthiness_document, 'expiry' => $bike->roadworthiness_expiry_date],
                            ['name' => 'Hackney Permit', 'file' => $bike->hackney_permit_document, 'expiry' => $bike->hackney_permit_expiry_date],
                            ['name' => 'Vehicle License', 'file' => $bike->vehicle_license_document, 'expiry' => $bike->vehicle_license_expiry_date],
                        ];
                    @endphp

                    @foreach($documents as $doc)
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">{{ $doc['name'] }}</h3>
                            @if($doc['expiry'])
                                <p class="text-sm text-gray-500 mt-1">
                                    Expires: {{ $doc['expiry']->format('M d, Y') }}
                                    @if($doc['expiry'] < now())
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                    @elseif($doc['expiry'] <= now()->addDays(30))
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Expiring Soon</span>
                                    @else
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-sm text-gray-400 mt-1">No expiry date set</p>
                            @endif
                        </div>
                        @if($doc['file'])
                            <a href="{{ asset('storage/' . $doc['file']) }}" target="_blank" class="ml-4 px-3 py-1 bg-pink-600 text-white text-sm rounded-md hover:bg-pink-700">
                                View
                            </a>
                        @else
                            <span class="ml-4 px-3 py-1 bg-gray-100 text-gray-400 text-sm rounded-md">No File</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Maintenance -->
            @if($bike->last_maintenance_date || $bike->next_maintenance_date)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Maintenance Schedule</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($bike->last_maintenance_date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Maintenance</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->last_maintenance_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    @if($bike->next_maintenance_date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Next Maintenance</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->next_maintenance_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            <!-- Stickers/Permits -->
            @if($bike->stickers_permits && count($bike->stickers_permits) > 0)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Stickers & Permits</h2>
                <div class="space-y-4">
                    @foreach($bike->stickers_permits as $sticker)
                    @php
                        $expiryDate = isset($sticker['expiry_date']) ? \Carbon\Carbon::parse($sticker['expiry_date']) : null;
                        $isExpired = $expiryDate && $expiryDate < now();
                        $isExpiringSoon = $expiryDate && $expiryDate <= now()->addDays(30) && $expiryDate >= now();
                    @endphp
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $sticker['name'] ?? '' }}</h3>
                            @if($isExpired)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                            @elseif($isExpiringSoon)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Expiring Soon</span>
                            @elseif($expiryDate)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                            @endif
                        </div>
                        <dl class="space-y-2">
                            @if(!empty($sticker['serial_number']))
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Serial Number</dt>
                                <dd class="text-sm text-gray-900">{{ $sticker['serial_number'] }}</dd>
                            </div>
                            @endif
                            @if($expiryDate)
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Expiry Date</dt>
                                <dd class="text-sm text-gray-900">{{ $expiryDate->format('M d, Y') }}</dd>
                            </div>
                            @endif
                            @if(isset($sticker['document']) && $sticker['document'])
                            <div>
                                <dt class="text-xs font-medium text-gray-500 mb-1">Document</dt>
                                <dd>
                                    <a href="{{ asset($sticker['document']) }}" target="_blank" class="inline-flex items-center px-3 py-1 bg-pink-600 text-white text-xs rounded-md hover:bg-pink-700">
                                        View Document
                                    </a>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($bike->notes)
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $bike->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Assigned Rider -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Assigned Rider</h2>
                @if($bike->assignedRider)
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $bike->assignedRider->name }}</p>
                            <p class="text-sm text-gray-500">{{ $bike->assignedRider->email }}</p>
                            <p class="text-xs text-gray-400 mt-1">Since {{ $bike->assignment_date?->format('M d, Y') }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Not assigned to any rider</p>
                    <button onclick="document.getElementById('assignRiderModal').classList.remove('hidden')" class="mt-3 w-full px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700">
                        Assign Rider
                    </button>
                @endif
            </div>

            <!-- Metadata -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Metadata</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->creator->name ?? 'System' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->created_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bike->updated_at->format('M d, Y h:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Assign Rider Modal -->
<div id="assignRiderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Assign Rider</h3>
            <button onclick="document.getElementById('assignRiderModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('admin.bikes.assign-rider', $bike->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Rider</label>
                <select name="rider_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    <option value="">Choose a rider</option>
                    @foreach(\App\Modules\Admin\Models\Rider::all() as $rider)
                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('assignRiderModal').classList.add('hidden')" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition-colors">
                    Assign
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
