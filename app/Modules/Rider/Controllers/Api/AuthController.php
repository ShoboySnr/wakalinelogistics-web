<?php

namespace App\Modules\Rider\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Modules\Admin\Models\Rider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'pin' => 'required|string|min:4|max:64',
            'device_name' => 'nullable|string|max:120',
        ]);

        $throttleKey = 'rider-login:'.Str::lower($data['phone']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ], 429);
        }

        $rider = Rider::where('phone', $data['phone'])->first();

        if (! $rider || ! $rider->password || ! Hash::check($data['pin'], $rider->password)) {
            RateLimiter::hit($throttleKey, 300);

            return response()->json(['message' => 'Those details do not match our records.'], 401);
        }

        if (! $rider->canLogIn()) {
            return response()->json([
                'message' => 'This account is not active. Contact the office.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        return response()->json($this->issueToken($rider, $data['device_name'] ?? null));
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);

        $throttleKey = 'rider-otp:'.Str::lower($data['phone']);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'message' => 'Too many requests. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ], 429);
        }

        RateLimiter::hit($throttleKey, 600);

        $rider = Rider::where('phone', $data['phone'])->first();

        if ($rider && $rider->canLogIn()) {
            $rider->forceFill([
                'login_otp' => (string) random_int(100000, 999999),
                'login_otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ])->save();
        }

        return response()->json([
            'message' => 'If that number is registered, a code has been sent to it.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'code' => 'required|string|max:10',
            'device_name' => 'nullable|string|max:120',
        ]);

        $rider = Rider::where('phone', $data['phone'])->first();

        if (! $rider
            || ! $rider->login_otp
            || ! hash_equals($rider->login_otp, $data['code'])
            || ! $rider->login_otp_expires_at
            || $rider->login_otp_expires_at->isPast()
        ) {
            return response()->json(['message' => 'That code is not valid or has expired.'], 401);
        }

        $rider->forceFill([
            'login_otp' => null,
            'login_otp_expires_at' => null,
            'phone_verified_at' => $rider->phone_verified_at ?? now(),
        ])->save();

        return response()->json($this->issueToken($rider, $data['device_name'] ?? null));
    }

    public function setPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pin' => 'required|string|min:4|max:64|confirmed',
        ]);

        $rider = $request->user();
        $rider->forceFill(['password' => Hash::make($data['pin'])])->save();

        return response()->json(['message' => 'PIN updated.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['rider' => $this->profile($request->user())]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|max:255',
            'platform' => 'required|in:ios,android',
            'device_name' => 'nullable|string|max:120',
            'app_version' => 'nullable|string|max:20',
        ]);

        DeviceToken::register($request->user(), array_merge($data, ['app' => 'rider']));

        return response()->json(['message' => 'Device registered.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $rider = $request->user();

        if ($token = $request->validate(['device_token' => 'nullable|string|max:255'])['device_token'] ?? null) {
            $rider->deviceTokens()->where('token', $token)->delete();
        }

        $request->user()->currentAccessToken()->delete();
        $rider->goOffline();

        return response()->json(['message' => 'Signed out.']);
    }

    private function issueToken(Rider $rider, ?string $deviceName): array
    {
        $rider->tokens()->where('name', $deviceName ?? 'rider-app')->delete();

        $token = $rider->createToken($deviceName ?? 'rider-app', ['rider'])->plainTextToken;

        $rider->forceFill(['last_login_at' => now(), 'last_seen_at' => now()])->save();

        return [
            'token' => $token,
            'rider' => $this->profile($rider),
        ];
    }

    private function profile(Rider $rider): array
    {
        return [
            'id' => $rider->id,
            'name' => $rider->name,
            'phone' => $rider->phone,
            'email' => $rider->email,
            'status' => $rider->status,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_number' => $rider->vehicle_number,
            'rating' => $rider->rating !== null ? (float) $rider->rating : null,
            'total_deliveries' => (int) $rider->total_deliveries,
            'is_online' => (bool) $rider->is_online,
            'photo' => $rider->rider_photo,
            'has_pin' => ! empty($rider->password),
        ];
    }
}
