@extends('emails.layout')

@section('title', 'Welcome to Waka Line Logistics!')

@section('content')
<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#111827;">Welcome to Waka Line Logistics, {{ $name }}! 🎉</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  Your email has been verified and your account is now active. We're thrilled to have you on board!
</p>

<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px 24px;margin:0 0 24px;">
  <p style="margin:0 0 4px;color:#166534;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Signup Bonus</p>
  <p style="margin:0;color:#14532d;font-size:22px;font-weight:800;">3,500 Credits Added</p>
  <p style="margin:6px 0 0;color:#166534;font-size:13px;">Ready to use on your first delivery!</p>
</div>

<p style="margin:0 0 16px;color:#4b5563;font-size:14px;font-weight:600;">Here's what you can do with your dashboard:</p>
<ul style="margin:0 0 24px;padding-left:20px;color:#4b5563;font-size:14px;line-height:2.2;">
  <li>Place and track delivery orders in real time</li>
  <li>Top up credits or purchase a subscription plan</li>
  <li>Manage your customers and delivery addresses</li>
  <li>Get discounted fees on all orders you place when you buy credits or subscribe to a plan</li>
</ul>

<p style="margin:0;color:#6b7280;font-size:13px;line-height:1.6;">
  Need help getting started? Reach us on <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">WhatsApp</a> or call <a href="tel:08100665758" style="color:#c1666b;text-decoration:none;">0810 066 5758</a>.
</p>
@endsection
