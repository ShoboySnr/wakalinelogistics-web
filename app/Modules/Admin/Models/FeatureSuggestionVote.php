<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureSuggestionVote extends Model
{
    protected $fillable = [
        'feature_suggestion_id',
        'client_id',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(FeatureSuggestion::class, 'feature_suggestion_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
