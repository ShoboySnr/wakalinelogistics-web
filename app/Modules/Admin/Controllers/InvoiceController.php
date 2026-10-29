<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Services\InvoiceService;
use DomainException;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function store(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);

        $validated = $request->validate(
            $this->invoices->rules() + ['action' => 'required|in:preview,download'],
            $this->invoices->messages()
        );

        try {
            $invoice = $this->invoices->create($client, $validated);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $validated['action'] === 'download'
            ? redirect()->route('admin.invoices.download', $invoice->id)
            : redirect()->route('admin.invoices.preview', $invoice->id);
    }

    public function preview($id)
    {
        $invoice = Invoice::with(['orders', 'client'])->findOrFail($id);

        return view('Admin::invoices.preview', [
            'invoice' => $invoice,
            'letterhead' => $this->invoices->letterhead(false),
        ]);
    }

    public function download($id)
    {
        $invoice = Invoice::with(['orders', 'client'])->findOrFail($id);

        return $this->invoices->pdf($invoice)->download($invoice->invoice_number . '.pdf');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $number = $invoice->invoice_number;
        $clientId = $invoice->client_id;

        $this->invoices->delete($invoice);

        return redirect()
            ->route('admin.clients.show', $clientId)
            ->with('success', "Invoice {$number} deleted.");
    }
}
