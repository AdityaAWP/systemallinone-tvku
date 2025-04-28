<!DOCTYPE html>
<html>

<head>
    <title>Dokumen Peminjaman Logistik</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            /* Untuk penempatan logo */
            padding-left: 50px;
            /* Ruang untuk logo */
            min-height: 80px;
            /* Minimal tinggi header untuk logo */
        }

        img {
            margin-left: 100px;
        }

        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .address {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .contact {
            font-size: 12px;
            margin-bottom: 15px;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.info td {
            border: 1px solid #000;
            padding: 5px;
        }

        .category-row td {
            background-color: #e8e8e8;
        }

        .checkbox {
            border: 1px solid #000;
            width: 15px;
            height: 15px;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            margin-right: 5px;
        }

        .checkbox.checked::before {
            content: "✓";
            font-size: 14px;
            line-height: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .items-table .category {
            width: 80px;
            font-weight: bold;
        }

        .signatures {
            width: 100%;
            margin-top: 20px;
            text-align: center;
        }

        .signature-box {
            display: inline-block;
            width: 30%;
            text-align: center;
            vertical-align: top;
        }

        .signature-box .check {
            border: 1px solid #000;
            width: 30px;
            height: 30px;
            margin: 0 auto 10px;
            text-align: center;
            line-height: 30px;
            font-size: 24px;
        }

        .signature-box .check.checked::before {
            content: "✓";
        }

        .signature-box .title {
            margin-bottom: 10px;
        }

        .signature-box .name {
            font-weight: bold;
        }

        .contact-table {
            width: 100%;
            border-collapse: collapse;
        }

        .contact-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('images/tvku-logo.png') }}" class="logo" alt="TVKU Logo">
        <div class="title">TELEVISI KAMPUS UNIVERSITAS DIAN NUSWANTORO</div>
        <div class="address">Gedung E Lt.2 Kompleks UDINUS Jl. Nakula I/No.5-11 Semarang 50131</div>
        <div class="contact">Telp. (024)356-8491 Fax. (024)356-4645</div>
    </div>

    <div class="document-title">DOKUMEN PEMINJAMAN LOGISTIK (ID : {{ $loanitem->id }})</div>

    <table class="info">
        <tr>
            <td width="120">Pengembalian</td>
            <td>{{ $loanitem->return_status }}</td>
            <td width="120">Tanggal Dibuat</td>
            <td>{{ $loanitem->created_at ? $loanitem->created_at->format('d-m-Y H:i') : date('d-m-Y H:i') }}</td>
        </tr>
        <tr>
            <td>Program</td>
            <td>{{ $loanitem->program }}</td>
            <td>Tanggal Booking</td>
            <td>{{ \Carbon\Carbon::parse($loanitem->booking_date)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Lokasi</td>
            <td>{{ $loanitem->location }}</td>
            <td>Jam Booking</td>
            <td>{{ \Carbon\Carbon::parse($loanitem->start_booking)->format('H:i') }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 10px;">
        @php
        $division = $loanitem->division;
        $isProduksi = $division === 'produksi';
        $isNews = $division === 'news';
        $isStudio = $division === 'studio';
        $isMarketing = $division === 'marketing';
        $isLainlain = $division === 'lain-lain';
        @endphp
        <span class="checkbox {{ $isProduksi ? 'checked' : '' }}"></span> Produksi
        <span class="checkbox {{ $isNews ? 'checked' : '' }}"></span> News
        <span class="checkbox {{ $isStudio ? 'checked' : '' }}"></span> Studio
        <span class="checkbox {{ $isMarketing ? 'checked' : '' }}"></span> Marketing
        <span class="checkbox {{ $isLainlain ? 'checked' : '' }}"></span> Lain-lain
    </div>

    <!-- Items Table with both individual items and category grouping -->
    <table class="items-table">
        @php
        // Group items by category
        $itemsByCategory = [];
        // Predefined categories
        $categories = ['Video', 'Audio', 'Lighting', 'Lain-lain'];

        // Grouping items based on their category
        foreach ($loanitem->items as $item) {
        $category = $item->category ?? 'Lain-lain';
        if (!isset($itemsByCategory[$category])) {
        $itemsByCategory[$category] = [];
        }
        $itemsByCategory[$category][] = $item->name . ' (' . $item->pivot->quantity . ')';
        }
        @endphp

        <!-- Loop through categories and display items -->
        @foreach ($categories as $category)
        <tr>
            <td class="category">{{ $category }}</td>
            <td>
                @if (isset($itemsByCategory[$category]))
                {{ implode(' , ', $itemsByCategory[$category]) }}
                @endif
            </td>
        </tr>
        @endforeach

        <tr>
            <td class="category">Catatan</td>
            <td>{{ $loanitem->notes ?? 'Kebutuhan lainnya menyesuaikan' }}</td>
        </tr>
    </table>

    <!-- Debug information (remove in production) -->
    <div style="font-size: 8px; color: #999; margin: 5px 0;">
        @foreach($loanitem->items as $item)
        Item: {{ $item->name }}, Category: "{{ $item->category ?? 'NULL' }}", Quantity: {{ $item->pivot->quantity }}<br>
        @endforeach
    </div>

    <div class="section-title">Requested</div>

    <div class="signatures">
        <div class="signature-box">
            <div class="check checked"></div>
            <div>Produser/Ass.</div>
        </div>

        <div class="signature-box">
            <div class="check checked"></div>
            <div>Campers</div>
        </div>

        <div class="signature-box">
            <div class="check {{ $loanitem->approval_admin_logistics ? 'checked' : '' }}"></div>
            <div>Logistik</div>
        </div>
    </div>

    <table class="contact-table">
        <tr>
            <td width="60">Nama</td>
            <td>{{ $loanitem->producer_name }}</td>
            <td width="60">Nama</td>
            <td>{{ $loanitem->crew_name }}</td>
            <td width="60">Nama</td>
            <td>{{ $loanitem->approver_name }}</td>
        </tr>
        <tr>
            <td>HP</td>
            <td>{{ $loanitem->producer_telp }}</td>
            <td>HP</td>
            <td>{{ $loanitem->crew_telp }}</td>
            <td>HP</td>
            <td>{{ $loanitem->approver_telp }}</td>
        </tr>
    </table>
</body>

</html>