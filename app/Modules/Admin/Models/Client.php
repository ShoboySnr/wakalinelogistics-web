<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'contact_person',
        'phone',
        'alternate_phone',
        'email',
        'alternate_email',
        'pickup_address',
        'business_address',
        'city',
        'state',
        'business_type',
        'tax_id',
        'website',
        'payment_terms',
        'credit_limit',
        'notes',
        'special_instructions',
        'onboarded_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'onboarded_date' => 'date',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
