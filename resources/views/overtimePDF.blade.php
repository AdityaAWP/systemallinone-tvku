<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Certificate of Analysis</title>
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

        .content table th,
        .content table td {
            border: 1px solid #000;
        }

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
                <div class="">
                    <div class="">Kepada</div>
                    <div class="value">: Staff HRD TVKU Semarang</div>
                    <div class="label"></div>
                    <div class="value"></div>
                </div>

                <div class="row">
                    <div class="label">Hal</div>
                    <div class="value">: Permohonan Lembur Karyawan</div>
                    <div class="label"></div>
                    <div class="value"></div>
                </div>

                <div class="row">
                    <div class="label">Nama</div>
                    <div class="value">: {{ $overtime[0]->user->name }}</div>
                    <div class="label"></div>
                    <div class="value"></div>
                </div>
                <div class="row">
                    <div class="label">Jabatan</div>
                    <div class="value">: ....</div>
                    <div class="label"></div>
                    <div class="value"></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengujian</th>
                        <th>Spesifikasi</th>
                        <th>Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($overtime as $item)
                    <tr>
                        <td>1</td>
                        <td>{{ $item->tanggal_overtime}}</td>
                        <td>
                            Terjadi warna biru intensif pada kertas saring,
                            warna akan memucat setelah beberapa menit
                        </td>
                        <td>Terpenuhi</td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        <div class="approved-by">
            <table>
                <tr>
                    <td>Approved by: <br /></td>
                    <td>QA-Analyst: <br /></td>
                </tr>
                <tr>
                    <td id="identity">
                        <strong>PP</strong><br />
                        Head Of Laboratory
                    </td>
                    <td id="identity">
                        <strong>(WIDODO)</strong>
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