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
        'owns_vehicle',
        'vehicle_type',
        'vehicle_registration',
        'coverage_areas',
        'has_smartphone',
        'guarantor1_full_name',
        'guarantor1_phone',
        'guarantor1_relationship',
        'guarantor1_occupation',
        'guarantor1_residential_address',
        'guarantor2_full_name',
        'guarantor2_phone',
        'guarantor2_relationship',
        'guarantor2_occupation',
        'guarantor2_residential_address',
        'experience_years',
        'previous_work',
        'availability',
        'why_join',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'owns_vehicle' => 'boolean',
        'has_smartphone' => 'boolean',
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
    public const PARTNER_TYPES = ['partner_rider', 'foot_soldier'];

    public function getFormattedJobTypeAttribute(): string
    {
        return match ($this->job_type) {
            'partner_rider' => 'Partner Rider',
            'foot_soldier' => 'Foot Soldier',
            default => ucwords(str_replace('_', ' ', (string) $this->job_type)),
        };
    }

    public function guarantors(): array
    {
        return collect([1, 2])
            ->map(fn (int $index) => [
                'index' => $index,
                'full_name' => $this->{"guarantor{$index}_full_name"},
                'phone' => $this->{"guarantor{$index}_phone"},
                'relationship' => $this->{"guarantor{$index}_relationship"},
                'occupation' => $this->{"guarantor{$index}_occupation"},
                'residential_address' => $this->{"guarantor{$index}_residential_address"},
            ])
            ->filter(fn (array $guarantor) => ! empty($guarantor['full_name']))
            ->values()
            ->all();
    }

    public function isPartnerApplication(): bool
    {
        return in_array($this->job_type, self::PARTNER_TYPES, true);
    }

    public function scopePartners($query)
    {
        return $query->whereIn('job_type', self::PARTNER_TYPES);
    }
}
