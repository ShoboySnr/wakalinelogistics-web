<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\FeatureSuggestion;
use App\Modules\Admin\Models\FeatureSuggestionVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeatureSuggestionController extends Controller
{
    /**
     * List all feature suggestions
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status = $request->query('status', 'all');
        $category = $request->query('category');

        $query = FeatureSuggestion::with('client:id,business_name')
            ->withCount('votes')
            ->orderByDesc('upvotes')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $suggestions = $query->get()->map(function ($suggestion) use ($user) {
            return [
                'id' => $suggestion->id,
                'title' => $suggestion->title,
                'description' => $suggestion->description,
                'category' => $suggestion->category,
                'status' => $suggestion->status,
                'upvotes' => $suggestion->upvotes,
                'votes_count' => $suggestion->votes_count,
                'submitted_by' => $suggestion->client->name ?? 'Anonymous',
                'is_own' => $suggestion->client_id === $user->id,
                'has_voted' => $suggestion->hasVoted($user->id),
                'admin_response' => $suggestion->admin_response,
                'reviewed_at' => $suggestion->reviewed_at,
                'completed_at' => $suggestion->completed_at,
                'reward_given' => $suggestion->reward_given,
                'created_at' => $suggestion->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get user's own suggestions
     */
    public function mySuggestions()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $suggestions = FeatureSuggestion::where('client_id', $user->id)
            ->withCount('votes')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($suggestion) {
                return [
                    'id' => $suggestion->id,
                    'title' => $suggestion->title,
                    'description' => $suggestion->description,
                    'category' => $suggestion->category,
                    'status' => $suggestion->status,
                    'upvotes' => $suggestion->upvotes,
                    'votes_count' => $suggestion->votes_count,
                    'admin_response' => $suggestion->admin_response,
                    'reviewed_at' => $suggestion->reviewed_at,
                    'completed_at' => $suggestion->completed_at,
                    'reward_given' => $suggestion->reward_given,
                    'reward_amount' => $suggestion->reward_amount,
                    'created_at' => $suggestion->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Submit a new feature suggestion
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'category' => 'nullable|string|in:UI/UX,Integration,Automation,Reporting,Mobile,Other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $suggestion = FeatureSuggestion::create([
            'client_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category ?? 'Other',
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your suggestion! We\'ll review it soon.',
            'data' => [
                'id' => $suggestion->id,
                'title' => $suggestion->title,
                'status' => $suggestion->status,
            ],
        ], 201);
    }

    /**
     * Upvote a suggestion
     */
    public function upvote(int $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $suggestion = FeatureSuggestion::find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        // Check if already voted
        if ($suggestion->hasVoted($user->id)) {
            return response()->json(['success' => false, 'message' => 'You have already voted for this suggestion'], 400);
        }

        // Create vote
        FeatureSuggestionVote::create([
            'feature_suggestion_id' => $suggestion->id,
            'client_id' => $user->id,
        ]);

        // Increment upvotes
        $suggestion->increment('upvotes');

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded!',
            'data' => [
                'upvotes' => $suggestion->upvotes,
            ],
        ]);
    }

    /**
     * Remove upvote
     */
    public function removeVote(int $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $suggestion = FeatureSuggestion::find($id);

        if (!$suggestion) {
            return response()->json(['success' => false, 'message' => 'Suggestion not found'], 404);
        }

        // Find and delete vote
        $vote = FeatureSuggestionVote::where('feature_suggestion_id', $suggestion->id)
            ->where('client_id', $user->id)
            ->first();

        if (!$vote) {
            return response()->json(['success' => false, 'message' => 'You have not voted for this suggestion'], 400);
        }

        $vote->delete();
        $suggestion->decrement('upvotes');

        return response()->json([
            'success' => true,
            'message' => 'Vote removed',
            'data' => [
                'upvotes' => $suggestion->upvotes,
            ],
        ]);
    }

    /**
     * Get suggestion statistics
     */
    public function stats()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_suggestions' => FeatureSuggestion::where('client_id', $user->id)->count(),
            'pending' => FeatureSuggestion::where('client_id', $user->id)->where('status', 'pending')->count(),
            'under_review' => FeatureSuggestion::where('client_id', $user->id)->where('status', 'under_review')->count(),
            'planned' => FeatureSuggestion::where('client_id', $user->id)->where('status', 'planned')->count(),
            'completed' => FeatureSuggestion::where('client_id', $user->id)->where('status', 'completed')->count(),
            'total_rewards' => FeatureSuggestion::where('client_id', $user->id)->where('reward_given', true)->sum('reward_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
