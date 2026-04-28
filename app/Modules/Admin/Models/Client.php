<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class Client extends Authenticatable
{
    use Notifiable, AuthenticatableTrait;

    protected $fillable = [
        'name',
        'company_name',
        'contact_person',
        'phone',
        'alternate_phone',
        'email',
        'password',
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
        'dashboard_enabled',
        'last_login_at',
        'email_verified_at',
        'api_key',
        'api_enabled',
        'api_key_generated_at',
        'api_last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'api_enabled' => 'boolean',
        'credit_limit' => 'decimal:2',
        'onboarded_date' => 'date',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'api_key_generated_at' => 'datetime',
        'api_last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function clientShare()
    {
        return $this->hasOne(\App\Models\ClientShare::class);
    }

    /**
     * Generate a new API key for the client
     */
    public function generateApiKey(): string
    {
        $apiKey = 'wkl_' . bin2hex(random_bytes(32));
        
        $this->update([
            'api_key' => $apiKey,
            'api_enabled' => true,
            'api_key_generated_at' => now(),
        ]);
        
        return $apiKey;
    }

    /**
     * Regenerate the API key
     */
    public function regenerateApiKey(): string
    {
        return $this->generateApiKey();
    }

    /**
     * Disable API access
     */
    public function disableApiAccess(): void
    {
        $this->update([
            'api_enabled' => false,
        ]);
    }

    /**
     * Enable API access (generates key if not exists)
     */
    public function enableApiAccess(): string
    {
        if (!$this->api_key) {
            return $this->generateApiKey();
        }
        
        $this->update(['api_enabled' => true]);
        return $this->api_key;
    }

    /**
     * Update last used timestamp
     */
    public function recordApiUsage(): void
    {
        $this->update(['api_last_used_at' => now()]);
    }

    /**
     * Check if API access is active
     */
    public function hasActiveApiAccess(): bool
    {
        return $this->api_enabled && !empty($this->api_key);
    }

    /**
     * Check if dashboard access is enabled
     */
    public function hasDashboardAccess(): bool
    {
        return $this->dashboard_enabled && $this->is_active && !empty($this->password);
    }

    /**
     * Enable dashboard access
     */
    public function enableDashboardAccess(): void
    {
        $this->update(['dashboard_enabled' => true]);
    }

    /**
     * Disable dashboard access
     */
    public function disableDashboardAccess(): void
    {
        $this->update(['dashboard_enabled' => false]);
    }

    /**
     * Record login timestamp
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }
}
