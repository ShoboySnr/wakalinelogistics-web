<?php

namespace App\Modules\Admin\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Laravel\Sanctum\HasApiTokens;
class Client extends Authenticatable
{
    use Notifiable, AuthenticatableTrait, HasApiTokens;

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
        'facebook_handle',
        'instagram_handle',
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
        'pod_remittance_enabled',
        'api_key_generated_at',
        'api_last_used_at',
        'theme_preference',
        'language',
        'timezone',
        'date_format',
        'default_pickup_address',
        'default_pickup_contact_name',
        'default_pickup_contact_phone',
        'notification_preferences',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_code',
        'two_factor_expires_at',
        'email_verification_code',
        'email_verification_expires_at',
        'signup_bonus_credited',
        'customer_settings',
        'waitlist_token',
        'company_logo',
        'invoice_prefix',
        'invoice_counter',
        'primary_color',
        'secondary_color',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'tax_id',
        'registration_number',
        'report_settings',
        'onboarding_completed',
        'onboarding_skipped',
        'onboarding_completed_at',
        'onboarding_current_step',
        'zone_rates',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'api_enabled' => 'boolean',
        'pod_remittance_enabled' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'credit_limit' => 'decimal:2',
        'onboarded_date' => 'date',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'api_key_generated_at' => 'datetime',
        'api_last_used_at' => 'datetime',
        'notification_preferences' => 'array',
        'customer_settings'        => 'array',
        'email_verification_expires_at' => 'datetime',
        'two_factor_expires_at' => 'datetime',
        'two_factor_recovery_codes' => 'array',
        'signup_bonus_credited' => 'boolean',
        'report_settings' => 'array',
        'onboarding_completed' => 'boolean',
        'onboarding_skipped' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'zone_rates' => 'array',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getZoneRate(string $zone): ?float
    {
        $rates = $this->zone_rates ?? [];
        return isset($rates[$zone]) ? (float) $rates[$zone] : null;
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
        $apiKey = 'waka_' . bin2hex(random_bytes(32));
        
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

    /**
     * Get client's credit account
     */
    public function credits()
    {
        return $this->hasOne(ClientCredit::class);
    }

    /**
     * Get or create client's credit account
     */
    public function getOrCreateCredits()
    {
        return $this->credits()->firstOrCreate(
            ['client_id' => $this->id],
            [
                'total_credits' => 0,
                'used_credits' => 0,
                'available_credits' => 0,
            ]
        );
    }

    /**
     * Get client's credit transactions
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get client's subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(ClientSubscription::class)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if client has enough credits
     */
    public function hasEnoughCredits(int $required = 1): bool
    {
        $credits = $this->getOrCreateCredits();
        return $credits->hasEnoughCredits($required);
    }

    /**
     * Get available credits count
     */
    public function getAvailableCreditsAttribute(): int
    {
        return $this->getOrCreateCredits()->available_credits;
    }
}
