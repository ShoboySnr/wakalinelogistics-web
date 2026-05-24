<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'client_id',
        'template_name',
        'is_default',
        'layout_settings',
        'style_settings',
        'header_html',
        'footer_html',
        'terms_conditions',
        'payment_instructions',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'layout_settings' => 'array',
        'style_settings' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Set this template as default and unset others
     */
    public function setAsDefault(): void
    {
        // Unset all other defaults for this client
        self::where('client_id', $this->client_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }
}
