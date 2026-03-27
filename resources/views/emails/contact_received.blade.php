<!doctype html>
<html>
<body>
    <p>Hi {{ $messageModel->first_name }},</p>
    <p>Thanks for contacting Wakaline. We have received your message and will get back to you shortly.</p>
    <p>Your message:</p>
    <blockquote>{{ $messageModel->message }}</blockquote>
    <p>Best regards,<br/>Wakaline Team</p>
</body>
</html>
