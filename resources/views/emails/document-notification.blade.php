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
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Document Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f2f2f2; padding: 20px;">

    <div style="background: #ffffff; padding: 20px; border-radius: 6px;">
        
        <h2>Hello {{ $recipientName }},</h2>

        <p>
            You have received a new document in the Document Management System (DMS).
        </p>

        <p>
            <strong>Tracking Code:</strong> {{ $document->tracking_code }} <br>
            <strong>Title:</strong> {{ $document->title ?? 'N/A' }}
        </p>

        <p>
            Click the button below to view the document:
        </p>

        <p>
            <a href="{{ $link }}" 
               style="background-color: #007bff; color: #ffffff; padding: 10px 15px; text-decoration: none; border-radius: 4px;">
                View Document
            </a>
        </p>

        <br>

        <p>
            Thank you,<br>
            <strong>DMS Team</strong>
        </p>

    </div>

</body>
</html>