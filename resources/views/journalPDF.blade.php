<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Jurnal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            color: #333;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 180px;
        }

        .journal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .journal-table th,
        .journal-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .journal-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .colon {
            padding-right: 10px;
        }
    </style>
</head>

<body>
    <h1>Rangkuman Kegiatan</h1>

    @if(count($journal) > 0 && $journal[0]->intern)
    <table class="info-table">
        <tr>
            <td>Nama</td>
            <td class="colon">:</td>
            <td>{{ $journal[0]->intern->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>NIS/NIM</td>
            <td class="colon">:</td>
            <td>{{ $journal[0]->intern->nis_nim ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Periode</td>
            <td class="colon">:</td>
            <td>{{ isset($journal[0]->intern->start_date) ? date('d-m-Y', strtotime($journal[0]->intern->start_date))
                : '01-03-2025' }} - {{ isset($journal[0]->intern->end_date) ? date('d-m-Y',
                strtotime($journal[0]->intern->end_date)) : '31-08-2025' }}</td>
        </tr>
        <tr>
            <td>Sekolah/Universitas</td>
            <td class="colon">:</td>
            <td>{{ $journal[0]->intern->school->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Divisi</td>
            <td class="colon">:</td>
            <td>{{ $journal[0]->intern->internDivision->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Nama Pembimbing</td>
            <td class="colon">:</td>
            <td>{{ $journal[0]->intern->supervisor->name ?? 'Belum ada pembimbing' }}</td>
        </tr>
    </table>
    @else
    <table class="info-table">
        <tr>
            <td>Nama</td>
            <td class="colon">:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>NIS/NIM</td>
            <td class="colon">:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Periode</td>
            <td class="colon">:</td>
            <td>01-03-2025 - 31-08-2025</td>
        </tr>
        <tr>
            <td>Sekolah/Universitas</td>
            <td class="colon">:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Divisi</td>
            <td class="colon">:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Nama Pembimbing</td>
            <td class="colon">:</td>
            <td>Belum ada pembimbing</td>
        </tr>
    </table>
    @endif

    <table class="journal-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Waktu Mulai</th>
                <th>Waktu Selesai</th>
                <th>Aktivitas</th>
                <th>Status</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($journal as $journal)
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $journal->entry_date ? date('d-m-Y', strtotime($journal->entry_date)) : 'N/A' }}</td>
                <td>{{ $journal->start_time ? date('H:i', strtotime($journal->start_time)) : '' }}</td>
                <td>{{ $journal->end_time ? date('H:i', strtotime($journal->end_time)) : '' }}</td>
                <td>{{ $journal->activity ?? '' }}</td>
                <td>{{ strtolower($journal->status ?? 'N/A') }}</td>
                <td>{{ $journal->reason_of_absence ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center;">Tidak ada data jurnal</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>