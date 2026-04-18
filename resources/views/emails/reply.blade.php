<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Reply</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .message-info {
            background-color: #f9fafb;
            border-left: 4px solid #4f46e5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .message-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .message-info strong {
            color: #374151;
        }
        .reply-section {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .reply-section h3 {
            margin-top: 0;
            color: #166534;
            font-size: 16px;
        }
        .reply-body {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-line;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 15px;
        }
        .button:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            <div class="header">
                <h1>Message Reply</h1>
            </div>

            <div class="content">
                <p>Hello {{ $message->sender->name }},</p>

                <p>You have received a reply to your message from <strong>{{ $adminName }}</strong>.</p>

                <div class="message-info">
                    <p><strong>Original Subject:</strong> {{ $message->subject ?? 'No Subject' }}</p>
                    <p><strong>Original Message:</strong></p>
                    <p style="margin-top: 5px; color: #6b7280;">{{ Str::limit($message->body, 200) }}</p>
                </div>

                <div class="reply-section">
                    <h3>Reply:</h3>
                    <div class="reply-body">{{ $replyBody }}</div>
                </div>

                <p>You can view the full conversation by logging into your account.</p>

                <div style="text-align: center;">
                    <a href="{{ url('/member/messages/' . $message->id) }}" class="button">View Message</a>
                </div>
            </div>

            <div class="footer">
                <p>This is an automated message from {{ config('app.name') }}.</p>
                <p>Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
