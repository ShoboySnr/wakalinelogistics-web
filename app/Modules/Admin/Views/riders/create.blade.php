@extends('Admin::layout')

@section('title', 'Add New Rider')

@section('content')
<div class="px-4 sm:px-6 lg:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Add New Rider</h1>
        <p class="text-sm text-gray-500 mt-1">Create a new delivery rider</p>
    </div>

    @if($errors->any())
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
        <ul class="list-disc list-inside text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <form method="POST" action="{{ route('admin.riders.store') }}" enctype="multipart/form-data" class="px-6 py-4">
            @csrf
            
            <!-- Basic Information -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="rider_photo" class="block text-sm font-medium text-gray-700 mb-2">
                            Profile Picture
                        </label>
                        <input type="file" name="rider_photo" id="rider_photo" accept="image/*"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, or GIF (Max 2MB)</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('name') }}">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('email') }}">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" id="phone" required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('phone') }}" placeholder="+234 810 000 0000">
                </div>

                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                        Age
                    </label>
                    <input type="number" name="age" id="age" min="18" max="100"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('age') }}" placeholder="25">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div>
                    <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Vehicle Type
                    </label>
                    <select name="vehicle_type" id="vehicle_type"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">Select vehicle type</option>
                        <option value="bike" {{ old('vehicle_type') == 'bike' ? 'selected' : '' }}>Bike</option>
                        <option value="car" {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>Car</option>
                        <option value="van" {{ old('vehicle_type') == 'van' ? 'selected' : '' }}>Van</option>
                    </select>
                </div>

                <div>
                    <label for="vehicle_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Vehicle Number
                    </label>
                    <input type="text" name="vehicle_number" id="vehicle_number"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('vehicle_number') }}" placeholder="ABC-123-XY">
                </div>

                <div>
                    <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                        License Number
                    </label>
                    <input type="text" name="license_number" id="license_number"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                           value="{{ old('license_number') }}">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Address
                    </label>
                    <textarea name="address" id="address" rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('address') }}</textarea>
                </div>
            </div>

            <!-- Rider Documents -->
            <div class="mt-8 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Rider Documents</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="rider_id_document" class="block text-sm font-medium text-gray-700 mb-2">
                            ID Document (NIN/Passport)
                        </label>
                        <input type="file" name="rider_id_document" id="rider_id_document" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF or Image (Max 5MB)</p>
                    </div>

                    <div>
                        <label for="driver_license_doc" class="block text-sm font-medium text-gray-700 mb-2">
                            Driver's License
                        </label>
                        <input type="file" name="driver_license_doc" id="driver_license_doc" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF or Image (Max 5MB)</p>
                    </div>

                    <div>
                        <label for="vehicle_registration" class="block text-sm font-medium text-gray-700 mb-2">
                            Vehicle Registration
                        </label>
                        <input type="file" name="vehicle_registration" id="vehicle_registration" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF or Image (Max 5MB)</p>
                    </div>

                    <div>
                        <label for="vehicle_insurance" class="block text-sm font-medium text-gray-700 mb-2">
                            Vehicle Insurance
                        </label>
                        <input type="file" name="vehicle_insurance" id="vehicle_insurance" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <p class="mt-1 text-xs text-gray-500">PDF or Image (Max 5MB)</p>
                    </div>
                </div>
            </div>

            <!-- Guarantor 1 Information -->
            <div class="mt-8 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Guarantor 1 Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="guarantor1_full_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name
                        </label>
                        <input type="text" name="guarantor1_full_name" id="guarantor1_full_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_full_name') }}">
                    </div>

                    <div>
                        <label for="guarantor1_dob" class="block text-sm font-medium text-gray-700 mb-2">
                            Date of Birth
                        </label>
                        <input type="date" name="guarantor1_dob" id="guarantor1_dob"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_dob') }}">
                    </div>

                    <div>
                        <label for="guarantor1_nationality" class="block text-sm font-medium text-gray-700 mb-2">
                            Nationality
                        </label>
                        <input type="text" name="guarantor1_nationality" id="guarantor1_nationality"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_nationality', 'Nigerian') }}">
                    </div>

                    <div>
                        <label for="guarantor1_occupation" class="block text-sm font-medium text-gray-700 mb-2">
                            Occupation
                        </label>
                        <input type="text" name="guarantor1_occupation" id="guarantor1_occupation"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_occupation') }}" placeholder="e.g., Teacher, Engineer">
                    </div>

                    <div>
                        <label for="guarantor1_nin" class="block text-sm font-medium text-gray-700 mb-2">
                            NIN
                        </label>
                        <input type="text" name="guarantor1_nin" id="guarantor1_nin"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_nin') }}">
                    </div>

                    <div>
                        <label for="guarantor1_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number
                        </label>
                        <input type="tel" name="guarantor1_phone" id="guarantor1_phone"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_phone') }}">
                    </div>

                    <div>
                        <label for="guarantor1_alt_phone1" class="block text-sm font-medium text-gray-700 mb-2">
                            Alternate Phone 1
                        </label>
                        <input type="tel" name="guarantor1_alt_phone1" id="guarantor1_alt_phone1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_alt_phone1') }}">
                    </div>

                    <div>
                        <label for="guarantor1_alt_phone2" class="block text-sm font-medium text-gray-700 mb-2">
                            Alternate Phone 2
                        </label>
                        <input type="tel" name="guarantor1_alt_phone2" id="guarantor1_alt_phone2"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_alt_phone2') }}">
                    </div>

                    <div>
                        <label for="guarantor1_relationship" class="block text-sm font-medium text-gray-700 mb-2">
                            Relationship to Rider
                        </label>
                        <input type="text" name="guarantor1_relationship" id="guarantor1_relationship"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_relationship') }}">
                    </div>

                    <div>
                        <label for="guarantor1_years_known" class="block text-sm font-medium text-gray-700 mb-2">
                            Years Known
                        </label>
                        <input type="number" name="guarantor1_years_known" id="guarantor1_years_known"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor1_years_known') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label for="guarantor1_residential_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Residential Address
                        </label>
                        <textarea name="guarantor1_residential_address" id="guarantor1_residential_address" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('guarantor1_residential_address') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label for="guarantor1_work_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Work Address
                        </label>
                        <textarea name="guarantor1_work_address" id="guarantor1_work_address" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('guarantor1_work_address') }}</textarea>
                    </div>
                </div>

                <!-- Guarantor 1 Documents -->
                <h4 class="text-md font-semibold text-gray-800 mt-6 mb-4">Guarantor 1 Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="guarantor1_id_document" class="block text-sm font-medium text-gray-700 mb-2">
                            ID Document
                        </label>
                        <input type="file" name="guarantor1_id_document" id="guarantor1_id_document" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor1_proof_of_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Proof of Address
                        </label>
                        <input type="file" name="guarantor1_proof_of_address" id="guarantor1_proof_of_address" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor1_employment_letter" class="block text-sm font-medium text-gray-700 mb-2">
                            Employment Letter
                        </label>
                        <input type="file" name="guarantor1_employment_letter" id="guarantor1_employment_letter" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor1_additional_doc" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Document
                        </label>
                        <input type="file" name="guarantor1_additional_doc" id="guarantor1_additional_doc" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>
            </div>

            <!-- Guarantor 2 Information -->
            <div class="mt-8 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Guarantor 2 Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="guarantor2_full_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name
                        </label>
                        <input type="text" name="guarantor2_full_name" id="guarantor2_full_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_full_name') }}">
                    </div>

                    <div>
                        <label for="guarantor2_dob" class="block text-sm font-medium text-gray-700 mb-2">
                            Date of Birth
                        </label>
                        <input type="date" name="guarantor2_dob" id="guarantor2_dob"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_dob') }}">
                    </div>

                    <div>
                        <label for="guarantor2_nationality" class="block text-sm font-medium text-gray-700 mb-2">
                            Nationality
                        </label>
                        <input type="text" name="guarantor2_nationality" id="guarantor2_nationality"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_nationality', 'Nigerian') }}">
                    </div>

                    <div>
                        <label for="guarantor2_occupation" class="block text-sm font-medium text-gray-700 mb-2">
                            Occupation
                        </label>
                        <input type="text" name="guarantor2_occupation" id="guarantor2_occupation"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_occupation') }}" placeholder="e.g., Teacher, Engineer">
                    </div>

                    <div>
                        <label for="guarantor2_nin" class="block text-sm font-medium text-gray-700 mb-2">
                            NIN
                        </label>
                        <input type="text" name="guarantor2_nin" id="guarantor2_nin"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_nin') }}">
                    </div>

                    <div>
                        <label for="guarantor2_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number
                        </label>
                        <input type="tel" name="guarantor2_phone" id="guarantor2_phone"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_phone') }}">
                    </div>

                    <div>
                        <label for="guarantor2_alt_phone1" class="block text-sm font-medium text-gray-700 mb-2">
                            Alternate Phone 1
                        </label>
                        <input type="tel" name="guarantor2_alt_phone1" id="guarantor2_alt_phone1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_alt_phone1') }}">
                    </div>

                    <div>
                        <label for="guarantor2_alt_phone2" class="block text-sm font-medium text-gray-700 mb-2">
                            Alternate Phone 2
                        </label>
                        <input type="tel" name="guarantor2_alt_phone2" id="guarantor2_alt_phone2"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_alt_phone2') }}">
                    </div>

                    <div>
                        <label for="guarantor2_relationship" class="block text-sm font-medium text-gray-700 mb-2">
                            Relationship to Rider
                        </label>
                        <input type="text" name="guarantor2_relationship" id="guarantor2_relationship"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_relationship') }}">
                    </div>

                    <div>
                        <label for="guarantor2_years_known" class="block text-sm font-medium text-gray-700 mb-2">
                            Years Known
                        </label>
                        <input type="number" name="guarantor2_years_known" id="guarantor2_years_known"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('guarantor2_years_known') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label for="guarantor2_residential_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Residential Address
                        </label>
                        <textarea name="guarantor2_residential_address" id="guarantor2_residential_address" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('guarantor2_residential_address') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label for="guarantor2_work_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Work Address
                        </label>
                        <textarea name="guarantor2_work_address" id="guarantor2_work_address" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('guarantor2_work_address') }}</textarea>
                    </div>
                </div>

                <!-- Guarantor 2 Documents -->
                <h4 class="text-md font-semibold text-gray-800 mt-6 mb-4">Guarantor 2 Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="guarantor2_id_document" class="block text-sm font-medium text-gray-700 mb-2">
                            ID Document
                        </label>
                        <input type="file" name="guarantor2_id_document" id="guarantor2_id_document" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor2_proof_of_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Proof of Address
                        </label>
                        <input type="file" name="guarantor2_proof_of_address" id="guarantor2_proof_of_address" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor2_employment_letter" class="block text-sm font-medium text-gray-700 mb-2">
                            Employment Letter
                        </label>
                        <input type="file" name="guarantor2_employment_letter" id="guarantor2_employment_letter" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label for="guarantor2_additional_doc" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Document
                        </label>
                        <input type="file" name="guarantor2_additional_doc" id="guarantor2_additional_doc" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>
            </div>

            <!-- Witness Information -->
            <div class="mt-8 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Witness Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="witness_full_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name
                        </label>
                        <input type="text" name="witness_full_name" id="witness_full_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('witness_full_name') }}">
                    </div>

                    <div>
                        <label for="witness_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number
                        </label>
                        <input type="tel" name="witness_phone" id="witness_phone"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('witness_phone') }}">
                    </div>

                    <div>
                        <label for="witness_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date
                        </label>
                        <input type="date" name="witness_date" id="witness_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"
                               value="{{ old('witness_date') }}">
                    </div>

                    <div>
                        <label for="witness_signature" class="block text-sm font-medium text-gray-700 mb-2">
                            Signature (Upload)
                        </label>
                        <input type="file" name="witness_signature" id="witness_signature" accept="image/*"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="witness_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Address
                        </label>
                        <textarea name="witness_address" id="witness_address" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">{{ old('witness_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Additional Files Section -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Additional Files (Optional)</h3>
                    <p class="text-sm text-gray-500 mt-1">Upload any other relevant documents</p>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="additional_file_1" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional File 1
                            </label>
                            <input type="file" name="additional_file_1" id="additional_file_1"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        </div>

                        <div>
                            <label for="additional_file_2" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional File 2
                            </label>
                            <input type="file" name="additional_file_2" id="additional_file_2"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        </div>

                        <div>
                            <label for="additional_file_3" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional File 3
                            </label>
                            <input type="file" name="additional_file_3" id="additional_file_3"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <p class="mt-1 text-xs text-gray-500">PDF, DOC, JPG, PNG (Max 5MB)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.riders') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-white rounded-md brand-accent-bg brand-accent-hover">
                    Create Rider
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
