<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('metter-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => 'user',
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('User registration error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Register a new client (self-service onboarding)
     */
    public function clientRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|max:255|unique:clients,email',
            'phone'             => 'required|string|max:20|unique:clients,phone',
            'password'          => 'required|string|min:8|confirmed',
            'company_name'      => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $client = Client::create([
                'name'                            => $request->name,
                'email'                           => $request->email,
                'phone'                           => $request->phone,
                'company_name'                    => $request->company_name,
                'password'                        => Hash::make($request->password),
                'is_active'                       => true,
                'dashboard_enabled'               => true,
                'pickup_address'                  => '',
                'email_verification_code'         => $code,
                'email_verification_expires_at'   => now()->addMinutes(30),
            ]);

            $this->sendVerificationEmail($client->email, $client->name, $code);

            return response()->json([
                'success'             => true,
                'message'             => 'Account created! Please check your email for a 6-digit verification code.',
                'requires_verification' => true,
                'data'                => [
                    'email' => $client->email,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Client registration error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify client email with OTP code
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        }

        if ($client->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email is already verified.'], 400);
        }

        if ($client->email_verification_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code.'], 400);
        }

        if (!$client->email_verification_expires_at || $client->email_verification_expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Code has expired. Please request a new one.'], 400);
        }

        $client->email_verified_at             = now();
        $client->email_verification_code       = null;
        $client->email_verification_expires_at = null;
        $client->save();

        // Credit 2,000 signup bonus credits (once only)
        if (!$client->signup_bonus_credited) {
            try {
                $credits = $client->getOrCreateCredits();
                $credits->addCredits(2000);
                $client->signup_bonus_credited = true;
                $client->save();
            } catch (\Exception $e) {
                Log::error('Signup bonus credit failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        try {
            $this->sendWelcomeEmail($client->email, $client->name);
        } catch (\Exception $e) {
            Log::error('Welcome email failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
        }

        $token = $client->createToken('wakaline-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified! Welcome to Waka Line — 2,000 credits have been added to your account.',
            'data'    => [
                'user' => [
                    'id'           => $client->id,
                    'name'         => $client->name,
                    'email'        => $client->email,
                    'phone'        => $client->phone,
                    'role'         => 'client',
                    'account_type' => 'client',
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Resend email verification code
     */
    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Email is required.'], 422);
        }

        $client = Client::where('email', $request->email)->whereNull('email_verified_at')->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Account not found or already verified.'], 404);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $client->email_verification_code       = $code;
        $client->email_verification_expires_at = now()->addMinutes(30);
        $client->save();

        $this->sendVerificationEmail($client->email, $client->name, $code);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
        ]);
    }

    private function sendVerificationEmail(string $email, string $name, string $code): void
    {
        // Sanitize name to ensure valid UTF-8 encoding
        $sanitizedName = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        
        Mail::send('emails.verify_email', ['name' => $sanitizedName, 'code' => $code], function ($message) use ($email) {
            $message->to($email)
                ->subject('Verify Your Waka Line Account — Get Code');
        });
    }

    private function sendPasswordResetEmail(string $email, string $name, string $code): void
    {
        // Sanitize name to ensure valid UTF-8 encoding
        $sanitizedName = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        
        Mail::send('emails.reset_password', ['name' => $sanitizedName, 'code' => $code], function ($message) use ($email) {
            $message->to($email)
                ->subject('Reset Your Waka Line Password — Get Code');
        });
    }

    private function sendWelcomeEmail(string $email, string $name): void
    {
        // Sanitize name to ensure valid UTF-8 encoding
        $sanitizedName = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        
        Mail::send('emails.welcome', ['name' => $sanitizedName], function ($message) use ($email) {
            $message->to($email)
                ->subject('Welcome to Waka Line Logistics — Your Account is Ready!');
        });
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Try to find user in users table first
        $user = User::where('email', $request->email)->first();
        $accountType = 'user';

        // If not found in users table, check clients table
        if (!$user) {
            $user = Client::where('email', $request->email)->first();
            $accountType = 'client';
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address'
            ], 401);
        }

        // For clients, check if they have dashboard access
        if ($accountType === 'client') {
            // Waitlist users: inactive with no password set yet
            if (!$user->is_active && empty($user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "You're on our waitlist! Your account will be activated when we launch. We'll send you an email when it's ready.",
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 401);
            }

            if (!$user->dashboard_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dashboard access is not enabled for your account. Please contact support.'
                ], 401);
            }

            if (!$user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not set up for dashboard access. Please contact support.'
                ], 401);
            }

            if (!$user->email_verified_at) {
                // Resend verification code so client can verify immediately
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->email_verification_code = $code;
                $user->email_verification_expires_at = now()->addMinutes(15);
                $user->save();
                $this->sendVerificationEmail($user->email, $user->name, $code);

                return response()->json([
                    'success' => false,
                    'requires_verification' => true,
                    'email' => $user->email,
                    'message' => 'Please verify your email address before logging in. A new verification code has been sent to your email.',
                ], 401);
            }
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password'
            ], 401);
        }

        // Check if 2FA is enabled for clients
        if ($accountType === 'client' && $user->two_factor_enabled) {
            $method = $user->two_factor_method ?? 'email';

            if ($method === 'email') {
                // Generate and send 2FA code via email
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->two_factor_code = $code;
                $user->two_factor_expires_at = now()->addMinutes(10);
                $user->save();

                try {
                    $this->send2FACode($user->email, $user->name, $code);
                } catch (\Exception $e) {
                    Log::error('2FA code email failed', ['client_id' => $user->id, 'error' => $e->getMessage()]);
                }

                return response()->json([
                    'success' => false,
                    'requires_2fa' => true,
                    'two_factor_method' => 'email',
                    'email' => $user->email,
                    'message' => 'A verification code has been sent to your email. Please enter it to complete login.',
                ], 200);
            } else {
                // Authenticator method
                return response()->json([
                    'success' => false,
                    'requires_2fa' => true,
                    'two_factor_method' => 'authenticator',
                    'email' => $user->email,
                    'message' => 'Please enter the code from your authenticator app to complete login.',
                ], 200);
            }
        }

        $token = $user->createToken('metter-app')->plainTextToken;

        // Update last login time for clients
        if ($accountType === 'client') {
            $user->last_login_at = now();
            $user->save();
        }

        // Determine role
        $role = 'user';
        if ($accountType === 'client') {
            $role = 'client';
        } elseif (isset($user->is_admin) && $user->is_admin) {
            $role = 'admin';
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role,
                    'account_type' => $accountType,
                ],
                'token' => $token
            ]
        ], 200);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
            ]
        ], 200);
    }

    /**
     * Send password reset link/OTP
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Clients use OTP-based reset
        $client = Client::where('email', $request->email)->first();
        if ($client) {
            // Waitlist users have no password yet
            if (!$client->is_active && empty($client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "You're on our waitlist! Your account hasn't been activated yet. You'll receive an email when we launch.",
                ], 403);
            }

            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            if (empty($client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not set up for dashboard access. Please contact support.',
                ], 403);
            }

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $client->email_verification_code = $code;
            $client->email_verification_expires_at = now()->addMinutes(15);
            $client->save();

            $this->sendPasswordResetEmail($client->email, $client->name, $code);

            return response()->json([
                'success' => true,
                'account_type' => 'client',
                'message' => 'A 6-digit password reset code has been sent to your email.',
            ], 200);
        }

        // Admin/staff users use token-based reset link
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $status = Password::sendResetLink($request->only('email'));
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'account_type' => 'user',
                    'message' => 'A password reset link has been sent to your email.',
                ], 200);
            }
            return response()->json([
                'success' => false,
                'message' => 'Unable to send reset link. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'No account found with this email address.',
        ], 404);
    }

    /**
     * Reset password (OTP for clients, token for users)
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // OTP-based reset for clients
        if ($request->filled('otp')) {
            $client = Client::where('email', $request->email)->first();
            if (!$client) {
                return response()->json(['success' => false, 'message' => 'No account found with this email.'], 404);
            }

            if (!$client->is_active && empty($client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "You're on our waitlist! Your account hasn't been activated yet.",
                ], 403);
            }

            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            if ($client->email_verification_code !== $request->otp) {
                return response()->json(['success' => false, 'message' => 'Invalid reset code.'], 400);
            }

            if (!$client->email_verification_expires_at || now()->isAfter($client->email_verification_expires_at)) {
                return response()->json(['success' => false, 'message' => 'Reset code has expired. Please request a new one.'], 400);
            }

            $client->password = Hash::make($request->password);
            $client->email_verification_code = null;
            $client->email_verification_expires_at = null;
            $client->save();

            return response()->json(['success' => true, 'message' => 'Password reset successful. You can now log in.'], 200);
        }

        // Token-based reset for users
        $validator2 = Validator::make($request->all(), ['token' => 'required|string']);
        if ($validator2->fails()) {
            return response()->json(['success' => false, 'message' => 'Reset token is required.'], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['success' => true, 'message' => 'Password reset successful.'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Password reset failed. Invalid or expired token.'], 400);
    }

    /**
     * Social login (Google/Facebook)
     */
    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,facebook',
            'provider_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user exists with this provider
            $user = User::where('provider', $request->provider)
                       ->where('provider_id', $request->provider_id)
                       ->first();

            if (!$user) {
                // Check if email already exists
                $existingUser = User::where('email', $request->email)->first();
                
                if ($existingUser) {
                    // Link social account to existing email
                    $existingUser->update([
                        'provider' => $request->provider,
                        'provider_id' => $request->provider_id,
                        'avatar' => $request->avatar,
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'provider' => $request->provider,
                        'provider_id' => $request->provider_id,
                        'avatar' => $request->avatar,
                        'password' => null, // No password for social login
                    ]);
                }
            } else {
                // Update user info if needed
                $user->update([
                    'name' => $request->name,
                    'avatar' => $request->avatar,
                ]);
            }

            $token = $user->createToken('metter-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Social login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'provider' => $user->provider,
                ],
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            Log::error('Social login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Social login failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify 2FA code and complete login
     */
    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
            'is_recovery_code' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address'
            ], 404);
        }

        if (!$client->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled for this account'
            ], 400);
        }

        $method = $client->two_factor_method ?? 'email';
        $isRecoveryCode = $request->is_recovery_code ?? false;

        // Handle recovery code
        if ($isRecoveryCode && $method === 'authenticator') {
            $twoFactorService = new \App\Services\TwoFactorAuthService();
            
            if (!$twoFactorService->verifyRecoveryCode($client->two_factor_recovery_codes ?? [], $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid recovery code'
                ], 401);
            }

            // Remove used recovery code
            $client->two_factor_recovery_codes = $twoFactorService->removeRecoveryCode(
                $client->two_factor_recovery_codes ?? [],
                $request->code
            );
            $client->last_login_at = now();
            $client->save();

            // Generate token
            $token = $client->createToken('metter-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful using recovery code',
                'data' => [
                    'user' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'role' => 'client',
                        'account_type' => 'client',
                    ],
                    'token' => $token,
                    'recovery_codes_remaining' => count($client->two_factor_recovery_codes ?? []),
                ]
            ], 200);
        }

        // Handle email 2FA
        if ($method === 'email') {
            if (!$client->two_factor_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verification code found. Please request a new one.'
                ], 400);
            }

            if ($client->two_factor_expires_at && $client->two_factor_expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired. Please log in again.'
                ], 400);
            }

            if ($client->two_factor_code !== $request->code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code'
                ], 401);
            }

            // Clear 2FA code
            $client->two_factor_code = null;
            $client->two_factor_expires_at = null;
        } 
        // Handle authenticator 2FA
        else if ($method === 'authenticator') {
            if (!$client->two_factor_secret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticator not set up'
                ], 400);
            }

            $twoFactorService = new \App\Services\TwoFactorAuthService();
            
            if (!$twoFactorService->verifyCode($client->two_factor_secret, $request->code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code'
                ], 401);
            }
        }

        $client->last_login_at = now();
        $client->save();

        // Generate token
        $token = $client->createToken('metter-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'role' => 'client',
                    'account_type' => 'client',
                ],
                'token' => $token
            ]
        ], 200);
    }

    /**
     * Resend 2FA code
     */
    public function resend2FACode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address'
            ], 404);
        }

        if (!$client->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled for this account'
            ], 400);
        }

        // Generate and send new code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $client->two_factor_code = $code;
        $client->two_factor_expires_at = now()->addMinutes(10);
        $client->save();

        try {
            $this->send2FACode($client->email, $client->name, $code);
        } catch (\Exception $e) {
            Log::error('2FA code resend failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.'
        ], 200);
    }

    /**
     * Send 2FA code email
     */
    private function send2FACode(string $email, string $name, string $code): void
    {
        // Sanitize name to ensure valid UTF-8 encoding
        $sanitizedName = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        
        Mail::send('emails.two_factor_code', ['name' => $sanitizedName, 'code' => $code], function ($message) use ($email) {
            $message->to($email)
                ->subject('Your Waka Line Login Verification Code');
        });
    }
}
