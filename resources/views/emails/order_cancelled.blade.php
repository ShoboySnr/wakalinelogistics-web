@extends('emails.layout')

@section('title', 'Order Cancelled — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Order cancelled</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $clientName }}, your order has been cancelled.
</p>

<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px 24px;text-align:center;margin:0 0 24px;">
  <p style="margin:0 0 6px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Cancelled Order</p>
  <p style="margin:0;color:#991b1b;font-size:20px;font-weight:800;font-family:'Courier New',monospace;">{{ $order->order_number }}</p>
</div>

@if($refundInfo)
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin:0 0 24px;">
  <p style="margin:0;color:#166534;font-size:14px;line-height:1.6;">{{ $refundInfo }}</p>
</div>
@endif

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;width:140px;">From</td>
    <td style="padding:10px 0;color:#111827;font-size:13px;">{{ $order->pickup_address }}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;color:#6b7280;font-size:13px;">To</td>
    <td style="padding:10px 0;color:#111827;font-size:13px;">{{ $order->delivery_address }}</td>
  </tr>
</table>

<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">
  If you cancelled by mistake or need further assistance, contact us on <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a>.
  You can also track this order at <a href="https://wakalinelogistics.com/t/{{ $order->order_number }}" style="color:#c1666b;text-decoration:none;">wakalinelogistics.com/t/{{ $order->order_number }}</a>.
</p>
@endsection
