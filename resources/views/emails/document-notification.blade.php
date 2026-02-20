<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Document Notification</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <h2>Hello {{ $recipientName }},</h2>
    <p>You have received a new document in your Document Management System inbox.</p>
    <p><strong>Tracking Code:</strong> {{ $document->tracking_code }}</p>
    <p><strong>Purpose:</strong> {{ $document->purpose }}</p>
    <p>
        <a href="{{ $link }}" style="display:inline-block;padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;">View Document</a>
    </p>
    <p>If you have any questions, please log in to your account for more details.</p>
    <p>Thank you,<br>Document Management System</p>
</body>
</html>
