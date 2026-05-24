<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Waka Line Logistics')</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:32px 16px;">
  <tr><td align="center">

    <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.08);">

      {{-- HEADER --}}
      <tr>
        <td style="background-color:#211919;padding:28px 40px;text-align:center;">
          <img src="https://wakalinelogistics.com/wakaline-logo-white.png"
               alt="Waka Line Logistics"
               width="200"
               style="display:block;margin:0 auto;max-width:200px;height:auto;">
        </td>
      </tr>

      {{-- CONTENT --}}
      <tr>
        <td style="padding:36px 40px 32px;">
          @yield('content')
        </td>
      </tr>

      {{-- FOOTER --}}
      <tr>
        <td style="background-color:#1a1a1a;padding:28px 32px;text-align:center;">
          <p style="margin:0 0 4px;color:#ffffff;font-size:14px;font-weight:700;letter-spacing:0.3px;">Waka Line Logistics Limited</p>
          <p style="margin:0 0 16px;color:#9ca3af;font-size:12px;">Your Delivery, Done Better.</p>

          <p style="margin:0 0 8px;color:#9ca3af;font-size:12px;line-height:1.8;">
            <a href="tel:08100665758" style="color:#e8929b;text-decoration:none;">0810 066 5758</a>
            &nbsp;&nbsp; | &nbsp;&nbsp;
            <a href="mailto:support@wakalinelogistics.com" style="color:#e8929b;text-decoration:none;">support@wakalinelogistics.com</a>
            &nbsp;&nbsp; | &nbsp;&nbsp;
            <a href="https://wa.me/2348100665758" style="color:#e8929b;text-decoration:none;">WhatsApp</a>
          </p>

          <p style="margin:0 0 16px;color:#6b7280;font-size:12px;">
            <a href="https://facebook.com/wakalinelogistics" style="color:#6b7280;text-decoration:none;margin:0 6px;">Facebook</a>
            <a href="https://instagram.com/wakalinelogistics" style="color:#6b7280;text-decoration:none;margin:0 6px;">Instagram</a>
            <a href="https://tiktok.com/@wakalinelogistics" style="color:#6b7280;text-decoration:none;margin:0 6px;">TikTok</a>
          </p>

          <p style="margin:16px 0 0;color:#4b5563;font-size:11px;border-top:1px solid #2d2d2d;padding-top:16px;">
            &copy; {{ date('Y') }} Waka Line Logistics Limited. All rights reserved.
          </p>
        </td>
      </tr>

    </table>

  </td></tr>
</table>

</body>
</html>
