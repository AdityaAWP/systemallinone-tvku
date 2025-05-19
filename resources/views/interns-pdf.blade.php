<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Anak Magang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            font-size: 18px;
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
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Daftar Anak Magang</h1>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Sekolah/Instansi</th>
                <th>Divisi</th>
                <th>Telepon Magang</th>
                <th>Pembimbing Asal</th>
                <th>Pembimbing TVKU</th>
                <th>Telepon Pembimbing</th>
                <th>Mulai Magang</th>
                <th>Selesai Magang</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($interns as $index => $intern)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $intern->name }}</td>
                <td>{{ $intern->email }}</td>
                <td>{{ $intern->school ? $intern->school->name : '-' }}</td>
                <td>{{ $intern->division ?: '-' }}</td>
                <td>{{ $intern->no_phone ?: '-' }}</td>
                <td>{{ $intern->institution_supervisor ?: '-' }}</td>
                <td>{{ $intern->college_supervisor ?: '-' }}</td>
                <td>{{ $intern->college_supervisor_phone ?: '-' }}</td>
                <td>{{ $intern->start_date ? $intern->start_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $intern->end_date ? $intern->end_date->format('d/m/Y') : '-' }}</td>
                <td>
                    @php
                        $now = \Carbon\Carbon::now();
                        if ($now->between($intern->start_date, $intern->end_date)) {
                            echo 'Active';
                        } elseif ($now->lessThan($intern->start_date)) {
                            echo 'Pending';
                        } else {
                            echo 'Completed';
                        }
                    @endphp
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>