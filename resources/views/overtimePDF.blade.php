<?php
use Carbon\Carbon;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Surat Izin Lembur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 0.5cm rgba(0, 0, 0, 0.5);
        }

        .header,
        .footer {
            text-align: center;
        }

        .header {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .title {
            text-align: center;
            display: block;
            width: 450px;
            margin: 0 auto;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .content {
            font-size: 12px;
            /* margin-bottom: 20px; */
        }

        /* .data-layout {
            display: grid;
            grid-template-columns: 1.5fr 3fr 1fr 2fr;
            /* 4 kolom */
        gap: 10px;
        /* Jarak antar elemen */
        font-family: Arial,
        sans-serif;
        font-size: 12px;
        }

        */ .row {
            display: contents;
            /* Membiarkan elemen row mengikuti grid */
        }

        .label {
            font-weight: bold;
        }

        .value {
            text-align: left;
        }

        .info {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
        }

        /* .content table th,
        .content table td {
            border: 1px solid #000;
        } */

        .content table th {
            text-align: center;
            padding: 8px;
        }

        .content table td {
            padding: 8px;
            text-align: left;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .approved-by {
            margin-top: 40px;
        }

        .approved-by table {
            width: 100%;
        }

        .approved-by table td {
            width: 50%;
            text-align: center;
        }

        .approved-by table #identity {
            padding-top: 100px;
        }

        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-button button {
            padding: 10px 20px;
            background-color: #4caf50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .print-button button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div class="" id="certificate">
        <div class="title">SURAT PERMOHONAN IJIN LEMBUR TVKU SEMARANG</div>

        <div class="content">
            <div class="">
                <div>
                    <table>
                        <tr>
                            <td style="width: 70px;">Kepada</td>
                            <td>: Staff HRD TVKU Semarang</td>
                        </tr>
                        <tr>
                            <td>Hal</td>
                            <td>: Permohonan Lembur Karyawan</td>
                        </tr>
                    </table>
                    <h4 style="font-weight: 400;margin-left: 7px;">Dengan ini saya, </h4>
                    <br>
                    <table>
                        <tr>
                            <td style="width: 70px;">Nama</td>
                            <td>: {{ $overtime[0]->user->name }}</td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>: {{ Str::title(str_replace('_', ' ', $overtime[0]->user->role)) }}</td>
                        </tr>
                        <tr>
                            <td>Divisi</td>
                            <td>: Devisi</td>
                        </tr>
                    </table>
                    <br>
                    <h4 style="font-weight: 400;margin-left: 7px;">Memohon untuk bekerja ekstra pada, </h4>
                </div>

                <table style="border: 1px solid black">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black">No</th>
                            <th style="border: 1px solid black">Hari / Tanggal</th>
                            <th style="border: 1px solid black">Jam Kerja Normal</th>
                            <th style="border: 1px solid black">Jam Lembur</th>
                            <th style="border: 1px solid black">Guna</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($overtime as $item)
                        <tr>
                            <td style="border: 1px solid black">{{ $loop->iteration }}</td>
                            <td style="border: 1px solid black">{{
                                Carbon::parse($item->tanggal_overtime)->format('F j, Y') }}</td>
                            <td style="border: 1px solid black">
                                8 JAM
                            </td>
                            <td style="border: 1px solid black">
                                @php
                                $overtime = explode('.', $item->overtime);
                                $hours = $overtime[0];
                                $minutes = isset($overtime[1]) ? $overtime[1] : 0;
                                @endphp

                                {{ $hours }} Jam {{ $minutes }} Menit
                            </td>
                            <td style="border: 1px solid black">{{ $item->description }}</td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            <div class="approved-by">
                <table>
                    <tr>
                        <td>Pemohon, <br /></td>
                        <td>Mengetahui, <br /></td>
                        <td>Menyetujui, <br /></td>
                    </tr>
                    <tr>
                        <td id="identity">
                            ...............<br />
                            ...............
                        </td>
                        <td id="identity">
                            Eko Purwito <br>
                            Manager Teknik
                        </td>
                        <td id="identity">
                            Dr.Hery Pamungkas SS, M.I.Kom <br>
                            Direktur Operasional
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Include jsPDF and html2canvas libraries -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</body>

</html>