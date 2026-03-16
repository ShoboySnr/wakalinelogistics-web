<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'age',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'status',
        'address',
        'rating',
        'total_deliveries',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'daily_code',
        'daily_code_date',
        // Guarantor 1
        'guarantor1_full_name',
        'guarantor1_dob',
        'guarantor1_nationality',
        'guarantor1_occupation',
        'guarantor1_nin',
        'guarantor1_residential_address',
        'guarantor1_phone',
        'guarantor1_alt_phone1',
        'guarantor1_alt_phone2',
        'guarantor1_work_address',
        'guarantor1_relationship',
        'guarantor1_years_known',
        // Guarantor 2
        'guarantor2_full_name',
        'guarantor2_dob',
        'guarantor2_nationality',
        'guarantor2_occupation',
        'guarantor2_nin',
        'guarantor2_residential_address',
        'guarantor2_phone',
        'guarantor2_alt_phone1',
        'guarantor2_alt_phone2',
        'guarantor2_work_address',
        'guarantor2_relationship',
        'guarantor2_years_known',
        // Witness
        'witness_full_name',
        'witness_phone',
        'witness_address',
        'witness_signature',
        'witness_date',
        // Guarantor Documents
        'guarantor1_id_document',
        'guarantor1_proof_of_address',
        'guarantor1_employment_letter',
        'guarantor1_additional_doc',
        'guarantor2_id_document',
        'guarantor2_proof_of_address',
        'guarantor2_employment_letter',
        'guarantor2_additional_doc',
        // Rider Documents
        'rider_photo',
        'rider_id_document',
        'driver_license_doc',
        'vehicle_registration',
        'vehicle_insurance',
        // Additional Files
        'additional_file_1',
        'additional_file_2',
        'additional_file_3',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'total_deliveries' => 'integer',
        'guarantor1_dob' => 'date',
        'guarantor2_dob' => 'date',
        'guarantor1_years_known' => 'integer',
        'guarantor2_years_known' => 'integer',
        'witness_date' => 'date',
        'last_location_update' => 'datetime',
        'daily_code_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class)->whereIn('status', ['confirmed', 'in_transit']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Generate a new daily code for the rider
     */
    public function generateDailyCode(): string
    {
        $code = strtoupper(substr(md5(uniqid($this->id . time(), true)), 0, 6));
        
        $this->update([
            'daily_code' => $code,
            'daily_code_date' => today()
        ]);
        
        return $code;
    }

    /**
     * Get the current valid daily code (generate if needed)
     */
    public function getDailyCode(): string
    {
        // If no code exists or code is from a previous day, generate new one
        if (!$this->daily_code || !$this->daily_code_date || $this->daily_code_date->lt(today())) {
            return $this->generateDailyCode();
        }
        
        return $this->daily_code;
    }

    /**
     * Validate a provided code against the rider's daily code
     */
    public function validateDailyCode(string $code): bool
    {
        // Code must exist, be for today, and match
        return $this->daily_code 
            && $this->daily_code_date 
            && $this->daily_code_date->isToday()
            && strtoupper($code) === strtoupper($this->daily_code);
    }
}
