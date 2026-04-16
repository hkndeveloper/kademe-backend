<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #f97316;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .body-text {
            color: #475569;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .footer {
            padding: 30px;
            background-color: #f1f5f9;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #f97316;
            color: #ffffff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KADEME</h1>
        </div>
        <div class="content">
            @if($userName)
                <div class="greeting">Merhaba {{ $userName }},</div>
            @else
                <div class="greeting">Merhaba,</div>
            @endif
            
            <div class="body-text">
                {!! nl2br(e($contentBody)) !!}
            </div>
            
            <a href="http://localhost:3000" class="button">Paneli Görüntüle</a>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KADEME Yönetim Sistemi. Tüm hakları saklıdır.
        </div>
    </div>
</body>
</html>
