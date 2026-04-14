<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class JobApplication extends Model
{
    protected $fillable = [
        'job_type',
        'status',
        'full_name',
        'phone',
        'email',
        'address',
        'age',
        'license_number',
        'experience_years',
        'previous_work',
        'availability',
        'why_join',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who reviewed this application
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to filter by job type
     */
    public function scopeJobType($query, string $type)
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'reviewing' => 'bg-blue-100 text-blue-800',
            'shortlisted' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'hired' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get formatted job type
     */
    public function getFormattedJobTypeAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->job_type));
    }
}
