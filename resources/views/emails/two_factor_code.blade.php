@extends('emails.layout')

@section('title', 'Your Login Verification Code')

@section('content')
<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#111827;">Login Verification Code</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Hi {{ $name }}, we received a login attempt to your Waka Line Logistics account.
</p>

<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:24px;margin:0 0 24px;text-align:center;">
  <p style="margin:0 0 8px;color:#0c4a6e;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Your Verification Code</p>
  <p style="margin:0;font-size:44px;font-weight:900;letter-spacing:12px;color:#c1666b;font-family:'Courier New',monospace;">{{ $code }}</p>
  <p style="margin:12px 0 0;color:#0369a1;font-size:13px;">This code expires in 10 minutes</p>
</div>

<p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.6;">
  Enter this code on the login page to complete your sign-in. If you didn't attempt to log in, please ignore this email or contact support if you're concerned about your account security.
</p>

<div style="background:#fef2f2;border:1px solid#fecaca;border-radius:8px;padding:12px 16px;margin:0 0 20px;">
  <p style="margin:0;color:#991b1b;font-size:13px;line-height:1.5;">
    <strong>Security Tip:</strong> Never share this code with anyone. Waka Line Logistics staff will never ask for your verification code.
  </p>
</div>

<p style="margin:0;color:#6b7280;font-size:13px;line-height:1.6;">
  Need help? Contact us on <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a> or call <a href="tel:08100665758" style="color:#c1666b;text-decoration:none;">0810 066 5758</a>.
</p>
@endsection
