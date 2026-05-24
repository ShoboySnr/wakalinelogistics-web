@extends('emails.layout')

@section('title', 'New Newsletter Subscriber — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#111827;">New Newsletter Subscriber</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  A new user has subscribed to the Waka Line Logistics newsletter.
</p>

<div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:10px;padding:20px 24px;margin:0 0 24px;">
  <p style="margin:0 0 6px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Subscriber Email</p>
  <p style="margin:0;font-size:16px;font-weight:600;">
    <a href="mailto:{{ $email }}" style="color:#c1666b;text-decoration:none;">{{ $email }}</a>
  </p>
</div>

<p style="margin:0;color:#6b7280;font-size:13px;">
  Check the admin dashboard to view all subscribers.
</p>
@endsection
