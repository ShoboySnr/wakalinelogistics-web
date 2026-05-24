@extends('emails.layout')

@section('title', "Congrats! You're on the waitlist!")

@section('content')
<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#211919;">You're on the list, {{ $clientName }}! 🎉</h2>
<p style="margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;">
  Congratulations! You’re now part of our early bird community. Thank you for joining the Waka Line Logistics waitlist. <br />

  We’re currently putting the finishing touches on our new subscription-based delivery platform, and we’ll be going live on June 1st.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fdf2f3;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
  <tr>
    <td>
      <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#211919;text-transform:uppercase;letter-spacing:0.5px;">Your early-bird reward</p>
      <p style="margin:0;font-size:24px;font-weight:800;color:#c1666b;">₦2,000 FREE delivery credits</p>
      <p style="margin:6px 0 0;font-size:13px;color:#6b7280;">Your credit will be automatically added to your account once we launch and you verify your account.</p>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#374151;">What happens next?</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #f3f4f6;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">01</span>
      <span style="font-size:13px;color:#4b5563;">We'll email you the moment the platform goes live</span>
    </td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #f3f4f6;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">02</span>
      <span style="font-size:13px;color:#4b5563;">Your ₦2,000 credit will be available in your dashboard once you set your password and verify your email address.</span>
    </td>
  </tr>
  <tr>
    <td style="padding:6px 0;">
      <span style="font-size:13px;font-weight:700;color:#c1666b;margin-right:10px;">03</span>
      <span style="font-size:13px;color:#4b5563;">Start sending deliveries — pay-as-you-go or subscribe & save</span>
    </td>
  </tr>
</table>

<p style="margin:0 0 24px;font-size:13px;color:#9ca3af;line-height:1.7;">
  Have questions in the meantime? Reply to this email or reach us on
  <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a>.
</p>
@endsection
