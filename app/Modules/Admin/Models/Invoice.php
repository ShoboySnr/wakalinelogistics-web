<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'deduction_amount',
        'deduction_label',
        'total',
        'status',
        'notes',
        'payment_terms',
        'bill_to_name',
        'bill_to_address',
        'bill_to_email',
        'bill_to_phone',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'invoice_order')
            ->withPivot(['order_number', 'service_date', 'description', 'amount', 'sort_order'])
            ->orderBy('invoice_order.sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Next sequential number for the current year, e.g. INV-2026-0007.
     * Locks the year's rows so two admins generating at once cannot collide.
     */
    public static function nextNumber(?int $year = null): string
    {
        $year = $year ?: (int) now()->format('Y');
        $prefix = 'INV-'.$year.'-';

        $last = static::where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** Invoice value before any amount owed back to the client is applied. */
    public function grossTotal(): float
    {
        return round((float) $this->total + (float) $this->deduction_amount, 2);
    }

    /**
     * Recalculate money fields from the attached line snapshots.
     * Discount is applied before tax.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) DB::table('invoice_order')
            ->where('invoice_id', $this->id)
            ->sum('amount');

        $discount = round($subtotal * ((float) $this->discount_percent / 100), 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * ((float) $this->tax_percent / 100), 2);

        $this->subtotal = round($subtotal, 2);
        $this->discount_amount = $discount;
        $this->tax_amount = $tax;
        $this->total = round($taxable + $tax - (float) $this->deduction_amount, 2);
        $this->save();
    }
}
