<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Document Forwarded Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f2f2f2; padding: 20px; margin: 0;">

    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

        <div style="border-bottom: 2px solid #696cff; padding-bottom: 16px; margin-bottom: 24px;">
            <h2 style="margin: 0; color: #1a1d3a; font-size: 1.25rem;">Document Forwarded Notification</h2>
        </div>

        <p style="color: #3a3d5c; font-size: 0.95rem;">Hello <strong>{{ $senderName }}</strong>,</p>

        <p style="color: #3a3d5c; font-size: 0.95rem;">
            Your document was forwarded by <strong>{{ $forwarderName }}</strong>
            to <strong>{{ $newRecipientName }}</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem;">
            <tr>
                <td style="padding: 8px 12px; background: #f5f6ff; border-radius: 4px 0 0 0; font-weight: 600; color: #696cff; width: 40%;">Tracking Code</td>
                <td style="padding: 8px 12px; color: #1a1d3a;">{{ $document->tracking_code }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; background: #f5f6ff; font-weight: 600; color: #696cff;">Document Type</td>
                <td style="padding: 8px 12px; color: #1a1d3a;">{{ optional($document->documentType)->type_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; background: #f5f6ff; font-weight: 600; color: #696cff;">Purpose</td>
                <td style="padding: 8px 12px; color: #1a1d3a;">{{ $document->purpose }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; background: #f5f6ff; border-radius: 0 0 0 4px; font-weight: 600; color: #696cff;">Forwarded To</td>
                <td style="padding: 8px 12px; color: #1a1d3a;">{{ $newRecipientName }}</td>
            </tr>
        </table>

        <p style="margin: 24px 0 8px;">
            <a href="{{ $link }}"
               style="display: inline-block; background-color: #696cff; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 5px; font-size: 0.9rem; font-weight: 600;">
                View Sent Documents
            </a>
        </p>

        <p style="color: #8b90b8; font-size: 0.82rem; margin-top: 32px; border-top: 1px solid #f0f2f7; padding-top: 16px;">
            This is an automated notification from the Document Management System.<br>
            Please do not reply to this email.
        </p>

    </div>

</body>
</html>
