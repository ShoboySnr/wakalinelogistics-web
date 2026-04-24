<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Statement of Accounts - {{ $period }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #C1666B;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #C1666B;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #C1666B;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 2px solid #333;
        }
        .summary {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-size: 16px;
            font-weight: bold;
            padding-top: 15px;
            border-top: 2px solid #333;
        }
        .profit {
            color: #4caf50;
        }
        .loss {
            color: #f44336;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #999;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>STATEMENT OF ACCOUNTS</h1>
        <p><strong>Period:</strong> {{ $period }}</p>
        <p><strong>Generated:</strong> {{ $generated_at }}</p>
    </div>

    <!-- Revenue Section -->
    <div class="section">
        <div class="section-title">REVENUE</div>
        @if($revenue->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th style="text-align: right;">Amount (NGN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenue as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->delivery_date)->format('M d, Y') }}</td>
                    <td>{{ $item->order_number ?? 'N/A' }}</td>
                    <td>{{ $item->customer_name }}</td>
                    <td>{{ $item->customer_phone }}</td>
                    <td style="text-align: right;">{{ number_format($item->price, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL REVENUE:</td>
                    <td style="text-align: right;">NGN {{ number_format($total_revenue, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="padding: 15px; background: #f9f9f9; text-align: center;">No revenue recorded for this period</p>
        @endif
    </div>

    <!-- Expenses Section -->
    <div class="section">
        <div class="section-title">EXPENSES</div>
        @if($expenses->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Vendor</th>
                    <th style="text-align: right;">Amount (NGN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->expense_date)->format('M d, Y') }}</td>
                    <td>{{ ucfirst($item->category) }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->vendor_name ?? 'N/A' }}</td>
                    <td style="text-align: right;">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">TOTAL EXPENSES:</td>
                    <td style="text-align: right;">NGN {{ number_format($total_expenses, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="padding: 15px; background: #f9f9f9; text-align: center;">No expenses recorded for this period</p>
        @endif
    </div>

    <!-- Summary Section -->
    <div class="summary">
        <div class="summary-item">
            <span>Total Revenue:</span>
            <span>NGN {{ number_format($total_revenue, 2) }}</span>
        </div>
        <div class="summary-item">
            <span>Total Expenses:</span>
            <span>NGN {{ number_format($total_expenses, 2) }}</span>
        </div>
        <div class="summary-item {{ $profit >= 0 ? 'profit' : 'loss' }}">
            <span>Net {{ $profit >= 0 ? 'Profit' : 'Loss' }}:</span>
            <span>NGN {{ number_format(abs($profit), 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Generated from Waka Line Logistics Management System</p>
    </div>
</body>
</html>
