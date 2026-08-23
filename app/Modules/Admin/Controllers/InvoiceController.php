<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function store(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);

        $validated = $request->validate([
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
            'action' => 'required|in:preview,download',
        ], [
            'order_ids.required' => 'Select at least one order to invoice.',
        ]);

        $orders = Order::where('client_id', $client->id)
            ->whereIn('id', $validated['order_ids'])
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'None of the selected orders belong to this client.');
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
                'deduction_label' => $validated['deduction_label'] ?: null,
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'bill_to_name' => $client->company_name ?: $client->name,
                'bill_to_address' => $client->business_address ?: $client->pickup_address,
                'bill_to_email' => $client->email,
                'bill_to_phone' => $client->phone,
                'created_by' => auth()->id(),
            ]);

            foreach ($orders->values() as $i => $order) {
                $invoice->orders()->attach($order->id, [
                    'order_number' => $order->order_number,
                    'service_date' => $order->delivery_date ?: $order->created_at,
                    'description' => trim(($order->item_description ?: 'Delivery service')."\n".$order->delivery_address),
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

        return $validated['action'] === 'download'
            ? redirect()->route('admin.invoices.download', $invoice->id)
            : redirect()->route('admin.invoices.preview', $invoice->id);
    }

    public function preview($id)
    {
        $invoice = Invoice::with(['orders', 'client'])->findOrFail($id);

        return view('Admin::invoices.preview', [
            'invoice' => $invoice,
            'letterhead' => $this->letterheadUrl(false),
        ]);
    }

    public function download($id)
    {
        $invoice = Invoice::with(['orders', 'client'])->findOrFail($id);

        $pdf = Pdf::loadView('Admin::invoices.document', [
            'invoice' => $invoice,
            'letterhead' => $this->letterheadUrl(true),
            'forPdf' => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $number = $invoice->invoice_number;
        $clientId = $invoice->client_id;

        $invoice->delete();

        ActivityLog::log('invoice_deleted', "Deleted invoice {$number}");

        return redirect()
            ->route('admin.clients.show', $clientId)
            ->with('success', "Invoice {$number} deleted.");
    }

    /**
     * dompdf cannot fetch the asset over HTTP reliably, so it gets an
     * absolute filesystem path while the browser preview gets a URL.
     */
    private function letterheadUrl(bool $forPdf): ?string
    {
        $relative = config('invoice.letterhead', 'assets/img/invoice-letterhead.png');
        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return null;
        }

        return $forPdf ? $absolute : asset($relative);
    }
}
