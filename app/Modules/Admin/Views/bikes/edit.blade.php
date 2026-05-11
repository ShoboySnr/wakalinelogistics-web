@extends('Admin::layout')

@section('title', 'Edit Bike')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <a href="{{ route('admin.bikes') }}" class="brand-accent-text text-sm font-medium" style="transition: color 0.2s ease;" onmouseover="this.style.color='#a8555a';" onmouseout="this.style.color='#C1666B';">
            ← Back to Bikes
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-900">Edit Bike - {{ $bike->bike_number }}</h2>
        </div>
        
        <form method="POST" action="{{ route('admin.bikes.update', $bike->id) }}" enctype="multipart/form-data" class="px-6 py-6">
        @csrf
        @method('PUT')

            <!-- Basic Information -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bike Number *</label>
                    <input type="text" name="bike_number" value="{{ old('bike_number', $bike->bike_number) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    @error('bike_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number *</label>
                    <input type="text" name="plate_number" value="{{ old('plate_number', $bike->plate_number) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    @error('plate_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $bike->brand) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ old('model', $bike->model) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" name="color" value="{{ old('color', $bike->color) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" value="{{ old('year', $bike->year) }}" min="1900" max="{{ date('Y') + 1 }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Engine Number</label>
                    <input type="text" name="engine_number" value="{{ old('engine_number', $bike->engine_number) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chassis Number</label>
                    <input type="text" name="chassis_number" value="{{ old('chassis_number', $bike->chassis_number) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="active" {{ old('status', $bike->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ old('status', $bike->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="inactive" {{ old('status', $bike->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Rider</label>
                    <select name="assigned_rider_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <option value="">Not Assigned</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->id }}" {{ old('assigned_rider_id', $bike->assigned_rider_id) == $rider->id ? 'selected' : '' }}>
                                {{ $rider->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            </div>

            <!-- Documents -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Documents & Expiry Dates</h3>
            <div class="space-y-4">
                <!-- Registration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Registration Document</label>
                        @if($bike->registration_document)
                            <div class="mb-2 flex items-center justify-between">
                                <a href="{{ asset($bike->registration_document) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                    View Current Document
                                </a>
                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_registration_document" value="1" class="mr-1">
                                    Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="registration_document" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace (PDF, JPG, PNG, Max 5MB)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Registration Expiry Date</label>
                        <input type="date" name="registration_expiry_date" value="{{ old('registration_expiry_date', $bike->registration_expiry_date?->format('Y-m-d')) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <!-- Insurance -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Insurance Document</label>
                        @if($bike->insurance_document)
                            <div class="mb-2 flex items-center justify-between">
                                <a href="{{ asset($bike->insurance_document) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                    View Current Document
                                </a>
                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_insurance_document" value="1" class="mr-1">
                                    Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="insurance_document" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Insurance Expiry Date</label>
                        <input type="date" name="insurance_expiry_date" value="{{ old('insurance_expiry_date', $bike->insurance_expiry_date?->format('Y-m-d')) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <!-- Roadworthiness -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Roadworthiness Certificate</label>
                        @if($bike->roadworthiness_document)
                            <div class="mb-2 flex items-center justify-between">
                                <a href="{{ asset($bike->roadworthiness_document) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                    View Current Document
                                </a>
                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_roadworthiness_document" value="1" class="mr-1">
                                    Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="roadworthiness_document" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Roadworthiness Expiry Date</label>
                        <input type="date" name="roadworthiness_expiry_date" value="{{ old('roadworthiness_expiry_date', $bike->roadworthiness_expiry_date?->format('Y-m-d')) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <!-- Hackney Permit -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hackney Permit</label>
                        @if($bike->hackney_permit_document)
                            <div class="mb-2 flex items-center justify-between">
                                <a href="{{ asset($bike->hackney_permit_document) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                    View Current Document
                                </a>
                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_hackney_permit_document" value="1" class="mr-1">
                                    Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="hackney_permit_document" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hackney Permit Expiry Date</label>
                        <input type="date" name="hackney_permit_expiry_date" value="{{ old('hackney_permit_expiry_date', $bike->hackney_permit_expiry_date?->format('Y-m-d')) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <!-- Vehicle License -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle License</label>
                        @if($bike->vehicle_license_document)
                            <div class="mb-2 flex items-center justify-between">
                                <a href="{{ asset($bike->vehicle_license_document) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                    View Current Document
                                </a>
                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_vehicle_license_document" value="1" class="mr-1">
                                    Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="vehicle_license_document" accept=".pdf,.jpg,.jpeg,.png" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle License Expiry Date</label>
                        <input type="date" name="vehicle_license_expiry_date" value="{{ old('vehicle_license_expiry_date', $bike->vehicle_license_expiry_date?->format('Y-m-d')) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>
            </div>
            </div>

            <!-- Maintenance -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Maintenance Schedule</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Maintenance Date</label>
                    <input type="date" name="last_maintenance_date" value="{{ old('last_maintenance_date', $bike->last_maintenance_date?->format('Y-m-d')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Next Maintenance Date</label>
                    <input type="date" name="next_maintenance_date" value="{{ old('next_maintenance_date', $bike->next_maintenance_date?->format('Y-m-d')) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
            </div>
            </div>

            <!-- Stickers/Permits -->
            <div class="mb-8">
                <div class="flex justify-between items-center my-4 pb-2 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Stickers & Permits</h3>
                    <button type="button" onclick="addStickerPermit()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        + Add Sticker/Permit
                    </button>
                </div>
                <div id="stickersContainer" class="space-y-4">
                    @if($bike->stickers_permits && is_array($bike->stickers_permits))
                        @foreach($bike->stickers_permits as $index => $sticker)
                            <div class="p-4 border border-gray-200 rounded-lg relative sticker-item">
                                <button type="button" onclick="this.closest('.sticker-item').remove()" class="absolute top-2 right-2 text-red-600 hover:text-red-800">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Sticker/Permit Name</label>
                                        <input type="text" name="stickers[{{ $index }}][name]" value="{{ old('stickers.'.$index.'.name', $sticker['name'] ?? '') }}" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                        <input type="text" name="stickers[{{ $index }}][serial_number]" value="{{ old('stickers.'.$index.'.serial_number', $sticker['serial_number'] ?? '') }}" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                                        <input type="date" name="stickers[{{ $index }}][expiry_date]" value="{{ old('stickers.'.$index.'.expiry_date', $sticker['expiry_date'] ?? '') }}" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Document</label>
                                        @if(isset($sticker['document']) && $sticker['document'])
                                            <div class="mb-2 flex items-center justify-between">
                                                <a href="{{ asset($sticker['document']) }}" target="_blank" class="text-sm brand-accent-text hover:underline">
                                                    View Current Document
                                                </a>
                                                <label class="flex items-center text-xs text-red-600 cursor-pointer">
                                                    <input type="checkbox" name="stickers[{{ $index }}][remove_document]" value="1" class="mr-1">
                                                    Remove
                                                </label>
                                            </div>
                                        @endif
                                        <input type="file" name="stickers[{{ $index }}][document]" accept=".pdf,.jpg,.jpeg,.png" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                                        <p class="mt-1 text-xs text-gray-500">Upload new file to replace</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Additional Notes</h3>
                <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('notes', $bike->notes) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.bikes') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                    Update Bike
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let stickerIndex = {{ $bike->stickers_permits ? count($bike->stickers_permits) : 0 }};

function addStickerPermit() {
    const container = document.getElementById('stickersContainer');
    const div = document.createElement('div');
    div.className = 'p-4 border border-gray-200 rounded-lg relative sticker-item';
    div.innerHTML = `
        <button type="button" onclick="this.closest('.sticker-item').remove()" class="absolute top-2 right-2 text-red-600 hover:text-red-800">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sticker/Permit Name</label>
                <input type="text" name="stickers[${stickerIndex}][name]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="stickers[${stickerIndex}][serial_number]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                <input type="date" name="stickers[${stickerIndex}][expiry_date]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Document</label>
                <input type="file" name="stickers[${stickerIndex}][document]" accept=".pdf,.jpg,.jpeg,.png" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG (Max 5MB)</p>
            </div>
        </div>
    `;
    container.appendChild(div);
    stickerIndex++;
}
</script>
@endsection
