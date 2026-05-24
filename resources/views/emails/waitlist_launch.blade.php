@extends('emails.layout')

@section('title', "We're live! Activate Your Account Now")

@section('content')
<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#211919;">We're officially live, {{ $clientName }}! 🎉</h2>
<p style="margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;">
  The wait is over. Waka Line Logistics is now live and your early-bird account is ready to be activated.
  Click the button below to set your password and claim your <strong style="color:#c1666b;">₦2,000 free delivery credit</strong>.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;text-align:center;">
  <tr>
    <td>
      <a href="{{ $activationUrl }}"
         style="display:inline-block;background-color:#c1666b;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;padding:16px 40px;border-radius:12px;letter-spacing:0.3px;">
        Activate My Account &amp; Get ₦2,000 →
      </a>
      <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">This link is unique to you and works only once.</p>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#374151;">What happens when you click the button?</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #f3f4f6;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">01</span>
      <span style="font-size:13px;color:#4b5563;">Set your password on the secure page</span>
    </td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #f3f4f6;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">02</span>
      <span style="font-size:13px;color:#4b5563;">Your ₦2,000 credit lands in your account instantly</span>
    </td>
  </tr>
  <tr>
    <td style="padding:6px 0;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">03</span>
      <span style="font-size:13px;color:#4b5563;">You’ll be taken straight to your dashboard, where you can start sending and receiving orders with our bike logistics service.</span>
    </td>
  </tr>
</table>

<p style="margin:0 0 6px;font-size:13px;color:#6b7280;line-height:1.6;">
  If the button doesn't work, copy and paste this link into your browser:
</p>
<p style="margin:0 0 24px;font-size:12px;word-break:break-all;">
  <a href="{{ $activationUrl }}" style="color:#c1666b;text-decoration:none;">{{ $activationUrl }}</a>
</p>

<p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.7;">
  Questions or Issues? Reply to this email or reach us on
  <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a>.
</p>
@endsection
