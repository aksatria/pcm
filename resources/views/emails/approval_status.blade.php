<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f5f7; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px;">
        <h2 style="margin: 0 0 8px; font-size: 18px; color: #111827;">{{ $title }}</h2>
        <p style="margin: 0 0 16px; font-size: 14px; color: #374151;">Hi {{ $recipientName }},</p>
        <p style="margin: 0 0 16px; font-size: 14px; color: #374151;">{{ $messageText }}</p>

        @if($href)
            <a href="{{ $href }}" style="display: inline-block; padding: 10px 16px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 13px;">
                Buka Dokumen
            </a>
        @endif

        <p style="margin: 20px 0 0; font-size: 12px; color: #6b7280;">
            Email ini dikirim otomatis oleh sistem PCM.
        </p>
    </div>
</body>
</html>
