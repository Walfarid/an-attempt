New contact message from the portfolio.

Name:    {{ $message->name }}
Email:   {{ $message->email }}
Subject: {{ $message->subject ?? '(no subject)' }}
Sent:    {{ $message->created_at->format('Y-m-d H:i') }} UTC

{{ $message->body }}

—
Reply directly to this email to answer {{ $message->name }}.
