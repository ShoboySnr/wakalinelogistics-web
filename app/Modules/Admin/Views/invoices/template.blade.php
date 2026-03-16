<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            font-size: 14px;
            position: relative;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
        }
        
        .diagonal-decoration {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 300px 150px 0;
            border-color: transparent #C1666B transparent transparent;
            opacity: 0.9;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            padding-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            letter-spacing: -0.5px;
        }
        
        .logo-subtext {
            font-size: 11px;
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-left: 2px;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #C1666B;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .invoice-details {
            font-size: 12px;
            color: #666;
        }
        
        .invoice-details strong {
            color: #333;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #C1666B;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .info-row {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            margin-bottom: 3px;
        }
        
        .info-value {
            color: #333;
            font-size: 14px;
        }
        
        .order-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .order-details-table th {
            background-color: #C1666B;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        
        .order-details-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        
        .order-details-table tr:last-child td {
            border-bottom: none;
        }
        
        .total-section {
            margin-top: 30px;
            text-align: right;
        }
        
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .total-label {
            display: table-cell;
            text-align: right;
            padding-right: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .total-value {
            display: table-cell;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            width: 150px;
        }
        
        .grand-total {
            background-color: #C1666B;
            color: white;
            padding: 15px 20px;
            margin-top: 10px;
            border-radius: 5px;
        }
        
        .grand-total .total-label {
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .grand-total .total-value {
            color: white;
            font-size: 20px;
        }
        
        .footer {
            margin-top: 50px;
            padding: 30px 0;
            background-color: #2d3748;
            margin-left: -40px;
            margin-right: -40px;
            margin-bottom: -40px;
            position: relative;
        }
        
        .footer-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 80px 200px;
            border-color: transparent transparent #C1666B transparent;
        }
        
        .footer-content {
            display: table;
            width: 100%;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }
        
        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
            color: #fff;
        }
        
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
            color: #fff;
        }
        
        .footer-item {
            margin-bottom: 8px;
            font-size: 12px;
            color: #e2e8f0;
        }
        
        .footer-item svg {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .notes {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #C1666B;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        
        .notes strong {
            color: #C1666B;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Diagonal Decoration -->
        <div class="diagonal-decoration"></div>
        
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-container">
                    <img src="{{ public_path('wakalinelogistics-logo-dark.png') }}" alt="Wakaline Logistics" style="height: 50px; width: auto;">
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-details">
                    <strong>Invoice #:</strong> {{ $invoice_number }}<br>
                    <strong>Date:</strong> {{ $invoice_date }}<br>
                    <strong>Order #:</strong> {{ $order->order_number }}
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="section">
            <div class="section-title">Bill To</div>
            <div class="info-grid">
                <div class="info-column">
                    <div class="info-row">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value">{{ $order->sender_name ?? $order->customer_name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value">{{ $order->sender_phone ?? $order->customer_phone }}</div>
                    </div>
                    @if($order->sender_email ?? $order->customer_email)
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $order->sender_email ?? $order->customer_email }}</div>
                    </div>
                    @endif
                </div>
                <div class="info-column">
                    <div class="info-row">
                        <div class="info-label">Order Status</div>
                        <div class="info-value">
                            <span class="status-badge status-delivered">Delivered</span>
                        </div>
                    </div>
                    @if($order->delivery_date)
                    <div class="info-row">
                        <div class="info-label">Delivery Date</div>
                        <div class="info-value">{{ $order->delivery_date->format('F d, Y \a\t h:i A') }}</div>
                    </div>
                    @endif
                    @if($order->rider)
                    <div class="info-row">
                        <div class="info-label">Delivered By</div>
                        <div class="info-value">{{ $order->rider->name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Delivery Details -->
        <div class="section">
            <div class="section-title">Delivery Details</div>
            <table class="order-details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Pickup Address</strong></td>
                        <td>{{ $order->pickup_address }}</td>
                    </tr>
                    <tr>
                        <td><strong>Delivery Address</strong></td>
                        <td>{{ $order->delivery_address }}</td>
                    </tr>
                    @if($order->receiver_name)
                    <tr>
                        <td><strong>Receiver</strong></td>
                        <td>{{ $order->receiver_name }} - {{ $order->receiver_phone }}</td>
                    </tr>
                    @endif
                    @if($order->item_description)
                    <tr>
                        <td><strong>Item Description</strong></td>
                        <td>{{ $order->item_description }}</td>
                    </tr>
                    @endif
                    @if($order->item_size || $order->weight || $order->quantity)
                    <tr>
                        <td><strong>Package Details</strong></td>
                        <td>
                            @if($order->item_size) Size: {{ $order->item_size }} @endif
                            @if($order->weight) | Weight: {{ $order->weight }} kg @endif
                            @if($order->quantity) | Quantity: {{ $order->quantity }} @endif
                        </td>
                    </tr>
                    @endif
                    @if($order->distance)
                    <tr>
                        <td><strong>Distance</strong></td>
                        <td>{{ $order->distance }} km</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Payment Summary -->
        <div class="section">
            <div class="section-title">Payment Summary</div>
            <div class="total-section">
                <div class="total-row">
                    <div class="total-label">Delivery Fee:</div>
                    <div class="total-value">₦{{ number_format($order->price, 2) }}</div>
                </div>
                <div class="total-row grand-total">
                    <div class="total-label">TOTAL AMOUNT:</div>
                    <div class="total-value">₦{{ number_format($order->price, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($order->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $order->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-decoration"></div>
            <div class="footer-content">
                <div class="footer-left">
                    <div class="footer-item">@wakalinelogistics</div>
                    <div class="footer-item">www.wakalinelogistics.com</div>
                </div>
                <div class="footer-right">
                    <div class="footer-item">hello@wakalinelogistics.com</div>
                    <div class="footer-item">+234 810 066 5758</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
