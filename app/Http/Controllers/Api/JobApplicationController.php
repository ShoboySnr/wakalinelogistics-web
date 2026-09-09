<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class JobApplicationController extends Controller
{
    /**
     * Submit a job application
     */
    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_type' => 'required|string|in:dispatch_rider,warehouse_staff,customer_service,partner_rider,foot_soldier',
            'full_name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'phone' => 'required|string|min:10|max:20|regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            'email' => 'required_if:job_type,partner_rider,foot_soldier|nullable|email:rfc,dns|max:255',
            'address' => 'required|string|min:10|max:500',
            'age' => 'required|integer|min:18|max:60',
            'license_number' => 'nullable|string|max:100',
            'experience_years' => 'nullable|string|max:50',
            'previous_work' => 'nullable|string|max:2000',
            'availability' => 'nullable|string|max:100',
            'why_join' => 'nullable|string|max:2000',
            'owns_vehicle' => 'nullable|boolean',
            'vehicle_type' => 'nullable|string|max:100',
            'vehicle_registration' => 'nullable|string|max:100',
            'coverage_areas' => 'nullable|string|max:1000',
            'has_smartphone' => 'nullable|boolean',
            'guarantor1_full_name' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|min:2|max:255',
            'guarantor1_phone' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|min:10|max:20',
            'guarantor1_relationship' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|max:100',
            'guarantor1_occupation' => 'nullable|string|max:150',
            'guarantor1_residential_address' => 'nullable|string|max:500',
            'guarantor2_full_name' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|min:2|max:255|different:guarantor1_full_name',
            'guarantor2_phone' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|min:10|max:20|different:guarantor1_phone',
            'guarantor2_relationship' => 'required_if:job_type,partner_rider,foot_soldier|nullable|string|max:100',
            'guarantor2_occupation' => 'nullable|string|max:150',
            'guarantor2_residential_address' => 'nullable|string|max:500',
        ], [
            'job_type.required' => 'Job type is required',
            'job_type.in' => 'Invalid job type selected',
            'full_name.required' => 'Full name is required',
            'full_name.min' => 'Full name must be at least 2 characters',
            'full_name.regex' => 'Full name can only contain letters, spaces, hyphens, and periods',
            'phone.required' => 'Phone number is required',
            'phone.min' => 'Phone number must be at least 10 digits',
            'phone.regex' => 'Please enter a valid phone number',
            'email.required_if' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'address.required' => 'Address is required',
            'address.min' => 'Address must be at least 10 characters',
            'age.required' => 'Age is required',
            'age.min' => 'You must be at least 18 years old',
            'age.max' => 'Age cannot exceed 60 years',
            'age.integer' => 'Age must be a valid number',
            'guarantor1_full_name.required_if' => "The first guarantor's name is required",
            'guarantor1_phone.required_if' => "The first guarantor's phone number is required",
            'guarantor1_relationship.required_if' => 'Tell us how you know your first guarantor',
            'guarantor2_full_name.required_if' => "The second guarantor's name is required",
            'guarantor2_phone.required_if' => "The second guarantor's phone number is required",
            'guarantor2_relationship.required_if' => 'Tell us how you know your second guarantor',
            'guarantor2_full_name.different' => 'Your two guarantors must be different people',
            'guarantor2_phone.different' => 'Each guarantor needs their own phone number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = JobApplication::create($validator->validated());

            // TODO: Send email notification to admin
            // TODO: Send confirmation email to applicant

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully! We will review your application and get back to you within 48 hours.',
                'application_id' => $application->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Job application submission failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application. Please try again later.',
            ], 500);
        }
    }
}
