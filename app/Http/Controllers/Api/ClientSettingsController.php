<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientSettingsController extends Controller
{
    /**
     * Update client profile information
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'contact_person' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'alternate_phone' => 'sometimes|nullable|string|max:20',
            'alternate_email' => 'sometimes|nullable|email|max:255',
            'business_address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:100',
            'business_type' => 'sometimes|nullable|string|max:100',
            'tax_id' => 'sometimes|nullable|string|max:100',
            'website' => 'sometimes|nullable|url|max:255',
            'facebook_handle' => 'sometimes|nullable|string|max:255',
            'instagram_handle' => 'sometimes|nullable|string|max:255',
            'pickup_address' => 'sometimes|nullable|string|max:500',
            'pickup_contact_name' => 'sometimes|nullable|string|max:255',
            'pickup_contact_phone' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Map frontend field names to database column names
        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('company_name')) $updateData['company_name'] = $request->company_name;
        if ($request->has('contact_person')) $updateData['contact_person'] = $request->contact_person;
        if ($request->has('email')) $updateData['email'] = $request->email;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('alternate_phone')) $updateData['alternate_phone'] = $request->alternate_phone;
        if ($request->has('alternate_email')) $updateData['alternate_email'] = $request->alternate_email;
        if ($request->has('business_address')) $updateData['business_address'] = $request->business_address;
        if ($request->has('city')) $updateData['city'] = $request->city;
        if ($request->has('state')) $updateData['state'] = $request->state;
        if ($request->has('business_type')) $updateData['business_type'] = $request->business_type;
        if ($request->has('tax_id')) $updateData['tax_id'] = $request->tax_id;
        if ($request->has('website')) $updateData['website'] = $request->website;
        if ($request->has('facebook_handle')) $updateData['facebook_handle'] = $request->facebook_handle;
        if ($request->has('instagram_handle')) $updateData['instagram_handle'] = $request->instagram_handle;
        if ($request->has('pickup_address')) $updateData['default_pickup_address'] = $request->pickup_address;
        if ($request->has('pickup_contact_name')) $updateData['default_pickup_contact_name'] = $request->pickup_contact_name;
        if ($request->has('pickup_contact_phone')) $updateData['default_pickup_contact_phone'] = $request->pickup_contact_phone;

        $user->update($updateData);
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ], 200);
    }

    /**
     * Update client pickup details
     */
    public function updatePickupDetails(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'pickup_address' => 'required|string|max:500',
            'pickup_contact_name' => 'required|string|max:255',
            'pickup_contact_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'default_pickup_address' => $request->pickup_address,
            'default_pickup_contact_name' => $request->pickup_contact_name,
            'default_pickup_contact_phone' => $request->pickup_contact_phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup details updated successfully',
            'data' => [
                'pickup_address' => $user->default_pickup_address,
                'pickup_contact_name' => $user->default_pickup_contact_name,
                'pickup_contact_phone' => $user->default_pickup_contact_phone,
            ]
        ], 200);
    }

    /**
     * Get client pickup details
     */
    public function getPickupDetails()
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pickup_address' => $user->default_pickup_address,
                'pickup_contact_name' => $user->default_pickup_contact_name,
                'pickup_contact_phone' => $user->default_pickup_contact_phone,
            ]
        ], 200);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ], 200);
    }

    /**
     * Update theme preference
     */
    public function updateThemePreference(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'theme' => 'required|in:light,dark',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'theme_preference' => $request->theme
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Theme preference updated successfully',
            'data' => [
                'theme' => $user->theme_preference
            ]
        ], 200);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'order_updates' => 'sometimes|boolean',
            'marketing_emails' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $preferences = $user->notification_preferences ?? [];
        
        if ($request->has('email_notifications')) {
            $preferences['email_notifications'] = $request->email_notifications;
        }
        if ($request->has('sms_notifications')) {
            $preferences['sms_notifications'] = $request->sms_notifications;
        }
        if ($request->has('order_updates')) {
            $preferences['order_updates'] = $request->order_updates;
        }
        if ($request->has('marketing_emails')) {
            $preferences['marketing_emails'] = $request->marketing_emails;
        }

        $user->update([
            'notification_preferences' => $preferences
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => $preferences
        ], 200);
    }

    /**
     * Enable/Disable API access
     */
    public function toggleApiAccess(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $enabled = $request->enabled;

        // If enabling and no API key exists, generate one
        if ($enabled && !$user->api_key) {
            $apiKey = 'waka_' . Str::random(40);
            $user->update([
                'api_enabled' => true,
                'api_key' => $apiKey,
            ]);
        } else {
            $user->update([
                'api_enabled' => $enabled,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'API access enabled' : 'API access disabled',
            'data' => [
                'api_enabled' => $user->api_enabled,
                'api_key' => $enabled ? $user->api_key : null,
            ]
        ], 200);
    }

    /**
     * Get API access status and key
     */
    public function getApiAccess()
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'api_enabled' => $user->api_enabled ?? false,
                'api_key' => $user->api_enabled ? $user->api_key : null,
            ]
        ], 200);
    }

    /**
     * Regenerate API key
     */
    public function regenerateApiKey()
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$user->api_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'API access is not enabled'
            ], 422);
        }

        $apiKey = 'waka_' . Str::random(40);
        
        $user->update([
            'api_key' => $apiKey,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API key regenerated successfully',
            'data' => [
                'api_key' => $apiKey,
            ]
        ], 200);
    }

    /**
     * Toggle Two-Factor Authentication
     */
    public function toggle2FA(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'method' => 'nullable|in:email,authenticator',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $enabled = $request->enabled;
        $method = $request->method ?? 'email';

        // If disabling, clear all 2FA data
        if (!$enabled) {
            $user->update([
                'two_factor_enabled' => false,
                'two_factor_method' => 'email',
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => '2FA disabled successfully',
                'data' => [
                    'two_factor_enabled' => false,
                ]
            ], 200);
        }

        // Update 2FA status and method
        $user->update([
            'two_factor_enabled' => $enabled,
            'two_factor_method' => $method,
        ]);

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully',
            'data' => [
                'two_factor_enabled' => $user->two_factor_enabled,
                'two_factor_method' => $user->two_factor_method,
            ]
        ], 200);
    }

    /**
     * Setup authenticator 2FA - Generate QR code
     */
    public function setupAuthenticator(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $twoFactorService = new \App\Services\TwoFactorAuthService();
        
        // Generate new secret
        $secret = $twoFactorService->generateSecret();
        
        // Store temporarily (not confirmed yet)
        $user->update([
            'two_factor_secret' => $secret,
        ]);

        // Generate QR code URL
        $qrCodeUrl = $twoFactorService->getQRCodeUrl($user->email, $secret);
        $provisioningUri = $twoFactorService->getProvisioningUri($user->email, $secret);

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
                'provisioning_uri' => $provisioningUri,
            ]
        ], 200);
    }

    /**
     * Confirm authenticator setup with verification code
     */
    public function confirmAuthenticator(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'No authenticator setup in progress'
            ], 400);
        }

        $twoFactorService = new \App\Services\TwoFactorAuthService();
        
        // Verify the code
        if (!$twoFactorService->verifyCode($user->two_factor_secret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 401);
        }

        // Generate recovery codes
        $recoveryCodes = $twoFactorService->generateRecoveryCodes();

        // Confirm setup
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => 'authenticator',
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Authenticator setup completed successfully',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ]
        ], 200);
    }

    /**
     * Get current 2FA settings
     */
    public function get2FASettings(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'two_factor_enabled' => $user->two_factor_enabled,
                'two_factor_method' => $user->two_factor_method ?? 'email',
                'has_recovery_codes' => !empty($user->two_factor_recovery_codes),
                'recovery_codes_count' => count($user->two_factor_recovery_codes ?? []),
            ]
        ], 200);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$user->two_factor_enabled || $user->two_factor_method !== 'authenticator') {
            return response()->json([
                'success' => false,
                'message' => 'Authenticator 2FA is not enabled'
            ], 400);
        }

        $twoFactorService = new \App\Services\TwoFactorAuthService();
        $recoveryCodes = $twoFactorService->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recovery codes regenerated successfully',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ]
        ], 200);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        if (!$user instanceof Client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'theme_preference' => 'sometimes|in:light,dark',
            'language' => 'sometimes|string|max:50',
            'timezone' => 'sometimes|string|max:100',
            'date_format' => 'sometimes|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['theme_preference', 'language', 'timezone', 'date_format']));

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => [
                'theme_preference' => $user->theme_preference,
                'language' => $user->language,
                'timezone' => $user->timezone,
                'date_format' => $user->date_format,
            ]
        ], 200);
    }
}
