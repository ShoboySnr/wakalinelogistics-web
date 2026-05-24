@extends('emails.layout')

@section('title', 'Order Confirmed — Waka Line Logistics')

@section('content')
@php
  $statusLabel = match($order->status ?? 'pending') {
    'confirmed' => 'Confirmed',
    'pending'   => 'Pending',
    default     => ucfirst($order->status ?? 'pending'),
  };
  $statusColor = ($order->status ?? '') === 'confirmed' ? '#166534' : '#92400e';
  $statusBg    = ($order->status ?? '') === 'confirmed' ? '#f0fdf4' : '#fffbeb';
  $statusBorder= ($order->status ?? '') === 'confirmed' ? '#bbf7d0' : '#fde68a';
@endphp

<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Order received!</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $clientName }}, your order has been placed successfully. Here are the details:
</p>

{{-- Order number + status --}}
<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;">
  <tr>
    <td style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:10px;padding:16px 20px;">
      <p style="margin:0 0 4px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Order Number</p>
      <p style="margin:0;color:#c1666b;font-size:20px;font-weight:800;font-family:'Courier New',monospace;">{{ $order->order_number }}</p>
    </td>
    <td style="width:12px;"></td>
    <td style="background:{{ $statusBg }};border:1px solid {{ $statusBorder }};border-radius:10px;padding:16px 20px;text-align:center;">
      <p style="margin:0 0 4px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Status</p>
      <p style="margin:0;color:{{ $statusColor }};font-size:16px;font-weight:700;">{{ $statusLabel }}</p>
    </td>
  </tr>
</table>

{{-- Pickup & Delivery --}}
<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;border:1px solid #f3f4f6;border-radius:10px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Pickup</p>
      <p style="margin:0;color:#111827;font-size:14px;">{{ $order->pickup_address }}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 16px;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Delivery to</p>
      <p style="margin:0;color:#111827;font-size:14px;">{{ $order->delivery_address }}</p>
    </td>
  </tr>
</table>

{{-- Package details --}}
<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;width:140px;">Receiver</td>
    <td style="padding:10px 0;color:#111827;font-size:13px;font-weight:600;">{{ $order->receiver_name }} · {{ $order->receiver_phone }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Package</td>
    <td style="padding:10px 0;color:#111827;font-size:13px;">{{ $order->package_description }} (qty: {{ $order->package_quantity }})</td>
  </tr>
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Payment</td>
    <td style="padding:10px 0;color:#111827;font-size:13px;text-transform:capitalize;">{{ $order->payment_method }}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Delivery fee</td>
    <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:700;">₦{{ number_format($order->price) }}</td>
  </tr>
</table>

{{-- Track order button --}}
<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;">
  <tr>
    <td align="center">
      <a href="https://wakalinelogistics.com/t/{{ $order->order_number }}"
         style="display:inline-block;background-color:#c1666b;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;">
        Track Your Order
      </a>
    </td>
  </tr>
</table>

<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">
  Any questions? Reach us on <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a>.
</p>
@endsection
