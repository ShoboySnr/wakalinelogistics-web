<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Invoice;
use App\Modules\Admin\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function preview(int $id): Response
    {
        return response($this->invoices->previewHtml(Invoice::findOrFail($id)))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function download(int $id)
    {
        $invoice = Invoice::findOrFail($id);

        return $this->invoices->pdf($invoice)->download($invoice->invoice_number . '.pdf');
    }

    public function destroy(int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $number = $invoice->invoice_number;
        $this->invoices->delete($invoice);

        return response()->json(['success' => true, 'message' => "Invoice {$number} deleted."]);
    }
}
