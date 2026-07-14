<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\FeatureSuggestion;
use Illuminate\Http\Request;

class SuggestionsController extends Controller
{
    public function index(Request $request)
    {
        $query = FeatureSuggestion::with('client')
            ->withCount('votes');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'upvotes');
        match ($sortBy) {
            'created_at' => $query->orderByDesc('created_at'),
            'status'     => $query->orderByRaw("FIELD(status, 'pending', 'under_review', 'planned', 'in_progress', 'completed', 'declined')"),
            default      => $query->orderByDesc('upvotes')->orderByDesc('created_at'),
        };

        $suggestions = $query->paginate(30)->withQueryString();

        $stats = [
            'total'        => FeatureSuggestion::count(),
            'pending'      => FeatureSuggestion::where('status', 'pending')->count(),
            'under_review' => FeatureSuggestion::where('status', 'under_review')->count(),
            'planned'      => FeatureSuggestion::whereIn('status', ['planned', 'in_progress'])->count(),
            'completed'    => FeatureSuggestion::where('status', 'completed')->count(),
            'declined'     => FeatureSuggestion::where('status', 'declined')->count(),
        ];

        return view('Admin::suggestions.index', compact('suggestions', 'stats'));
    }

    public function show(int $id)
    {
        $suggestion = FeatureSuggestion::with([
            'client',
            'votes.client',
        ])->withCount('votes')->findOrFail($id);

        return view('Admin::suggestions.show', compact('suggestion'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status'         => 'required|in:pending,under_review,planned,in_progress,completed,declined',
            'admin_response' => 'nullable|string|max:2000',
        ]);

        $suggestion = FeatureSuggestion::findOrFail($id);

        $update = ['status' => $request->status];

        if ($suggestion->status === 'pending' && $request->status !== 'pending') {
            $update['reviewed_at'] = now();
        }

        if ($request->status === 'completed' && $suggestion->status !== 'completed') {
            $update['completed_at'] = now();
        }

        if ($request->filled('admin_response')) {
            $update['admin_response'] = $request->admin_response;
            if (!$suggestion->reviewed_at) {
                $update['reviewed_at'] = now();
            }
        }

        $suggestion->update($update);

        return redirect()->route('admin.suggestions.show', $id)
            ->with('success', 'Suggestion updated successfully.');
    }

    public function awardReward(Request $request, int $id)
    {
        $request->validate([
            'reward_amount' => 'required|numeric|min:1|max:100000',
        ]);

        $suggestion = FeatureSuggestion::with('client')->findOrFail($id);

        if ($suggestion->reward_given) {
            return back()->with('error', 'Reward has already been given for this suggestion.');
        }

        $suggestion->update([
            'reward_given'  => true,
            'reward_amount' => $request->reward_amount,
            'status'        => 'completed',
            'completed_at'  => $suggestion->completed_at ?? now(),
        ]);

        // Credit the client's account
        try {
            $credits = $suggestion->client->getOrCreateCredits();
            $credits->addCredits((int) $request->reward_amount);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Reward credit failed', [
                'suggestion_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.suggestions.show', $id)
            ->with('success', "₦" . number_format($request->reward_amount) . " reward credited to " . $suggestion->client->name . ".");
    }

    public function destroy(int $id)
    {
        $suggestion = FeatureSuggestion::findOrFail($id);
        $suggestion->votes()->delete();
        $suggestion->delete();

        return redirect()->route('admin.suggestions')
            ->with('success', 'Suggestion deleted.');
    }
}
