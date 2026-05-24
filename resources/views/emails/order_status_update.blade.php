@extends('emails.layout')

@section('title', 'Order Update — Waka Line Logistics')

@section('content')
@php
  $labels = [
    'pending'    => 'Pending',
    'confirmed'  => 'Confirmed',
    'in_transit' => 'In Transit',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
  ];
  $colors = [
    'pending'    => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e'],
    'confirmed'  => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e40af'],
    'in_transit' => ['bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#6b21a8'],
    'delivered'  => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534'],
    'cancelled'  => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#991b1b'],
  ];
  $statusLabel  = $labels[$newStatus]  ?? ucfirst($newStatus);
  $color        = $colors[$newStatus]  ?? $colors['pending'];
  $messages = [
    'confirmed'  => 'Your order has been confirmed and is being prepared for pickup.',
    'in_transit' => 'Great news — your package has been picked up and is on its way!',
    'delivered'  => 'Your package has been delivered successfully. Thank you for choosing Waka Line Logistics!',
    'cancelled'  => 'Your order has been cancelled.',
    'pending'    => 'Your order status has been updated.',
  ];
  $statusMessage = $messages[$newStatus] ?? 'Your order status has been updated.';
@endphp

<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Order update</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $clientName }}, {{ $statusMessage }}
</p>

{{-- Status badge --}}
<div style="background:{{ $color['bg'] }};border:1px solid {{ $color['border'] }};border-radius:12px;padding:20px 24px;text-align:center;margin:0 0 24px;">
  <p style="margin:0 0 6px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Order #{{ $order->order_number }}</p>
  <p style="margin:0;color:{{ $color['text'] }};font-size:22px;font-weight:800;">{{ $statusLabel }}</p>
</div>

{{-- Order route --}}
<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;border:1px solid #f3f4f6;border-radius:10px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">From</p>
      <p style="margin:0;color:#111827;font-size:14px;">{{ $order->pickup_address }}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 16px;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">To</p>
      <p style="margin:0;color:#111827;font-size:14px;">{{ $order->delivery_address }}</p>
    </td>
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
  Track at <a href="https://wakalinelogistics.com/t/{{ $order->order_number }}" style="color:#c1666b;text-decoration:none;">wakalinelogistics.com/t/{{ $order->order_number }}</a>
  &nbsp;·&nbsp; Questions? <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a> or call <a href="tel:08100665758" style="color:#c1666b;text-decoration:none;">0810 066 5758</a>.
</p>
@endsection
