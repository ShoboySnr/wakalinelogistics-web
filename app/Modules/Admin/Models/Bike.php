<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Bike extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bike_number',
        'brand',
        'model',
        'plate_number',
        'color',
        'year',
        'engine_number',
        'chassis_number',
        'status',
        'registration_document',
        'registration_expiry_date',
        'insurance_document',
        'insurance_expiry_date',
        'roadworthiness_document',
        'roadworthiness_expiry_date',
        'hackney_permit_document',
        'hackney_permit_expiry_date',
        'vehicle_license_document',
        'vehicle_license_expiry_date',
        'stickers_permits',
        'assigned_rider_id',
        'assignment_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'registration_expiry_date' => 'date',
        'insurance_expiry_date' => 'date',
        'roadworthiness_expiry_date' => 'date',
        'hackney_permit_expiry_date' => 'date',
        'vehicle_license_expiry_date' => 'date',
        'assignment_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'stickers_permits' => 'array',
    ];

    /**
     * Get the assigned rider
     */
    public function assignedRider(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Rider::class, 'assigned_rider_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Check if any document is expiring within the given days
     */
    public function hasExpiringDocuments($days = 30): bool
    {
        $expiryDate = now()->addDays($days);
        
        $documents = [
            $this->registration_expiry_date,
            $this->insurance_expiry_date,
            $this->roadworthiness_expiry_date,
            $this->hackney_permit_expiry_date,
            $this->vehicle_license_expiry_date,
        ];

        foreach ($documents as $date) {
            if ($date && $date <= $expiryDate && $date >= now()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any document has expired
     */
    public function hasExpiredDocuments(): bool
    {
        $documents = [
            $this->registration_expiry_date,
            $this->insurance_expiry_date,
            $this->roadworthiness_expiry_date,
            $this->hackney_permit_expiry_date,
            $this->vehicle_license_expiry_date,
        ];

        foreach ($documents as $date) {
            if ($date && $date < now()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all expiring documents within the given days
     */
    public function getExpiringDocuments($days = 30): array
    {
        $expiryDate = now()->addDays($days);
        $expiring = [];

        $documentMap = [
            'registration_expiry_date' => 'Registration',
            'insurance_expiry_date' => 'Insurance',
            'roadworthiness_expiry_date' => 'Roadworthiness',
            'hackney_permit_expiry_date' => 'Hackney Permit',
            'vehicle_license_expiry_date' => 'Vehicle License',
        ];

        foreach ($documentMap as $field => $name) {
            $date = $this->$field;
            if ($date && $date <= $expiryDate && $date >= now()) {
                $expiring[] = [
                    'name' => $name,
                    'expiry_date' => $date,
                    'days_remaining' => now()->diffInDays($date),
                ];
            }
        }

        return $expiring;
    }

    /**
     * Get all expired documents
     */
    public function getExpiredDocuments(): array
    {
        $expired = [];

        $documentMap = [
            'registration_expiry_date' => 'Registration',
            'insurance_expiry_date' => 'Insurance',
            'roadworthiness_expiry_date' => 'Roadworthiness',
            'hackney_permit_expiry_date' => 'Hackney Permit',
            'vehicle_license_expiry_date' => 'Vehicle License',
        ];

        foreach ($documentMap as $field => $name) {
            $date = $this->$field;
            if ($date && $date < now()) {
                $expired[] = [
                    'name' => $name,
                    'expiry_date' => $date,
                    'days_overdue' => now()->diffInDays($date),
                ];
            }
        }

        return $expired;
    }

    /**
     * Scope to get bikes with expiring documents
     */
    public function scopeWithExpiringDocuments($query, $days = 30)
    {
        $expiryDate = now()->addDays($days);
        
        return $query->where(function($q) use ($expiryDate) {
            $q->whereBetween('registration_expiry_date', [now(), $expiryDate])
              ->orWhereBetween('insurance_expiry_date', [now(), $expiryDate])
              ->orWhereBetween('roadworthiness_expiry_date', [now(), $expiryDate])
              ->orWhereBetween('hackney_permit_expiry_date', [now(), $expiryDate])
              ->orWhereBetween('vehicle_license_expiry_date', [now(), $expiryDate]);
        });
    }

    /**
     * Scope to get bikes with expired documents
     */
    public function scopeWithExpiredDocuments($query)
    {
        return $query->where(function($q) {
            $q->where('registration_expiry_date', '<', now())
              ->orWhere('insurance_expiry_date', '<', now())
              ->orWhere('roadworthiness_expiry_date', '<', now())
              ->orWhere('hackney_permit_expiry_date', '<', now())
              ->orWhere('vehicle_license_expiry_date', '<', now());
        });
    }

    /**
     * Generate unique bike number
     */
    public static function generateBikeNumber(): string
    {
        do {
            $lastBike = self::withTrashed()->orderBy('id', 'desc')->first();
            $number = $lastBike ? intval(substr($lastBike->bike_number, 5)) + 1 : 1;
            $bikeNumber = 'BIKE-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            
            // Check if this number already exists
            $exists = self::withTrashed()->where('bike_number', $bikeNumber)->exists();
            
            if ($exists) {
                // If exists, increment and try again
                $number++;
            }
        } while ($exists);
        
        return $bikeNumber;
    }

    /**
     * Get all stickers/permits including expiry status
     */
    public function getStickersPermitsWithStatus(): array
    {
        if (!$this->stickers_permits || !is_array($this->stickers_permits)) {
            return [];
        }

        return collect($this->stickers_permits)->map(function($item) {
            $expiryDate = isset($item['expiry_date']) ? \Carbon\Carbon::parse($item['expiry_date']) : null;
            
            // Support both 'document' and 'document_path' keys for backwards compatibility
            $documentPath = $item['document'] ?? $item['document_path'] ?? null;
            
            return [
                'name' => $item['name'] ?? '',
                'serial_number' => $item['serial_number'] ?? '',
                'expiry_date' => $expiryDate,
                'document_path' => $documentPath,
                'is_expired' => $expiryDate && $expiryDate < now(),
                'is_expiring_soon' => $expiryDate && $expiryDate <= now()->addDays(30) && $expiryDate >= now(),
                'days_remaining' => $expiryDate && $expiryDate >= now() ? now()->diffInDays($expiryDate) : null,
                'days_overdue' => $expiryDate && $expiryDate < now() ? now()->diffInDays($expiryDate) : null,
            ];
        })->toArray();
    }

    /**
     * Check if any sticker/permit is expiring or expired
     */
    public function hasExpiringStickerPermit($days = 30): bool
    {
        if (!$this->stickers_permits || !is_array($this->stickers_permits)) {
            return false;
        }

        $expiryDate = now()->addDays($days);
        
        foreach ($this->stickers_permits as $item) {
            if (isset($item['expiry_date'])) {
                $itemExpiry = \Carbon\Carbon::parse($item['expiry_date']);
                if ($itemExpiry <= $expiryDate && $itemExpiry >= now()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if any sticker/permit has expired
     */
    public function hasExpiredStickerPermit(): bool
    {
        if (!$this->stickers_permits || !is_array($this->stickers_permits)) {
            return false;
        }

        foreach ($this->stickers_permits as $item) {
            if (isset($item['expiry_date'])) {
                $itemExpiry = \Carbon\Carbon::parse($item['expiry_date']);
                if ($itemExpiry < now()) {
                    return true;
                }
            }
        }

        return false;
    }
}
