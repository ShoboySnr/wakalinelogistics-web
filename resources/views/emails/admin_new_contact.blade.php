@extends('emails.layout')

@section('title', 'New Contact Form Submission — Waka Line Logistics')

@section('content')
<h2 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#111827;">New Contact Form Submission</h2>
<p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">
  A new message has been submitted through the contact form on your website.
</p>

<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 24px;">
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;font-weight:600;width:100px;">Name</td>
    <td style="padding:10px 0;color:#111827;font-size:14px;">{{ $messageModel->first_name }} {{ $messageModel->last_name }}</td>
  </tr>
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;font-weight:600;">Email</td>
    <td style="padding:10px 0;font-size:14px;">
      <a href="mailto:{{ $messageModel->email }}" style="color:#c1666b;text-decoration:none;">{{ $messageModel->email }}</a>
    </td>
  </tr>
  @if($messageModel->phone)
  <tr style="border-bottom:1px solid #f3f4f6;">
    <td style="padding:10px 0;color:#6b7280;font-size:13px;font-weight:600;">Phone</td>
    <td style="padding:10px 0;color:#111827;font-size:14px;">
      <a href="tel:{{ $messageModel->phone }}" style="color:#c1666b;text-decoration:none;">{{ $messageModel->phone }}</a>
    </td>
  </tr>
  @endif
</table>

<div style="background:#fdf4f4;border:1px solid #f3d0d2;border-radius:10px;padding:20px 24px;margin:0 0 24px;">
  <p style="margin:0 0 8px;color:#6b7280;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Message</p>
  <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">{{ $messageModel->message }}</p>
</div>

<p style="margin:0;color:#6b7280;font-size:13px;">
  Reply directly to <a href="mailto:{{ $messageModel->email }}" style="color:#c1666b;text-decoration:none;">{{ $messageModel->email }}</a> to respond.
</p>
@endsection
