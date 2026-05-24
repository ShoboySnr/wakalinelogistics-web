<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureSuggestion extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'category',
        'status',
        'upvotes',
        'admin_response',
        'reviewed_at',
        'completed_at',
        'reward_given',
        'reward_amount',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'reward_given' => 'boolean',
        'reward_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeatureSuggestionVote::class);
    }

    /**
     * Check if a client has voted for this suggestion
     */
    public function hasVoted(int $clientId): bool
    {
        return $this->votes()->where('client_id', $clientId)->exists();
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'under_review' => 'blue',
            'planned' => 'purple',
            'in_progress' => 'yellow',
            'completed' => 'green',
            'declined' => 'red',
            default => 'gray',
        };
    }
}
