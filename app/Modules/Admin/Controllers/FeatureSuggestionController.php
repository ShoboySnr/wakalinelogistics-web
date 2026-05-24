<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\FeatureSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureSuggestionController extends Controller
{
    /**
     * List all feature suggestions (Admin view)
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $category = $request->query('category');
        $sortBy = $request->query('sort_by', 'upvotes'); // upvotes, created_at, status

        $query = FeatureSuggestion::with('client:id,first_name,last_name,email,phone')
            ->withCount('votes');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        // Sorting
        switch ($sortBy) {
            case 'upvotes':
                $query->orderByDesc('upvotes');
                break;
            case 'created_at':
                $query->orderByDesc('created_at');
                break;
            case 'status':
                $query->orderByRaw("FIELD(status, 'pending', 'under_review', 'planned', 'in_progress', 'completed', 'declined')");
                break;
        }

        $suggestions = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $suggestions->map(function ($suggestion) {
                return [
                    'id' => $suggestion->id,
                    'title' => $suggestion->title,
                    'description' => $suggestion->description,
                    'category' => $suggestion->category,
                    'status' => $suggestion->status,
                    'upvotes' => $suggestion->upvotes,
                    'votes_count' => $suggestion->votes_count,
                    'client' => [
                        'id' => $suggestion->client->id,
                        'name' => $suggestion->client->first_name . ' ' . $suggestion->client->last_name,
                        'email' => $suggestion->client->email,
                        'phone' => $suggestion->client->phone,
                    ],
                    'admin_response' => $suggestion->admin_response,
                    'reviewed_at' => $suggestion->reviewed_at,
                    'completed_at' => $suggestion->completed_at,
                    'reward_given' => $suggestion->reward_given,
                    'reward_amount' => $suggestion->reward_amount,
                    'created_at' => $suggestion->created_at,
                    'updated_at' => $suggestion->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $suggestions->currentPage(),
                'last_page' => $suggestions->lastPage(),
                'per_page' => $suggestions->perPage(),
                'total' => $suggestions->total(),
            ],
        ]);
    }

    /**
     * Get single suggestion details
     */
    public function show(int $id)
    {
        $suggestion = FeatureSuggestion::with([
            'client:id,first_name,last_name,email,phone',
            'votes.client:id,first_name,last_name'
        ])->withCount('votes')->find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $suggestion->id,
                'title' => $suggestion->title,
                'description' => $suggestion->description,
                'category' => $suggestion->category,
                'status' => $suggestion->status,
                'upvotes' => $suggestion->upvotes,
                'votes_count' => $suggestion->votes_count,
                'client' => [
                    'id' => $suggestion->client->id,
                    'name' => $suggestion->client->first_name . ' ' . $suggestion->client->last_name,
                    'email' => $suggestion->client->email,
                    'phone' => $suggestion->client->phone,
                ],
                'voters' => $suggestion->votes->map(fn($vote) => [
                    'id' => $vote->client->id,
                    'name' => $vote->client->first_name . ' ' . $vote->client->last_name,
                    'voted_at' => $vote->created_at,
                ]),
                'admin_response' => $suggestion->admin_response,
                'reviewed_at' => $suggestion->reviewed_at,
                'completed_at' => $suggestion->completed_at,
                'reward_given' => $suggestion->reward_given,
                'reward_amount' => $suggestion->reward_amount,
                'created_at' => $suggestion->created_at,
                'updated_at' => $suggestion->updated_at,
            ],
        ]);
    }

    /**
     * Update suggestion status
     */
    public function updateStatus(Request $request, int $id)
    {
        $suggestion = FeatureSuggestion::find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,under_review,planned,in_progress,completed,declined',
            'admin_response' => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'status' => $request->status,
        ];

        // Set reviewed_at when status changes from pending
        if ($suggestion->status === 'pending' && $request->status !== 'pending') {
            $updateData['reviewed_at'] = now();
        }

        // Set completed_at when status is completed
        if ($request->status === 'completed' && $suggestion->status !== 'completed') {
            $updateData['completed_at'] = now();
        }

        // Update admin response if provided
        if ($request->has('admin_response')) {
            $updateData['admin_response'] = $request->admin_response;
        }

        $suggestion->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $suggestion,
        ]);
    }

    /**
     * Add admin response
     */
    public function addResponse(Request $request, int $id)
    {
        $suggestion = FeatureSuggestion::find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'admin_response' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $suggestion->update([
            'admin_response' => $request->admin_response,
            'reviewed_at' => $suggestion->reviewed_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response added successfully',
            'data' => $suggestion,
        ]);
    }

    /**
     * Award reward for implemented suggestion
     */
    public function awardReward(Request $request, int $id)
    {
        $suggestion = FeatureSuggestion::with('client')->find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        if ($suggestion->reward_given) {
            return response()->json(['success' => false, 'message' => 'Reward already given'], 400);
        }

        $validator = Validator::make($request->all(), [
            'reward_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update suggestion
        $suggestion->update([
            'reward_given' => true,
            'reward_amount' => $request->reward_amount,
            'status' => 'completed',
            'completed_at' => $suggestion->completed_at ?? now(),
        ]);

        // Add credits to client's account
        $client = $suggestion->client;
        $client->increment('credit_balance', $request->reward_amount);

        // TODO: Create transaction record for the reward
        // TODO: Send notification to client

        return response()->json([
            'success' => true,
            'message' => 'Reward awarded successfully',
            'data' => [
                'suggestion_id' => $suggestion->id,
                'client_id' => $client->id,
                'reward_amount' => $request->reward_amount,
                'new_balance' => $client->credit_balance,
            ],
        ]);
    }

    /**
     * Get statistics for admin dashboard
     */
    public function statistics()
    {
        $stats = [
            'total' => FeatureSuggestion::count(),
            'pending' => FeatureSuggestion::where('status', 'pending')->count(),
            'under_review' => FeatureSuggestion::where('status', 'under_review')->count(),
            'planned' => FeatureSuggestion::where('status', 'planned')->count(),
            'in_progress' => FeatureSuggestion::where('status', 'in_progress')->count(),
            'completed' => FeatureSuggestion::where('status', 'completed')->count(),
            'declined' => FeatureSuggestion::where('status', 'declined')->count(),
            'total_rewards_given' => FeatureSuggestion::where('reward_given', true)->sum('reward_amount'),
            'top_suggestions' => FeatureSuggestion::with('client:id,first_name,last_name')
                ->orderByDesc('upvotes')
                ->limit(10)
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'upvotes' => $s->upvotes,
                    'status' => $s->status,
                    'client_name' => $s->client->first_name . ' ' . $s->client->last_name,
                ]),
            'recent_submissions' => FeatureSuggestion::with('client:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'status' => $s->status,
                    'client_name' => $s->client->first_name . ' ' . $s->client->last_name,
                    'created_at' => $s->created_at,
                ]),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Delete suggestion (admin only)
     */
    public function destroy(int $id)
    {
        $suggestion = FeatureSuggestion::find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        $suggestion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Suggestion deleted successfully',
        ]);
    }
}
