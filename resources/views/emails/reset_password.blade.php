@extends('emails.layout')

@section('title', 'Password Reset — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#111827;">Reset your password</h2>
<p style="margin:0 0 24px;color:#6b7280;font-size:14px;line-height:1.6;">
  Hi {{ $name }}, we received a request to reset your Waka Line Logistics account password. Use the code below to set a new password.
</p>

<div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:12px;padding:28px;text-align:center;margin:0 0 24px;">
  <p style="margin:0 0 10px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">Your reset code</p>
  <p style="margin:0;font-size:44px;font-weight:900;letter-spacing:12px;color:#c1666b;font-family:'Courier New',monospace;">{{ $code }}</p>
  <p style="margin:12px 0 0;color:#9ca3af;font-size:12px;">Expires in 15 minutes</p>
</div>

<p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
  If you did not request a password reset, you can safely ignore this email. Your password will not change.
</p>
@endsection
