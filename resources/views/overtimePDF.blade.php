<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .period-info {
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .content {
            font-size: 12px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
        }

        .content table th,
        .content table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .content table th {
            text-align: center;
        }

        .approved-by {
            margin-top: 40px;
        }

        .approved-by table {
            width: 100%;
            border: none;
        }

        .approved-by table td {
            width: 33.33%;
            text-align: center;
            border: none;
            padding-top: 100px;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px;
        }
    </style>
</head>

<body>
    <div class="title">SURAT PERMOHONAN IJIN LEMBUR TVKU SEMARANG</div>

    <?php if($overtime->count() > 0): ?>
    <div class="content">
        <table style="border: none; margin-bottom: 20px;">
            <tr>
                <td style="width: 70px; border: none;">Kepada</td>
                <td style="border: none;">: Staff HRD TVKU Semarang</td>
            </tr>
            <tr>
                <td style="border: none;">Hal</td>
                <td style="border: none;">: Permohonan Lembur Karyawan
                    <?php if(isset($period)) echo "- " . $period; ?>
                </td>
            </tr>
        </table>

        <h4 style="font-weight: 400; margin-left: 7px;">Dengan ini saya,</h4>
        <br>

        <table style="border: none; margin-bottom: 20px;">
            <tr>
                <td style="width: 70px; border: none;">Nama</td>
                <td style="border: none;">: {{ $overtime[0]->user->name }}</td>
            </tr>
            <tr>
                <td style="border: none;">Jabatan</td>
                <td style="border: none;">: {{ Str::title(str_replace('_', ' ', $overtime[0]->user->role)) }}</td>
            </tr>
            <tr>
                <td style="border: none;">Divisi</td>
                <td style="border: none;">: Divisi</td>
            </tr>
        </table>

        <h4 style="font-weight: 400; margin-left: 7px;">
            Memohon untuk bekerja ekstra pada
            <?php if(isset($period)) echo ", periode " . $period; ?>:
        </h4>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari / Tanggal</th>
                    <th>Jam Kerja Normal</th>
                    <th>Jam Lembur</th>
                    <th>Guna</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($overtime as $index => $item): ?>
                <tr>
                    <td>
                        <?php echo $index + 1; ?>
                    </td>
                    <td>
                        <?php echo \Carbon\Carbon::parse($item->tanggal_overtime)->locale('id')->isoFormat('dddd, D MMMM Y'); ?>
                    </td>
                    <td>8 JAM</td>
                    <td>
                        <?php echo $item->overtime_hours; ?> Jam
                        <?php echo $item->overtime_minutes; ?> Menit
                    </td>
                    <td>
                        <?php echo $item->description; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="approved-by">
            <table>
                <tr>
                    <td>Pemohon,<br><br><br><br><br>...............
                        <br>{{ $overtime[0]->user->name }}
                    </td>
                    <td>Mengetahui,<br><br><br><br><br>Eko Purwito
                        <br>Manager Teknik
                    </td>
                    <td>Menyetujui,<br><br><br><br><br>Dr.Hery Pamungkas SS, M.I.Kom
                        <br>Direktur Operasional
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="content">
        <div class="no-data">
            <h3>TIDAK ADA DATA LEMBUR</h3>
            <?php if(isset($period)): ?>
            <p>Tidak ada data lembur untuk periode <strong>
                    <?php echo $period; ?>
                </strong></p>
            <?php else: ?>
            <p>Tidak ada data lembur yang tersedia</p>
            <?php endif; ?>
            <p style="margin-top: 30px;">
                <em>Silakan pilih periode lain atau periksa kembali data lembur Anda.</em>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>

</html>