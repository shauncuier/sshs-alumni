<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $renderedSubject ?: ($campaign->subject ?: 'SSHS Alumni') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #060a17;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #0b1329;
            border: 1px solid rgba(20, 184, 166, 0.2);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .header {
            background: linear-gradient(135deg, #060a17 0%, #0d1b38 100%);
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid rgba(20, 184, 166, 0.15);
        }
        .header h1 {
            color: #14b8a6;
            font-size: 20px;
            margin: 10px 0 0 0;
            letter-spacing: 0.5px;
        }
        .header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 4px 0 0 0;
        }
        .content {
            padding: 32px 24px;
            color: #cbd5e1;
            font-size: 15px;
            white-space: pre-line;
        }
        .footer {
            background-color: #060a17;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .footer a {
            color: #14b8a6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SSHS Alumni Association</h1>
            <p>সবুজ শিক্ষায়তন প্রাক্তন ছাত্র-ছাত্রী পরিষদ • Est. 2015</p>
        </div>
        <div class="content">
{!! nl2br(e($renderedBody)) !!}
        </div>
        <div class="footer">
            <p>Sabuj Shikshayatan Government High School Alumni Association</p>
            <p>School Est. 1976 • Association Est. 2015</p>
            <p>This message was sent to you as a registered member of the SSHS Alumni community.</p>
        </div>
    </div>
</body>
</html>
