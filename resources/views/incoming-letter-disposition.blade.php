<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi - {{ $record->reference_number ?? '' }}</title>
    <style>
        @page {
            margin: 10mm 15mm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /* Header Section */
        .header-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .logo-cell {
            display: table-cell;
            width: 85px;
            vertical-align: top;
            padding-top: 8px;
        }
        .logo {
            width: 70px;
            height: 38px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            line-height: 36px;
        }
        .company-info-cell {
            display: table-cell;
            vertical-align: top;
            padding-left: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .company-details {
            font-size: 9px;
            margin: 0;
            line-height: 1.3;
        }

        /* Title */
        .title-section {
            text-align: center;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 4px;
            text-decoration: underline;
            text-transform: uppercase;
        }

        /* Main Form Container */
        .main-container {
            border: 1px solid #000;
        }
        
        /* Layout Grid using Tables */
        .form-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .form-row {
            display: table-row;
        }
        .form-cell {
            display: table-cell;
            padding: 6px 10px;
            vertical-align: top;
        }

        /* Specific Cell and Border Styling */
        .left-column {
            width: 65%;
            border-right: 1px solid #000; /* Vertical divider */
        }
        .right-column {
            width: 35%;
        }
        .top-section {
            border-bottom: 1px solid #000;
        }
        .bottom-section {
            border-top: 1px solid #000;
        }
        
        /* Top Section Field Styling (Agenda) */
        .top-field-container {
            display: table;
            width: 100%;
        }
        .top-field-label {
            display: table-cell;
            white-space: nowrap;
            padding-right: 5px;
        }
        .top-field-value {
            display: table-cell;
            width: 100%;
            border-bottom: 1px dotted #000;
        }

        /* Aligned Fields in Left Column */
        .aligned-field, .aligned-field-multiline {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .af-label {
            display: table-cell;
            width: 70px; /* Fixed width for alignment */
            vertical-align: middle;
            padding-right: 5px;
        }
        .af-separator {
            display: table-cell;
            width: 10px;
            vertical-align: middle;
        }
        .af-value {
            display: table-cell;
            border-bottom: 1px dotted #000;
            height: 18px;
            padding-left: 3px;
        }
        .aligned-field-multiline .af-label, .aligned-field-multiline .af-separator {
            vertical-align: top;
            padding-top: 2px;
        }
        .af-value-container {
            display: table-cell;
            padding-left: 3px;
        }
        .af-value-line {
            border-bottom: 1px dotted #000;
            min-height: 20px;
        }
        .af-value-line:not(:last-child) {
            margin-bottom: 12px;
        }

        /* Priority List in Right Column (USING TABLES FOR DOMPDF) */
        .priority-item {
            display: table;
            width: 100%;
            margin: 12px 0;
            font-size: 11px;
        }
        .priority-text, .priority-box {
            display: table-cell;
            vertical-align: middle;
        }
        .priority-text {
            text-align: left;
        }
        .priority-box {
            text-align: right;
            white-space: nowrap;
        }
        

        /* Bottom Section: Disposition and Signature */
        .keterangan-field {
             min-height: 60px;
             border-bottom: 1px dotted #000;
             margin-top: 2px;
             font-size: 11px;
             padding: 2px;
        }
        .diteruskan-field {
             min-height: 25px;
             border-bottom: 1px dotted #000;
             margin-top: 2px;
        }
        .signature-area {
            text-align: right;
            font-size: 11px;
            padding-top: 25px;
        }
        .signature-date-value {
             border-bottom: 1px dotted #000;
             display: inline-block;
             width: 180px;
             height: 18px;
        }

    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-section">
        <div class="logo-cell">
            <div class="logo"> <img class="logo" src="{{ public_path('images/tvku-logo.png') }}" alt="TVKU Logo">
        </div>
        </div>
        <div class="company-info-cell">
            <div class="company-name">PT. TELEVISI KAMPUS UNIVERSITAS DIAN NUSWANTORO</div>
            <div class="company-details">Jl. Nakula I No. 5 - 11, Gedung E Lt. 2 Kompleks Udinus Semarang</div>
            <div class="company-details">Telp. 024 - 3568491, Fax. 024 - 3564645</div>
            <div class="company-details">Homepage: tvku.tv, email: tvku.semarang@tvku.tv</div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-section">
        LEMBAR DISPOSISI
    </div>
    
    <!-- Main Form -->
    <div class="main-container">
        <div class="form-grid">
            <!-- Top Row: Agenda Date and Number -->
            <div class="form-row">
                <div class="form-cell left-column top-section">
                    <div class="top-field-container">
                        <div class="top-field-label">TANGGAL AGENDA :</div>
                        <div class="top-field-value">{{ $record->received_date ? $record->received_date->format('d / m / Y') : '' }}</div>
                    </div>
                </div>
                <div class="form-cell right-column top-section">
                    <div class="top-field-container">
                        <div class="top-field-label">NO. AGENDA :</div>
                        <div class="top-field-value">{{ $record->reference_number ?? '' }}</div>
                    </div>
                </div>
            </div>

            <!-- Middle Row: Main content -->
            <div class="form-row">
                <!-- Left Column: Letter Details -->
                <div class="form-cell left-column">
                    <div class="aligned-field">
                        <div class="af-label">DARI</div>
                        <div class="af-separator">:</div>
                        <div class="af-value">{{ $record->sender ?? '' }}</div>
                    </div>
                    <div class="aligned-field">
                        <div class="af-label">NOMOR</div>
                        <div class="af-separator">:</div>
                        <div class="af-value">{{ $record->letter_number ?? '' }}</div>
                    </div>
                    <div class="aligned-field">
                        <div class="af-label">TANGGAL</div>
                        <div class="af-separator">:</div>
                        <div class="af-value">{{ $record->letter_date ? $record->letter_date->format('d / m / Y') : '' }}</div>
                    </div>
                    <div class="aligned-field-multiline">
                        <div class="af-label">HAL</div>
                        <div class="af-separator">:</div>
                        <div class="af-value-container">
                            <div class="af-value-line">{{ $record->subject ?? '' }}</div>
                            <div class="af-value-line"></div>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Priority (DOMPDF Compatible) -->
                <div class="form-cell right-column">
                    <div class="priority-item">
                        <div class="priority-text">RAHASIA</div>
                        <div class="priority-box">(___)</div>
                    </div>
                    <div class="priority-item">
                        <div class="priority-text">PENTING</div>
                        <div class="priority-box">(___)</div>
                    </div>
                    <div class="priority-item">
                        <div class="priority-text">SEGERA</div>
                        <div class="priority-box">(___)</div>
                    </div>
                    <div class="priority-item">
                        <div class="priority-text">BIASA</div>
                        <div class="priority-box">(___)</div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Disposition and Notes -->
            <div class="form-row">
                <div class="form-cell full-width-cell bottom-section" colspan="2">
                    <div>Diteruskan Ke :</div>
                    <div class="diteruskan-field"></div>
                    
                    <div style="margin-top: 15px;">Keterangan :</div>
                    <div class="keterangan-field">{{ $record->notes ?? '' }}</div>
                    
                    <div class="signature-area">
                        Semarang, <span class="signature-date-value"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($record->content)
    <div style="page-break-before: always; margin-top: 20px;">
        <div style="border: 1px solid #000; padding: 15px;">
            <div style="font-weight: bold; margin-bottom: 10px; font-size: 11px;">ISI SURAT:</div>
            <div style="line-height: 1.4; font-size: 11px;">
                {!! nl2br(e(strip_tags($record->content))) !!}
            </div>
        </div>
    </div>
    @endif
</body>
</html>