<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientIntegration extends Model
{
    protected $fillable = [
        'client_id',
        'integration_type',
        'is_active',
        'credentials',
        'settings',
        'last_synced_at',
        'last_error',
        'sync_count',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials', // Never expose credentials in API responses
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    /**
     * Get safe credentials (masked for display)
     */
    public function getSafeCredentials(): array
    {
        $creds = $this->credentials ?? [];
        $safe = [];
        
        foreach ($creds as $key => $value) {
            if (is_string($value) && strlen($value) > 4) {
                $safe[$key] = substr($value, 0, 4) . str_repeat('*', strlen($value) - 4);
            } else {
                $safe[$key] = '****';
            }
        }
        
        return $safe;
    }

    /**
     * Test the integration connection
     */
    public function testConnection(): bool
    {
        // This will be implemented per integration type
        return true;
    }
}
