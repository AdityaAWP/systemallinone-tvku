<!DOCTYPE html>
<html>

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
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
        }

        .email-header h2 {
            font-size: 20px;
            font-weight: 600;
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
            color: #1a202c;
            margin-bottom: 15px;
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

        /* Buttons */
        .button-container {
            display: flex;
            margin: 24px 0;
            flex-wrap: wrap;
        }

        .button {
            border-radius: 8px;
            color: #ffffff !important;
            display: inline-block;
            font-weight: 600;
            text-decoration: none;
            padding: 11px 22px;
            font-size: 12px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: 'Poppins', sans-serif;
            margin-right: 10px; /* Add spacing between buttons */
        }

        .button-container .button:last-child {
            margin-right: 0; /* Remove margin for the last button */
        }

        .button-approve {
            background-color: #3b82f6;
        }

        .button-reject {
            background-color: #F44336;
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

            .button-container {
                flex-direction: column;
            }

            .button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header matching TVKU design -->
        <div class="email-header">
            <h2>Permintaan Persetujuan Cuti</h2>
        </div>

        <div class="email-body">
            <h1 class="greeting">Halo, {{ $name }}</h1>

            <p class="text">
                        <span class="text-primary font-semibold">{{ $requester }}</span> telah mengajukan cuti dan membutuhkan persetujuan Anda.
                    </p>

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

            <h2 class="section-title">Alasan Cuti</h2>
            <div class="reason-box">
                {{ $reason }}
            </div>

            <h2 class="section-title">Tindakan</h2>
            <p>Silakan berikan keputusan Anda terhadap permintaan cuti ini:</p>

            <div class="button-container">
                <a href="{{ $approveUrl }}" class="button button-approve" target="_blank">Setujui</a>
                <a href="{{ $rejectUrl }}" class="button button-reject" target="_blank">Tolak</a>
            </div>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon tinjau permintaan cuti ini secepatnya.</p>
            <p>© {{ date('Y') }} TVKU. Seluruh hak cipta dilindungi.</p>
        </div>
    </div>
</body>

</html>