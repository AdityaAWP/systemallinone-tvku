<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Surat Perintah Penugasan</title>
    <link href="https://fonts.cdnfonts.com/css/dejavu-sans" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }

        .header {
            position: relative;
            padding-left: 50px;
        }

        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
        }

        .company-info {
            font-weight: bold;
            font-size: 14px;
            margin-left: 20px;
        }

        .sub-info {
            font-size: 12px;
            font-weight: normal;
        }

        .document-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            text-decoration: underline;
            margin: 20px 0 0px;
        }

        .document-number {
            text-align: center;
            margin-bottom: 20px;
        }

        .content {
            text-align: justify;
            line-height: 1.5;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
        }

        .checkboxes {
            margin: 15px 0;
        }

        .qr-code {
            margin-top: -40px;
            width: 100px;
            height: 100px;
            margin-bottom: 10px;
        }

        .checkbox-item {
            margin-bottom: 5px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid black;
            margin-right: 5px;
            position: relative;
        }

        .checked::after {
            font-family: 'DejaVu Sans', sans-serif;
            content: "✓";
            position: absolute;
            top: -12px;
            left: 1px;
        }

        .footer {
            margin-top: 30px;
        }

        .signature {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }

        .sign-note {
            font-size: 10px;
            color: blue;
            margin-bottom: 5px;
        }

        .cc-list {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img class="logo" src="{{ public_path('images/tvku-logo.png') }}" alt="TVKU Logo">
        <div class="company-info">
            PT. TELEVISI KAMPUS UNIVERSITAS DIAN NUSWANTORO<br>
            <span class="sub-info"> JL.Nakula I No.5-11 Semarang</span>
        </div>
    </div>

    <div class="document-title">SURAT PERINTAH PENUGASAN</div>
    <div class="document-number">No. {{ $generatedSppNumber }}</div>

    <div class="content">
        <div>Berdasarkan :</div>
        
        @if ($assignment->type == \App\Models\Assignment::TYPE_PAID)
            <p>SPK Nomor {{ $generatedSpkNumber }} ({{ $assignment->client }} - {{ $assignment->description }})</p>
            @if($generatedInvoiceNumber)
                <p>{{ $generatedInvoiceNumber }}</p>
            @endif
        @elseif ($assignment->type == \App\Models\Assignment::TYPE_BARTER)
            <p>SPK Nomor {{ $generatedSpkNumber }} ({{ $assignment->client }} - {{ $assignment->description }})</p>
        @elseif ($assignment->type == \App\Models\Assignment::TYPE_FREE)
            <p>{{ $assignment->client }} - {{ $assignment->description }}</p>
        @endif

        <p>Dengan ini menugaskan Direktur Operasional untuk melakukan produksi maupun penayangan dengan ketentuan
            sebagai berikut:</p>

        <table>
            <tr>
                <td>Deadline Pengerjaan</td>
                <td>{{ \Carbon\Carbon::parse($assignment->deadline)->locale('id')->isoFormat('dddd, D MMMM YYYY')}}</td>
            </tr>
            <tr>
                <td>Info Waktu Produksi/Penayangan</td>
                <td>
                    {{$assignment->production_notes}}
                </td>
            </tr>
        </table>

        <div>Status Prioritas :</div>
        <div class="checkboxes">
            <div class="checkbox-item">
                <span class="checkbox @if($assignment->priority == \App\Models\Assignment::PRIORITY_VERY_IMPORTANT) checked @endif"></span>Sangat Penting
            </div>
            <div class="checkbox-item">
                <span class="checkbox @if($assignment->priority == \App\Models\Assignment::PRIORITY_IMPORTANT) checked @endif"></span>Penting
            </div>
            <div class="checkbox-item">
                <span class="checkbox @if($assignment->priority == \App\Models\Assignment::PRIORITY_NORMAL) checked @endif"></span>Biasa
            </div>
        </div>

        <p>Agar dilaksanakan sebaik-baiknya dengan penuh tanggung jawab</p>

        <p>Dokumen ini telah ditandatangani secara elektronik sehingga tidak diperlukan tanda tangan basah pada dokumen
            ini.</p>

        <div class="footer">
            <div style="text-align: left;">
                Semarang, {{ \Carbon\Carbon::parse($assignment->created_date)->locale('id')->isoFormat('D MMMM YYYY') }}
                <div>
                    Direktur Utama<br>
                    PT. Televisi Kampus Udinus
                </div>

                <div class="signature">
                    @if($qrCode)
                    <img src="data:image/png;base64,{{ $qrCode }}" class="qr-code">
                    @endif 
                    <div>
                        {{ $assignment->approver ? $assignment->approver->name : 'Dr. Guruh Fajar Shidik, S.Kom, M.CS' }}
                    </div>
                </div>
            </div>

            <div class="cc-list" style="margin-top: -5px">
                <div>Tembusan :</div>
                <ol style="margin-top: -5px">
                    <li>Manager Operasional</li>
                    <li>Manager Teknik</li>
                    <li>Manager Marketing</li>
                </ol>
            </div>
        </div>
    </div>
</body>

</html>