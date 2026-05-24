<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    protected $fillable = [
        'client_integration_id',
        'action',
        'status',
        'data',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ClientIntegration::class, 'client_integration_id');
    }
}
