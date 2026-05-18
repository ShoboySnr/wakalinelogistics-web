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

        $token = $client->createToken('wakaline-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified! Welcome to Wakaline — 2,000 credits have been added to your account.',
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
        $html = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f9fafb;font-family:\'Helvetica Neue\',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <tr><td style="background:linear-gradient(135deg,#1a0406,#c1666b);padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">Wakaline Logistics</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,.7);font-size:13px;">Verify your email address</p>
        </td></tr>
        <tr><td style="padding:40px;">
          <p style="margin:0 0 8px;color:#374151;font-size:15px;">Hi ' . htmlspecialchars($name) . ',</p>
          <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;">Thanks for signing up! Enter the code below to verify your email and unlock your 2,000 credit signup bonus.</p>
          <div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:12px;padding:28px;text-align:center;margin-bottom:28px;">
            <p style="margin:0 0 8px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">Your verification code</p>
            <p style="margin:0;font-size:42px;font-weight:900;letter-spacing:10px;color:#c1666b;font-family:\'Courier New\',monospace;">' . $code . '</p>
            <p style="margin:12px 0 0;color:#9ca3af;font-size:12px;">Expires in 30 minutes</p>
          </div>
          <div style="background:#fefce8;border:1px solid #fde047;border-radius:10px;padding:14px 18px;margin-bottom:28px;">
            <p style="margin:0;color:#854d0e;font-size:13px;font-weight:600;">🎁 2,000 credits will be added to your account once verified.</p>
          </div>
          <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">If you did not create a Wakaline account, you can safely ignore this email.</p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #f3f4f6;">
          <p style="margin:0;color:#9ca3af;font-size:12px;">© ' . date('Y') . ' Wakaline Logistics. All rights reserved.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

        Mail::html($html, function ($message) use ($email) {
            $message->to($email)
                ->subject('Verify your Wakaline account — your code inside');
        });
    }

    private function sendPasswordResetEmail(string $email, string $name, string $code): void
    {
        $html = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f9fafb;font-family:\'Helvetica Neue\',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <tr><td style="background:linear-gradient(135deg,#1a0406,#c1666b);padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">Wakaline Logistics</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,.7);font-size:13px;">Password reset request</p>
        </td></tr>
        <tr><td style="padding:40px;">
          <p style="margin:0 0 8px;color:#374151;font-size:15px;">Hi ' . htmlspecialchars($name) . ',</p>
          <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;">We received a request to reset your password. Use the code below to set a new password. If you did not request this, you can safely ignore this email.</p>
          <div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:12px;padding:28px;text-align:center;margin-bottom:28px;">
            <p style="margin:0 0 8px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">Your reset code</p>
            <p style="margin:0;font-size:42px;font-weight:900;letter-spacing:10px;color:#c1666b;font-family:\'Courier New\',monospace;">' . $code . '</p>
            <p style="margin:12px 0 0;color:#9ca3af;font-size:12px;">Expires in 15 minutes</p>
          </div>
          <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">If you did not request a password reset, please ignore this email. Your password will not change.</p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #f3f4f6;">
          <p style="margin:0;color:#9ca3af;font-size:12px;">© ' . date('Y') . ' Wakaline Logistics. All rights reserved.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

        Mail::html($html, function ($message) use ($email) {
            $message->to($email)
                ->subject('Reset your Wakaline password — code inside');
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

        $token = $user->createToken('metter-app')->plainTextToken;

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
}
