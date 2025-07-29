<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        /* Reset and base styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: #E4EFE7;
            margin: 0;
            padding: 0;
            color: #4a5568;
            line-height: 1.6;
        }

        /* Card styling */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #F7F7F7;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Header */
        .email-header {
            background: linear-gradient(to right, #1f2937, #3b82f6);
            color: #ffffff !important;
            padding: 20px;
            text-align: center;
        }

        .email-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff !important;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        /* Body content */
        .email-body {
            padding: 24px;
            font-family: 'Poppins', sans-serif;
        }

        .greeting {
            color: #1a202c;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-message {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }

        .status-approved {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .status-rejected {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .badge-approved {
            background: #dcfce7;
            color: #166534;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .section-title {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .details-container {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: #222831;
            width: 120px;
        }

        .detail-value {
            font-weight: 500;
            color: #2d3748;
            flex: 1;
        }

        .reason-box {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
            padding: 16px;
            margin: 16px 0;
            font-family: 'Poppins', sans-serif;
        }

        .rejection-reason {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            padding: 16px;
            margin: 16px 0;
            font-family: 'Poppins', sans-serif;
        }

        /* Button */
        .button-container {
            text-align: center;
            margin: 24px 0;
        }

        .button {
            border-radius: 8px;
            color: #ffffff !important;
            display: inline-block;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 24px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #1f2937, #3b82f6);
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        /* Footer */
        .footer {
            text-align: center;
            background: #393E46;
            color: #ffffff !important;
            padding: 15px;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 16px;
            }

            .detail-row {
                flex-direction: column;
            }

            .detail-label {
                width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h2>Status Permintaan Cuti Anda</h2>
        </div>

        <div class="email-body">
            <h1 class="greeting">Halo, {{ $name }}</h1>

            <!-- Status Message -->
            <div class="status-message @if($status === 'approved') status-approved @elseif($status === 'rejected') status-rejected @endif">
                <div class="status-badge @if($status === 'approved') badge-approved @elseif($status === 'rejected') badge-rejected @else badge-pending @endif">
                    @if($status === 'approved')
                        ✅ Cuti Disetujui
                    @elseif($status === 'rejected')
                        ❌ Cuti Ditolak
                    @else
                        ⏳ Menunggu Persetujuan
                    @endif
                </div>
                <p style="margin: 0; font-weight: 500;">
                    Permintaan cuti <strong>{{ $leaveType }}</strong> Anda telah <strong>{{ $statusText }}</strong>{{ $approver }}.
                </p>
            </div>

            <h2 class="section-title">Detail Cuti</h2>

            <div class="details-container">
                <div class="detail-row">
                    <span class="detail-label">Jenis Cuti</span>
                    <span class="detail-value">{{ $leaveType }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durasi</span>
                    <span class="detail-value">{{ $days }} hari</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Mulai</span>
                    <span class="detail-value">{{ $fromDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Selesai</span>
                    <span class="detail-value">{{ $toDate }}</span>
                </div>
            </div>

            @if($reason)
            <h2 class="section-title">Alasan Cuti</h2>
            <div class="reason-box">
                {{ $reason }}
            </div>
            @endif

            @if($rejectionReason)
            <h2 class="section-title">Alasan Penolakan</h2>
            <div class="rejection-reason">
                {{ $rejectionReason }}
            </div>
            @endif

            <div class="button-container">
                <a href="{{ $detailUrl }}" class="button" target="_blank">Lihat Detail Lengkap</a>
            </div>

            <p style="color: #718096; font-size: 14px; text-align: center; margin-top: 20px;">
                Terima kasih telah menggunakan sistem manajemen cuti TVKU.<br>
                Jika ada pertanyaan, silakan hubungi HRD.
            </p>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
            <p>© {{ date('Y') }} TVKU. Seluruh hak cipta dilindungi.</p>
        </div>
    </div>
</body>

</html>
