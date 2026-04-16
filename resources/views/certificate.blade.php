<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sertifika - {{ $user->name }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            width: 100%;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            border: 20px solid #f97316; /* KADEME Orange */
            position: relative;
        }
        .outer-border {
            border: 2px solid #fdba74;
            height: 94%;
            padding: 20px;
        }
        .header {
            font-size: 54px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 8px;
            margin-top: 50px;
            margin-bottom: 20px;
        }
        .divider {
            width: 200px;
            height: 4px;
            background-color: #f97316;
            margin: 0 auto 40px auto;
        }
        .subtitle {
            font-size: 22px;
            color: #64748b;
            margin-bottom: 30px;
        }
        .name {
            font-size: 42px;
            font-weight: bold;
            color: #f97316;
            margin: 10px 0 40px 0;
            text-transform: uppercase;
        }
        .text {
            font-size: 18px;
            color: #334155;
            line-height: 1.8;
            margin: 0 80px;
        }
        .footer-table {
            width: 100%;
            position: absolute;
            bottom: 60px;
            left: 0;
            padding: 0 80px;
        }
        .signature-cell {
            text-align: left;
            vertical-align: bottom;
        }
        .qr-cell {
            text-align: right;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 2px solid #334155;
            width: 200px;
            padding-top: 10px;
        }
        .qr-code {
            width: 110px;
            height: 110px;
            border: 1px solid #e2e8f0;
            padding: 5px;
            background: white;
        }
        .verify-text {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 5px;
            font-family: monospace;
        }
        .date {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="outer-border">
            <div class="header">SERTİFİKA</div>
            <div class="divider"></div>
            
            <div class="subtitle">İşbu belge ile sayın</div>
            
            <div class="name">{{ $user->name }}</div>
            
            <div class="text">
                KADEME bünyesinde yürütülen <strong>{{ $project->name }}</strong> programını başarıyla tamamlayarak mezun olmaya hak kazanmıştır. Kendisinin gösterdiği üstün başarı ve katılımın devamını dileriz.
            </div>
            
            <div class="date">Düzenlenme Tarihi: {{ now()->format('d.m.Y') }}</div>
            
            <table class="footer-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-line">
                            <strong style="color: #1e293b;">KADEME YÖNETİMİ</strong><br>
                            <span style="font-size:12px; color:#64748b;">Genel Koordinatör</span>
                        </div>
                    </td>
                    <td class="qr-cell">
                        @php
                            $verifyUrl = url('/api/cv/' . ($user->participantProfile->uuid ?? $user->id));
                            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyUrl);
                        @endphp
                        <img src="{{ $qrUrl }}" class="qr-code">
                        <div class="verify-text">DOGRULAMA KODU: KD-{{ strtoupper(substr(md5($user->id . $project->id), 0, 8)) }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
