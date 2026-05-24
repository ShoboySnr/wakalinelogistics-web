@extends('emails.layout')

@section('title', 'Rider Assigned — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Your rider is on the way!</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $clientName }}, a rider has been assigned to your order and will be handling your delivery.
</p>

{{-- Rider card --}}
<div style="background:#fdf2f3;border:1px solid #f5c6c8;border-radius:12px;padding:20px 24px;margin:0 0 24px;">
  <p style="margin:0 0 12px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Your Rider</p>
  <table cellpadding="0" cellspacing="0" style="width:100%;">
    <tr>
      <td style="padding-left:14px;vertical-align:middle;">
        <p style="margin:0 0 2px;color:#111827;font-size:16px;font-weight:700;">{{ $rider->name }}</p>
        <p style="margin:0;color:#6b7280;font-size:13px;">
          <a href="tel:{{ $rider->phone }}" style="color:#c1666b;text-decoration:none;">{{ $rider->phone }}</a>
        </p>
      </td>
    </tr>
  </table>
</div>

{{-- Order route --}}
<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;border:1px solid #f3f4f6;border-radius:10px;overflow:hidden;">
  <tr style="background:#f9fafb;">
    <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Order</p>
      <p style="margin:0;color:#111827;font-size:14px;font-weight:600;">#{{ $order->order_number }}</p>
    </td>
  </tr>
  <tr style="background:#f9fafb;">
    <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Pickup from</p>
      <p style="margin:0;color:#111827;font-size:14px;">{{ $order->pickup_address }}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 16px;">
      <p style="margin:0 0 2px;color:#6b7280;font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Delivering to</p>
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
  You can also track your order <a href="https://wakalinelogistics.com/t/{{ $order->order_number }}" style="color:#c1666b;text-decoration:none;">here</a>
  &nbsp;·&nbsp; Questions? <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a> or call <a href="tel:08100665758" style="color:#c1666b;text-decoration:none;">0810 066 5758</a>.
</p>
@endsection
