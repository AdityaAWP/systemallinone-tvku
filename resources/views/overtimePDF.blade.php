<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Permission Request Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin-bottom: 5px;
            font-size: 18px;
            font-weight: bold;
        }

        .header h2 {
            margin-top: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .recipient {
            margin-bottom: 20px;
        }

        .recipient-line {
            display: flex;
            margin-bottom: 5px;
        }

        .recipient-label {
            width: 80px;
        }

        .form-field {
            display: flex;
            margin-bottom: 15px;
        }

        .form-label {
            width: 80px;
        }

        .form-input {
            flex: 1;
            border: none;
            border-bottom: 1px dotted #000;
        }

        .pre-filled {
            border-bottom: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .footer {
            margin-top: 30px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .signature {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            margin-top: 70px;
            border-top: 1px solid #000;
        }

        .signature-name {
            margin-top: 10px;
            font-weight: bold;
        }

        .signature-title {
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>SURAT PERMOHONAN IJIN LEMBUR</h1>
        <h2>TVKU SEMARANG</h2>
    </div>

    <div class="recipient">
        <div class="recipient-line">
            <div class="recipient-label">Kepada</div>
            <div>: Staff HRD TVKU Semarang</div>
        </div>
        <div class="recipient-line">
            <div class="recipient-label">Hal</div>
            <div>: Permohonan Lembur Karyawan</div>
        </div>
    </div>>
    <div>Dengan ini saya, {{ $overtime[0]->user->name }}<div>

            <div class="form-field">
                <div class="form-label">Nama</div>
                <div>: <input type="text" class="form-input"></div>
            </div>
            <div class="form-field">
                <div class="form-label">Jabatan</div>
                <div>: <input type="text" class="form-input"></div>
            </div>
            <div class="form-field">
                <div class="form-label">Divisi</div>
                <div>: <span class="pre-filled">Teknik</span></div>
            </div>

            <div>Memohon untuk bekerja ekstra pada,</div>

            <table>
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
                    @foreach ($overtime as $item)
                    <tr>
                        <td>{{ $item['tanggal_overtime'] }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endforeach

                </tbody>
            </table>

            <div class="footer">
                <p>Demikian permohonan dari kami, atas persetujuannya kami ucapkan terimakasih</p>
            </div>

            <div class="signature-section">
                <div class="signature">
                    <div>Pemohon,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name"></div>
                </div>
                <div class="signature">
                    <div>Mengetahui,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">Eko Purwito</div>
                    <div class="signature-title">Manager Teknik</div>
                </div>
                <div class="signature">
                    <div>Menyetujui,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">Dr. Hery Pamungkas SS, M.I.Kom</div>
                    <div class="signature-title">Direktur Operasional</div>
                </div>
            </div>
</body>

</html>