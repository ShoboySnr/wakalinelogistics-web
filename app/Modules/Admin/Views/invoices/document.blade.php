<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body>
@include('Admin::invoices._body', ['invoice' => $invoice, 'letterhead' => $letterhead, 'forPdf' => true])
</body>
</html>
