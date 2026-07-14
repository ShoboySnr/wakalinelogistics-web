<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    /**
     * Get onboarding status
     */
    public function getStatus()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'onboarding_completed' => $user->onboarding_completed,
                'onboarding_skipped' => $user->onboarding_skipped,
                'onboarding_current_step' => $user->onboarding_current_step,
                'onboarding_completed_at' => $user->onboarding_completed_at,
                'should_show_tour' => !$user->onboarding_completed && !$user->onboarding_skipped,
            ],
        ]);
    }

    /**
     * Update onboarding progress
     */
    public function updateProgress(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_step' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'onboarding_current_step' => $request->current_step ?? $user->onboarding_current_step + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress updated',
            'data' => [
                'current_step' => $user->onboarding_current_step,
            ],
        ]);
    }

    /**
     * Complete onboarding
     */
    public function complete()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully',
        ]);
    }

    /**
     * Skip onboarding
     */
    public function skip()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->update([
            'onboarding_skipped' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped',
        ]);
    }

    /**
     * Reset onboarding (allow user to retake tour)
     */
    public function reset()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->update([
            'onboarding_completed' => false,
            'onboarding_skipped' => false,
            'onboarding_current_step' => 0,
            'onboarding_completed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reset successfully',
        ]);
    }
}
