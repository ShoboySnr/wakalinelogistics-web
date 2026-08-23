@php
    $safe = config('invoice.safe_area');
    $bank = config('invoice.bank', []);
    $forPdf = $forPdf ?? false;
@endphp
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Tahoma, Geneva, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2F3437;
        }

        .sheet {
            position: relative;
            width: 210mm;
            min-height: 297mm;
        }

        .letterhead {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
            padding: {{ $safe['top'] }} {{ $safe['right'] }} {{ $safe['bottom'] }} {{ $safe['left'] }};
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #C1666B;
            letter-spacing: 1px;
            margin-bottom: 2mm;
        }

        table { width: 100%; border-collapse: collapse; }

        .meta { margin-bottom: 8mm; }
        .meta td { vertical-align: top; padding: 0; }
        .meta .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8b8f92;
            padding-bottom: 1mm;
        }
        .meta .value { font-size: 11px; }
        .meta .bill-to { font-weight: bold; font-size: 12px; }

        .items { margin-bottom: 6mm; }
        .items th {
            background: #2F3437;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 2.5mm 3mm;
            font-weight: bold;
        }
        .items td {
            padding: 2.5mm 3mm;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items tr:nth-child(even) td { background: #faf7f7; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; font-size: 10px; }
        .line-desc { font-size: 10.5px; }
        .line-addr { font-size: 9.5px; color: #6b7280; margin-top: 0.6mm; }

        .summary { width: 100%; margin-top: 2mm; }
        .summary > tbody > tr > td { vertical-align: top; padding: 0; }
        .pay-cell { width: 52%; padding-right: 6mm !important; }
        .totals-cell { width: 48%; }

        .pay-box {
            border: 1px solid #e5e7eb;
            border-left: 3px solid #C1666B;
            padding: 3mm 4mm;
        }
        .pay-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8b8f92;
            margin-bottom: 2mm;
        }
        .pay-lines { width: 100%; }
        .pay-lines td { padding: 0.6mm 0; vertical-align: top; }
        .pay-k { color: #6b7280; width: 26mm; font-size: 10px; }
        .pay-v { font-weight: bold; font-size: 10px; }
        .pay-acct { font-size: 13px; letter-spacing: 1px; color: #C1666B; }
        .pay-ref { margin-top: 2mm; font-size: 9px; color: #6b7280; }

        .totals { width: 100%; }
        .totals td { padding: 1.5mm 3mm; }
        .totals .k { color: #6b7280; }
        .totals .v { text-align: right; white-space: nowrap; }
        .totals .grand td {
            border-top: 2px solid #C1666B;
            font-size: 14px;
            font-weight: bold;
            color: #C1666B;
            padding-top: 2.5mm;
        }

        .note-block { margin-top: 8mm; }
        .note-block h4 {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8b8f92;
            margin-bottom: 1.5mm;
        }
        .note-block p { font-size: 10px; color: #4b5563; }

        .status-cancelled { color: #b91c1c; font-size: 9px; text-transform: uppercase; }
    </style>
<div class="sheet">
    @if($letterhead)
        <img class="letterhead" src="{{ $letterhead }}" alt="">
    @endif

    <div class="content">
        <div class="doc-title">INVOICE</div>

        <table class="meta">
            <tr>
                <td width="55%">
                    <div class="label">Bill to</div>
                    <div class="value bill-to">{{ $invoice->bill_to_name }}</div>
                    @if($invoice->bill_to_address)
                        <div class="value">{{ $invoice->bill_to_address }}</div>
                    @endif
                    @if($invoice->bill_to_phone)
                        <div class="value">{{ $invoice->bill_to_phone }}</div>
                    @endif
                    @if($invoice->bill_to_email)
                        <div class="value">{{ $invoice->bill_to_email }}</div>
                    @endif
                </td>
                <td width="45%">
                    <table>
                        <tr>
                            <td class="label">Invoice no.</td>
                            <td class="value num"><strong>{{ $invoice->invoice_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Invoice date</td>
                            <td class="value num">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        </tr>
                        @if($invoice->due_date)
                        <tr>
                            <td class="label">Due date</td>
                            <td class="value num">{{ $invoice->due_date->format('d M Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="label">Deliveries</td>
                            <td class="value num">{{ $invoice->orders->count() }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th width="14%">Order</th>
                    <th width="13%">Date</th>
                    <th width="53%">Description</th>
                    <th width="20%" style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->orders as $order)
                <tr>
                    <td>
                        {{ $order->pivot->order_number ?: $order->order_number }}
                        @if($order->status === 'cancelled')
                            <div class="status-cancelled">Cancelled</div>
                        @endif
                    </td>
                    <td>{{ $order->pivot->service_date ? \Carbon\Carbon::parse($order->pivot->service_date)->format('d M Y') : '—' }}</td>
                    <td>
                        @php
                            [$lineDesc, $lineAddr] = array_pad(explode("\n", (string) $order->pivot->description, 2), 2, null);
                        @endphp
                        <div class="line-desc">{{ $lineDesc }}</div>
                        @if($lineAddr)
                            <div class="line-addr">{{ $lineAddr }}</div>
                        @endif
                    </td>
                    <td class="num">₦{{ number_format((float) $order->pivot->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="pay-cell">
                    @if($bank['account_number'] ?? null)
                    <div class="pay-box">
                        <div class="pay-title">Payment details</div>
                        <table class="pay-lines">
                            <tr>
                                <td class="pay-k">Bank</td>
                                <td class="pay-v">{{ $bank['bank_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="pay-k">Account name</td>
                                <td class="pay-v">{{ $bank['account_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="pay-k">Account no.</td>
                                <td class="pay-v pay-acct">{{ $bank['account_number'] }}</td>
                            </tr>
                        </table>
                        <div class="pay-ref">Please quote <strong>{{ $invoice->invoice_number }}</strong> as the payment reference.</div>
                    </div>
                    @endif
                </td>
                <td class="totals-cell">
                    <table class="totals">
            <tr>
                <td class="k">Subtotal</td>
                <td class="v">₦{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if((float) $invoice->discount_percent > 0)
            <tr>
                <td class="k">Discount ({{ rtrim(rtrim(number_format((float) $invoice->discount_percent, 2), '0'), '.') }}%)</td>
                <td class="v">− ₦{{ number_format((float) $invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if((float) $invoice->tax_percent > 0)
            <tr>
                <td class="k">VAT ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</td>
                <td class="v">₦{{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            @if((float) $invoice->deduction_amount > 0)
            <tr>
                <td class="k">{{ $invoice->deduction_label ?: 'Less: amount owed to you' }}</td>
                <td class="v">− ₦{{ number_format((float) $invoice->deduction_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="grand">
                <td>{{ (float) $invoice->deduction_amount > 0 ? 'Amount due' : 'Total' }}</td>
                <td class="v">₦{{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
                    </table>
                </td>
            </tr>
        </table>

        @if($invoice->payment_terms)
        <div class="note-block">
            <h4>Payment terms</h4>
            <p>{!! nl2br(e($invoice->payment_terms)) !!}</p>
        </div>
        @endif

        @if($invoice->notes)
        <div class="note-block">
            <h4>Notes</h4>
            <p>{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
        @endif
    </div>
</div>
