<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_number',
        'vendor_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get formatted payment method name matching the form dropdown
     */
    public function getFormattedPaymentMethodAttribute(): string
    {
        $paymentMethods = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Cheque',
        ];

        return $paymentMethods[$this->payment_method] ?? 'N/A';
    }
}
