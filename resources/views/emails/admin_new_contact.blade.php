<!doctype html>
<html>
<body>
    <p>Admin,</p>
    <p>A new contact form was submitted with the following details:</p>
    <ul>
        <li><strong>Name:</strong> {{ $messageModel->first_name }} {{ $messageModel->last_name }}</li>
        <li><strong>Email:</strong> {{ $messageModel->email }}</li>
        <li><strong>Phone:</strong> {{ $messageModel->phone }}</li>
        <li><strong>Message:</strong> {{ $messageModel->message }}</li>
    </ul>
</body>
</html>
