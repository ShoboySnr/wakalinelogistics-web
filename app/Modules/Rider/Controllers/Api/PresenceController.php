<?php

namespace App\Modules\Rider\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function setAvailability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_online' => 'required|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $rider = $request->user();

        if ($data['is_online']) {
            $rider->goOnline($data['latitude'] ?? null, $data['longitude'] ?? null);
        } else {
            $rider->goOffline();
        }

        return response()->json([
            'is_online' => (bool) $rider->fresh()->is_online,
        ]);
    }

    public function pushLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pings' => 'required|array|min:1|max:100',
            'pings.*.latitude' => 'required|numeric|between:-90,90',
            'pings.*.longitude' => 'required|numeric|between:-180,180',
            'pings.*.recorded_at' => 'nullable|date',
        ]);

        $latest = collect($data['pings'])
            ->sortBy(fn ($ping) => $ping['recorded_at'] ?? now()->toIso8601String())
            ->last();

        $rider = $request->user();

        $rider->forceFill([
            'current_latitude' => $latest['latitude'],
            'current_longitude' => $latest['longitude'],
            'last_location_update' => $latest['recorded_at'] ?? now(),
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['accepted' => count($data['pings'])]);
    }

    public function showProfile(Request $request): JsonResponse
    {
        $rider = $request->user();

        return response()->json([
            'rider' => [
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
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:20',
        ]);

        $rider = $request->user();
        $rider->fill(array_filter($data, fn ($value) => $value !== null))->save();

        return response()->json(['message' => 'Profile updated.']);
    }
}
