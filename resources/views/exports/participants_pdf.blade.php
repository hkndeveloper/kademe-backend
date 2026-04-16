<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Katılımci Listesi</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>KADEME Katılımcı Listesi</h2>
    <p>Oluşturulma Tarihi: {{ date('d.m.Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Üniversite</th>
                <th>Bölüm</th>
                <th>Sınıf</th>
                <th>Email</th>
                <th>Kredi</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            @foreach($participants as $p)
            <tr>
                <td>{{ $p->user_id }}</td>
                <td>{{ $p->user->name ?? '-' }}</td>
                <td>{{ $p->university ?? '-' }}</td>
                <td>{{ $p->department ?? '-' }}</td>
                <td>{{ $p->class ?? '-' }}</td>
                <td>{{ $p->user->email ?? '-' }}</td>
                <td>{{ $p->credits }}</td>
                <td>
                    @if($p->status == 'active') Aktif
                    @elseif($p->status == 'passive') Pasif
                    @elseif($p->status == 'alumni') Mezun
                    @elseif($p->status == 'blacklisted') Kara Liste
                    @else {{ $p->status }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
