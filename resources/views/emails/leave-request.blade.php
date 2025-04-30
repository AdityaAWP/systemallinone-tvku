<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Tailwind-inspired utility classes */
        .font-poppins {
            font-family: 'Poppins', sans-serif;
        }
        
        @media only screen and (max-width: 640px) {
            .sm-w-full {
                width: 100% !important;
            }
            .sm-px-4 {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
            .sm-py-8 {
                padding-top: 32px !important;
                padding-bottom: 32px !important;
            }
        }
        
        /* Modern design styles */
        body, body *:not(html):not(style):not(br):not(tr):not(code) {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }
        
        body {
            -webkit-text-size-adjust: none;
            background-color: #f7fafc;
            color: #4a5568;
            height: 100%;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        
        .button {
            border-radius: 8px;
            color: #ffffff;
            display: inline-block;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 24px;
            margin: 0 12px 12px 0;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        .button-approve {
            background-color: #4CAF50;
            border: none;
        }
        
        .button-reject {
            background-color: #F44336;
            border: none;
        }
        
        .content-wrapper {
            max-width: 100vw;
            padding: 24px;
        }
        
        .header {
            padding: 24px 0;
            text-align: center;
        }
        
        .header a {
            color: #2d3748;
            font-size: 22px;
            font-weight: 700;
            text-decoration: none;
        }
        
        .logo {
            height: 60px;
            max-height: 60px;
            width: auto;
        }
        
        .body-wrapper {
            background-color: #f7fafc;
            padding: 32px 0;
            width: 100%;
        }
        
        .card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
            max-width: 600px;
            padding: 40px;
            width: 100%;
        }
        
        .text {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-top: 0;
            margin-bottom: 16px;
        }
        
        .text-sm {
            font-size: 14px;
        }
        
        .text-muted {
            color: #718096;
        }
        
        .text-primary {
            color: #5a67d8;
        }
        
        .greeting {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
        }
        
        .section-title {
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 8px;
        }
        
        .details-container {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        
        .detail-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
        }
        
        .detail-value {
            color: #2d3748;
            font-size: 14px;
            font-weight: 600;
            flex: 2;
            text-align: right;
        }
        
        .button-container {
            display: flex;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        
        .divider {
            height: 1px;
            width: 100%;
            background-color: #e2e8f0;
            margin: 32px 0;
        }
        
        .footer {
            color: #a0aec0;
            font-size: 12px;
            text-align: center;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="body-wrapper">
        <div class="content-wrapper">
            <div class="header">
                <a href="{{ config('app.url') }}" class="logo-link">
                    {{ config('app.name') }}
                </a>
            </div>
            
            <div class="card">
                <h1 class="greeting">Halo, {{ $name }}</h1>
                
                <p class="text">
                    <span class="text-primary font-semibold">{{ $requester }}</span> telah mengajukan cuti dan membutuhkan persetujuan Anda.
                </p>
                
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
                    
                    <div class="detail-row">
                        <span class="detail-label">Alasan</span>
                        <span class="detail-value">{{ $reason }}</span>
                    </div>
                </div>
                
                <p class="section-title">Tindakan</p>
                <p class="text">
                    Silakan berikan keputusan Anda terhadap permintaan cuti ini:
                </p>
                
                <div class="button-container">
                    <a href="{{ $approveUrl }}" class="button button-approve" target="_blank">Setujui</a>
                    <a href="{{ $rejectUrl }}" class="button button-reject" target="_blank">Tolak</a>
                </div>
                
                <div class="divider"></div>
                
                <p class="text text-sm text-muted">
                    Email ini dikirim secara otomatis. Mohon tinjau permintaan cuti ini secepatnya.
                </p>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta dilindungi.</p>
            </div>
        </div>
    </div>
</body>
</html>