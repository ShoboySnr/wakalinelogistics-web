@extends('emails.layout')

@section('title', 'Verify Your Waka Line Logistics Account')

@section('content')
<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Verify your email address</h2>
<p style="margin:0 0 24px;color:#6b7280;font-size:14px;line-height:1.6;">
  Hi {{ $name }}, thanks for signing up! Enter the code below to verify your email and unlock your <strong style="color:#111827;">2,000 credit signup bonus</strong>.
</p>

<div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:12px;padding:28px;text-align:center;margin:0 0 24px;">
  <p style="margin:0 0 10px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">Your verification code</p>
  <p style="margin:0;font-size:44px;font-weight:900;letter-spacing:12px;color:#c1666b;font-family:'Courier New',monospace;">{{ $code }}</p>
  <p style="margin:12px 0 0;color:#9ca3af;font-size:12px;">Expires in 30 minutes</p>
</div>

<div style="background:#fefce8;border:1px solid #fde047;border-radius:10px;padding:14px 18px;margin:0 0 24px;">
  <p style="margin:0;color:#854d0e;font-size:13px;font-weight:600;">2,000 credits will be added to your account once your email is verified.</p>
</div>

<p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
  If you did not create a Waka Line Logistics account, you can safely ignore this email.
</p>
@endsection
