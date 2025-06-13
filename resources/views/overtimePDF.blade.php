<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Surat Permohonan Ijin Lembur' }}</title>
    <style>
        @page {
            size: A4;
            /* Explicitly set paper size to A4 */
            margin: 20mm;
            /* Standard A4 margins */
        }

        body {
            font-family: 'Times New Roman', Times, serif, Helvetica, sans-serif;
            /* Common sans-serif font */
            font-size: 11pt;
            /* Standard font size */
            line-height: 1.4;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .header-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
        }

        .header-subtitle {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }

        /* Combined table for recipient and employee details for alignment */
        .info-section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-section-table td {
            padding: 1px 5px;
            vertical-align: top;
        }

        .info-label {
            width: 80px;
        }

        .info-colon {
            width: 15px;
            text-align: center;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .details-table th,
        .details-table td {
            border: 1px solid black;
            padding: 1px 8px;
            text-align: left;
            vertical-align: top;
        }

        .details-table th {
            text-align: center;
            font-weight: bold;
            /* Light gray background for header */
        }

        .details-table td.no {
            text-align: center;
            width: 30px;
        }

        .details-table td.date {
            width: 180px;
        }

        .details-table td.time {
            width: 100px;
            text-align: center;
        }

        .closing-text {
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-space {
            height: 70px;
            /* Space for actual signature */
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px;
            border: 1px dashed #ccc;
            margin-top: 30px;
        }
    </style>
</head>

<body>

    <div class="header-title">SURAT PERMOHONAN IJIN LEMBUR</div>
    <div class="header-subtitle">TVKU SEMARANG</div>

    {{-- Combined Recipient/Subject and Employee Details Table for Alignment --}}
    <table class="info-section-table" style="margin-left: 10px">
        <tr>
            <td class="info-label">Kepada</td>
            <td class="info-colon">:</td>
            <td>Staff HRD TVKU Semarang</td>
        </tr>
        <tr>
            <td class="info-label">Hal</td>
            <td class="info-colon">:</td>
            <td>Permohonan Lembur Karyawan</td>
        </tr>
        {{-- Spacer row for visual separation --}}
        <tr>
            <td colspan="3" style="padding-top: 10px; padding-bottom: 40px">Dengan ini saya,</td>
        </tr>
        {{-- Employee Details - Assuming data comes from the first overtime record's user --}}
        @if($overtime->count() > 0)
        <tr>
            <td class="info-label">Nama</td>
            <td class="info-colon">:</td>
            <td>{{Str::headline($overtime[0]->user->name ?? 'Nama Karyawan') }}</td>
        </tr>
        <tr>
            <td class="info-label">Jabatan</td>
            <td class="info-colon">:</td>
            {{-- Using static data from image as role/division might not be directly available or match --}}
            <td>{{ Str::headline($overtime[0]->user->roles->first()->name) }}</td>
        </tr>
        <tr>
            <td class="info-label">Divisi</td>
            <td class="info-colon">:</td>
            <td>{{Str::headline($overtime[0]->user->division->name ?? 'Divisi') }}</td>
        </tr>
        @else
        {{-- Placeholder if no overtime data --}}
        <tr>
            <td colspan="3" style="padding-top: 5px;">(Detail Karyawan tidak tersedia)</td>
        </tr>
        @endif
    </table>

    @if($overtime->count() > 0)
    <p style="margin-top: 20px; margin-left: 10px">Memohon untuk bekerja ekstra pada,</p>

    {{-- Overtime Details Table --}}
    <table class="details-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Hari/Tanggal</th>
                <th>Jam Kerja Normal</th>
                <th>Jam Lembur</th>
                <th>Guna</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtime as $index => $item)
            <tr>
                <td class="no">{{ $index + 1 }}</td>
                <td class="date">
                    {{-- Format date as in image: Day, DD Month YYYY (Indonesian) --}}
                    {{ \Carbon\Carbon::parse($item->tanggal_overtime)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                </td>
                <td class="time">
                    @php
                    // Format normal work time to HH.MM format
                    $normalIn = $item->normal_work_time_check_in ?
                    \Carbon\Carbon::parse($item->normal_work_time_check_in)->format('H.i') : 'N/A';
                    $normalOut = $item->normal_work_time_check_out ?
                    \Carbon\Carbon::parse($item->normal_work_time_check_out)->format('H.i') : 'N/A';
                    echo $normalIn . ' - ' . $normalOut;
                    @endphp
                </td>
                <td class="time">
                    @php
                    // Format overtime to HH.MM format
                    $overtimeIn = $item->check_in ? \Carbon\Carbon::parse($item->check_in)->format('H.i') : 'N/A';
                    $overtimeOut = $item->check_out ? \Carbon\Carbon::parse($item->check_out)->format('H.i') : 'N/A';
                    echo $overtimeIn . ' - ' . $overtimeOut;
                    @endphp
                </td>
                <td>{{ $item->description ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="closing-text" style="margin-left: 10px">Demikian permohonan dari kami, atas persetujuannya kami ucapkan
        terimakasih</p>

    @php
    $direkturOperasional = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'direktur_operasional');
    })->first();
    @endphp

    <table class="signature-table">
        <tr>
            <td>Pemohon,</td>
            <td>Mengetahui,</td>
            <td>Menyetujui,</td>
        </tr>
        <tr>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
        </tr>
        <tr>
            <td>
                <span class="text-bold">{{ Str::headline($overtime[0]->user->name ?? '') }}</span><br>
                {{ Str::headline($overtime[0]->user->division->name ?? 'IT') }}
            </td>
            <td>
                @if($overtime[0]->user->atasan)
                <span class="text-bold">{{ Str::headline($overtime[0]->user->atasan->name) }}</span><br>
                {{ $overtime[0]->user->jabatan_atasan }}
                @else
                <span class="text-bold">-</span><br>
                -
                @endif
            </td>
            <td>
                @if($direkturOperasional)
                <span class="text-bold">{{ Str::headline($direkturOperasional->name) }}</span><br>
                Direktur Operasional
                @else
                <span class="text-bold">-</span><br>
                Direktur Operasional
                @endif
            </td>
        </tr>
    </table>

    @else
    {{-- No Data Section --}}
    <div class="no-data">
        <h3>TIDAK ADA DATA LEMBUR</h3>
        @isset($period)
        <p>Tidak ada data lembur untuk periode <strong>{{ $period }}</strong>.</p>
        @else
        <p>Tidak ada data lembur yang tersedia.</p>
        @endisset
    </div>
    @endif

</body>

</html>