<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->invoice_number }} — Preview</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <style>
        body {
            margin: 0;
            background: #e9eaeb;
            font-family: Tahoma, Geneva, sans-serif;
        }
        .bar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #2F3437;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .bar-title { font-size: 14px; font-weight: bold; }
        .bar-sub { font-size: 12px; color: #b9bcbe; margin-top: 2px; }
        .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .btn-primary { background: #C1666B; color: #fff; }
        .btn-primary:hover { background: #a8555a; }
        .btn-ghost { background: rgba(255,255,255,0.12); color: #fff; }
        .btn-ghost:hover { background: rgba(255,255,255,0.2); }
        .stage { padding: 24px 12px 48px; }
        .paper {
            width: 210mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 6px 24px rgba(0,0,0,0.18);
        }
        @media print {
            .bar { display: none; }
            .stage { padding: 0; }
            .paper { box-shadow: none; width: auto; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <div>
            <div class="bar-title">{{ $invoice->invoice_number }}</div>
            <div class="bar-sub">
                {{ $invoice->bill_to_name }} · {{ $invoice->orders->count() }}
                {{ Str::plural('order', $invoice->orders->count()) }} · ₦{{ number_format((float) $invoice->total, 2) }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('admin.invoices.download', $invoice->id) }}">Download PDF</a>
            <a class="btn btn-ghost" href="{{ route('admin.clients.show', $invoice->client_id) }}">Back to client</a>
        </div>
    </div>

    <div class="stage">
        <div class="paper">
            @include('Admin::invoices._body', ['invoice' => $invoice, 'letterhead' => $letterhead, 'forPdf' => false])
        </div>
    </div>
</body>
</html>
