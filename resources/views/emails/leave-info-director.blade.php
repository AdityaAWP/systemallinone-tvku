<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Cuti Manager/Kepala</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .info-item {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-item strong {
            color: #4a5568;
            font-weight: 600;
        }
        .info-item span {
            color: #718096;
            display: block;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            background-color: #48bb78;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 10px 0;
        }
        .reason-box {
            background-color: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4299e1;
            margin: 20px 0;
        }
        .footer {
            background-color: #f7fafc;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #718096;
        }
        .info-notice {
            background-color: #e6fffa;
            border: 1px solid #38b2ac;
            color: #234e52;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Informasi Cuti Manager/Kepala</h1>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $name }}</strong>,</p>
            
            <div class="info-notice">
                <strong>ℹ️ Untuk Informasi Saja</strong><br>
                Cuti Manager/Kepala telah otomatis disetujui. Email ini hanya untuk memberikan informasi kepada Anda.
            </div>
            
            <p>Berikut adalah informasi cuti yang telah diajukan oleh Manager/Kepala:</p>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nama Pengaju</strong>
                    <span>{{ $requester }}</span>
                </div>
                <div class="info-item">
                    <strong>Jenis Cuti</strong>
                    <span>{{ $leaveType }}</span>
                </div>
                <div class="info-item">
                    <strong>Tanggal Mulai</strong>
                    <span>{{ $fromDate }}</span>
                </div>
                <div class="info-item">
                    <strong>Tanggal Selesai</strong>
                    <span>{{ $toDate }}</span>
                </div>
                <div class="info-item">
                    <strong>Durasi</strong>
                    <span>{{ $days }} hari</span>
                </div>
                <div class="info-item">
                    <strong>Status</strong>
                    <span class="status-badge">✅ Approved</span>
                </div>
            </div>
            
            <div class="reason-box">
                <strong>Alasan Cuti:</strong>
                <p style="margin: 10px 0 0 0;">{{ $reason }}</p>
            </div>
            
            <p style="color: #718096; font-size: 14px; margin-top: 30px;">
                <strong>Catatan:</strong> Sebagai direktur, Anda hanya menerima informasi ini untuk mengetahui status cuti Manager/Kepala. 
                Tidak diperlukan tindakan approval dari Anda karena cuti Manager/Kepala telah otomatis disetujui.
            </p>
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis dari Sistem Manajemen Cuti TVKU.<br>
            Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
