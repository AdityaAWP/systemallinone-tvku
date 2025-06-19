<!-- resources/views/interns-pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'Daftar Anak Magang' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .status-datang {
            background-color: #FFF9C2;
        }
        .status-active {
            background-color: #D1FAE5;
        }
        .status-hampir {
            background-color: #FEE2E2;
        }
        .status-selesai {
            background-color: #E5E7EB;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/tvku-logo.png') }}" alt="Logo" class="logo">
        <h1>{{ $title ?? 'Daftar Anak Magang' }}</h1>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y') }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Sekolah/Instansi</th>
                <th>Divisi</th>
                <th>Pembimbing TVKU</th>
                <th>Pembimbing Asal</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($interns as $index => $intern)
                @php
                    $now = \Carbon\Carbon::now();
                    $start = \Carbon\Carbon::parse($intern->start_date);
                    $end = \Carbon\Carbon::parse($intern->end_date);
                    $hampirStart = $end->copy()->subMonth();

                    if ($now->lessThan($start)) {
                        $status = 'Datang';
                        $statusClass = 'status-datang';
                    } elseif ($now->greaterThanOrEqualTo($hampirStart) && $now->lessThanOrEqualTo($end)) {
                        $status = 'Hampir';
                        $statusClass = 'status-hampir';
                    } elseif ($now->between($start, $hampirStart->subSecond())) {
                        $status = 'Active';
                        $statusClass = 'status-active';
                    } else {
                        $status = 'Selesai';
                        $statusClass = 'status-selesai';
                    }
                @endphp
                <tr class="{{ $statusClass }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $intern->name }}</td>
                    <td>{{ $intern->school->name ?? '-' }}</td>
                    <td>{{ $intern->internDivision->name ?? '-' }}</td>
                    <td>{{ $intern->institution_supervisor }}</td>
                    <td>{{ $intern->college_supervisor }}</td>
                    <td>{{ $intern->start_date ? $intern->start_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $intern->end_date ? $intern->end_date->format('d/m/Y') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <p><strong>Keterangan Status:</strong></p>
        <ul>
            <li><span style="background-color: #D1FAE5; padding: 2px 5px;">Active</span> - Magang sedang berjalan</li>
            <li><span style="background-color: #FFF9C2; padding: 2px 5px;">Datang</span> - Akan mulai magang</li>
            <li><span style="background-color: #FEE2E2; padding: 2px 5px;">Hampir</span> - Hampir selesai magang</li>
            <li><span style="background-color: #E5E7EB; padding: 2px 5px;">Selesai</span> - Telah selesai magang</li>
        </ul>
    </div>
</body>
</html>