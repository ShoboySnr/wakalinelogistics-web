@extends('emails.layout')

@section('title', 'We received your message — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#111827;">Hi {{ $messageModel->first_name }},</h2>
<p style="margin:0 0 16px;color:#4b5563;font-size:15px;line-height:1.6;">
  Thanks for reaching out to Waka Line Logistics! We have received your message and one of our team members will get back to you shortly.
</p>

<div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:10px;padding:20px 24px;margin:24px 0;">
  <p style="margin:0 0 8px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Your message</p>
  <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">{{ $messageModel->message }}</p>
</div>

<p style="margin:0 0 8px;color:#4b5563;font-size:14px;line-height:1.6;">
  In the meantime, you can reach us directly:
</p>
<ul style="margin:0 0 24px;padding-left:20px;color:#4b5563;font-size:14px;line-height:1.8;">
  <li>Phone: <a href="tel:08100665758" style="color:#c1666b;text-decoration:none;">0810 066 5758</a></li>
  <li>WhatsApp: <a href="https://wa.me/2348100665758" style="color:#c1666b;text-decoration:none;">Chat with us</a></li>
</ul>
@endsection
