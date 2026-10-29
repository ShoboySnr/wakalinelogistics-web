<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function rules(): array
    {
        return [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'deduction_amount' => 'nullable|numeric|min:0',
            'deduction_label' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
            'payment_terms' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return ['order_ids.required' => 'Select at least one order to invoice.'];
    }

    public function create(Client $client, array $validated): Invoice
    {
        $orders = Order::where('client_id', $client->id)
            ->whereIn('id', $validated['order_ids'])
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            throw new DomainException('None of the selected orders belong to this client.');
        }

        $invoice = DB::transaction(function () use ($client, $orders, $validated) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::nextNumber(),
                'client_id' => $client->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 0,
                'deduction_amount' => $validated['deduction_amount'] ?? 0,
                'deduction_label' => ($validated['deduction_label'] ?? null) ?: null,
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'bill_to_name' => $client->company_name ?: $client->name,
                'bill_to_address' => $client->business_address ?: $client->pickup_address,
                'bill_to_email' => $client->email,
                'bill_to_phone' => $client->phone,
                'created_by' => Auth::id(),
            ]);

            foreach ($orders->values() as $i => $order) {
                $invoice->orders()->attach($order->id, [
                    'order_number' => $order->order_number,
                    'service_date' => $order->delivery_date ?: $order->created_at,
                    'description' => trim(($order->item_description ?: 'Delivery service') . "\n" . $order->delivery_address),
                    'amount' => (float) $order->price,
                    'sort_order' => $i,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        ActivityLog::log(
            'invoice_generated',
            "Generated invoice {$invoice->invoice_number} for {$invoice->bill_to_name} ({$orders->count()} orders)",
            $invoice
        );

        return $invoice;
    }

    public function delete(Invoice $invoice): void
    {
        $number = $invoice->invoice_number;
        $invoice->delete();

        ActivityLog::log('invoice_deleted', "Deleted invoice {$number}");
    }

    public function invoiceableOrders(Client $client): Collection
    {
        $orders = $client->orders()
            ->latest()
            ->get(['id', 'order_number', 'status', 'price', 'created_at', 'delivery_date', 'pickup_address', 'delivery_address', 'item_description']);

        $invoiced = DB::table('invoice_order')
            ->whereIn('order_id', $orders->pluck('id'))
            ->pluck('order_id')
            ->unique()
            ->flip();

        return $orders->map(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'price' => (float) $order->price,
            'date' => ($order->delivery_date ?: $order->created_at)?->toDateString(),
            'pickup_address' => $order->pickup_address,
            'delivery_address' => $order->delivery_address,
            'invoiced' => $invoiced->has($order->id),
        ]);
    }

    public function previewHtml(Invoice $invoice): string
    {
        return view('Admin::invoices.preview', [
            'invoice' => $invoice->loadMissing(['orders', 'client']),
            'letterhead' => $this->letterhead(false),
        ])->render();
    }

    public function pdf(Invoice $invoice)
    {
        return Pdf::loadView('Admin::invoices.document', [
            'invoice' => $invoice->loadMissing(['orders', 'client']),
            'letterhead' => $this->letterhead(true),
            'forPdf' => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    public function letterhead(bool $forPdf): ?string
    {
        $relative = config('invoice.letterhead', 'assets/img/invoice-letterhead.png');
        $absolute = public_path($relative);

        if (!is_file($absolute)) {
            return null;
        }

        return $forPdf ? $absolute : asset($relative);
    }
}
