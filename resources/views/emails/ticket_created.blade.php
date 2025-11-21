<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket Created</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
        }
        h1 {
            color: #333333;
            margin-bottom: 10px;
        }
        p {
            font-size: 15px;
            color: #555555;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            background-color: #0d6efd;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            margin-top: 35px;
            font-size: 12px;
            color: #8a8a8a;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        $apiUrl = env('API_URL', 'http://core-laravel-apis.test/api/v1/public') . '/tickets/' . $ticket->id;
    @endphp


    <div class="container">
        <h1>🎫 Ticket Created Successfully!</h1>

        <p>Hello {{ $ticket->user->name ?? 'User' }},</p>

        <p>Your ticket <strong>{{ $ticket->title }}</strong> has been created successfully.</p>

        <p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>

        <a href="{{ $apiUrl }}" class="button">View Ticket</a>

        <div class="footer">
            Thanks,<br>
            {{ config('app.name') }}
        </div>
    </div>

</body>
</html>
