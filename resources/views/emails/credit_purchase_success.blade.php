@extends('emails.layout')

@section('title', 'Credits Added — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Credits added to your account</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $clientName }}, your payment was successful and your credits are ready to use.
</p>

<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:24px;text-align:center;margin:0 0 24px;">
  <p style="margin:0 0 6px;color:#166534;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Credits Added</p>
  <p style="margin:0;color:#14532d;font-size:36px;font-weight:900;">{{ number_format($credits) }}</p>
  <p style="margin:8px 0 0;color:#166534;font-size:13px;">{{ $description }}</p>
</div>

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 24px;">
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;width:140px;">Amount Paid</td>
    <td style="padding:10px 0;color:#111827;font-size:14px;font-weight:700;">₦{{ number_format($amountPaid) }}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;color:#6b7280;font-size:13px;">New Balance</td>
    <td style="padding:10px 0;color:#c1666b;font-size:14px;font-weight:700;">{{ number_format($newBalance) }} credits</td>
  </tr>
</table>

<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">
  Your credits are now available for placing delivery orders. Need help? Chat with us on <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a>.
</p>
@endsection
