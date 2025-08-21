# SISTEM ALL IN ONE TVKU

## Comprehensive Human Resource & Document Management System

![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=flat-square&logo=php)
![Filament](https://img.shields.io/badge/Filament-3.x-orange?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Sistem manajemen sumber daya manusia terintegrasi yang dirancang khusus untuk TVKU, mengelola seluruh aspek HR mulai dari manajemen intern, karyawan, hingga administrasi dokumen dengan interface yang modern dan user-friendly.

# ERROR HANDLING DOCUMENTATION

![Error Upload](/storage/app/private/md1.jpeg)

## Tambahkan header ini di config virtual host

Apache (/etc/apache2/sites-available/xxx.conf):

## Tambahkan ini supaya Laravel tahu request via HTTPS

SetEnvIf X-Forwarded-Proto https HTTPS=on

## 📋 Table of Contents

### 🚀 [Getting Started](#getting-started)

-   [Fitur Utama](#-fitur-utama)
-   [Tech Stack](#️-tech-stack)
-   [Requirements](#-requirements)
-   [Quick Installation](#-quick-installation)

### 📖 [Installation Guide](#-installation-guide)

-   [Environment Setup](#1-persiapan-environment)
-   [Dependencies Installation](#3-install-dependencies)
-   [Database Configuration](#5-setup-database)
-   [Email & Queue Setup](#9-setup-email-dan-queue-system)
-   [Production Deployment](#-konfigurasi-production)

### 👨‍💻 [User Guide](#-user-guide)

-   [User Roles](#role-dan-hak-akses)
-   [Login & Dashboard](#login-dan-dashboard)
-   [Intern Management](#manajemen-intern)
-   [Daily Reports](#laporan-harian)
-   [Leave Management](#manajemen-cuti)
-   [Document Management](#sistem-surat-menyurat)
-   [Inventory System](#manajemen-inventaris)

### 📧 [Email & Queue System](#-email--queue-system)

-   [Queue Configuration](#queue-configuration)
-   [Email Providers Setup](#email-providers-setup)
-   [Running Workers](#running-queue-workers)
-   [Monitoring & Troubleshooting](#monitoring--troubleshooting)

### 🔧 [Development Guide](#-development-guide)

-   [Project Structure](#project-structure)
-   [Coding Standards](#coding-standards)
-   [Testing](#testing)
-   [API Documentation](#api-endpoints)

### ❓ [FAQ & Support](#-faq--support)

-   [Common Issues](#common-issues)
-   [Troubleshooting](#troubleshooting-guide)
-   [Contact Support](#contact-support)

---

## Getting Started

### 🚀 Fitur Utama

#### 👥 Manajemen SDM

-   **Data Intern & Karyawan**: Pengelolaan data lengkap dengan profil, divisi, dan status
-   **Role & Permission**: Sistem hak akses berbasis role yang fleksibel
-   **User Management**: Multi-level user dengan dashboard khusus

#### 📊 Laporan & Monitoring

-   **Daily Reports**: Laporan harian aktivitas intern dengan approval workflow
-   **Analytics Dashboard**: Visualisasi data dan metrics performa
-   **Custom Reports**: Generator laporan dengan filter dan export multiple format

#### 🏖️ Leave Management

-   **Pengajuan Cuti**: Sistem pengajuan cuti dengan approval bertingkat
-   **Leave Quota**: Manajemen kuota cuti per user/divisi
-   **Calendar Integration**: Integrasi dengan kalender untuk planning

#### ⏰ Overtime Management

-   **Request Overtime**: Pengajuan lembur dengan tracking jam kerja
-   **Approval Workflow**: Sistem persetujuan supervisor dan HR
-   **Reporting**: Laporan overtime dengan analisis productivity

#### 📧 Document Management

-   **Surat Masuk/Keluar**: Sistem tracking surat dengan disposisi
-   **Letter Templates**: Template surat otomatis dengan numbering
-   **Digital Archive**: Penyimpanan digital dengan search function

#### 📦 Inventory Management

-   **Asset Tracking**: Tracking inventaris dengan QR Code
-   **Loan System**: Sistem peminjaman barang dengan approval
-   **Maintenance**: Schedule maintenance dan audit inventory

#### 🔧 Advanced Features

-   **Export System**: Export ke Excel, PDF dengan custom templates
-   **Backup System**: Auto backup dengan cloud integration
-   **Notification**: Real-time notification untuk approval dan reminder
-   **Mobile Responsive**: Interface yang responsif untuk mobile device

### 🛠️ Tech Stack

-   **Backend**: Laravel 11.x (PHP 8.2+)
-   **Frontend**: Filament 3.x Admin Panel
-   **UI Framework**: Tailwind CSS
-   **Database**: MySQL/PostgreSQL/SQLite
-   **Build Tool**: Vite
-   **PDF Generation**: DomPDF
-   **Excel**: Maatwebsite Excel
-   **Authentication**: Laravel Sanctum
-   **Permissions**: Spatie Laravel Permission
-   **Backup**: Spatie Laravel Backup

### 📋 Requirements

-   **PHP**: 8.2 atau lebih tinggi
-   **Node.js**: 18.x atau lebih tinggi
-   **Composer**: Latest version
-   **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite
-   **Extensions**: ext-gd, ext-mbstring, ext-xml, ext-zip

### 🚀 Quick Installation

```bash
# 1. Clone repository
git clone https://github.com/AdityaAWP/systemallinone-tvku.git
cd systemallinone-tvku

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Database setup
php artisan migrate
php artisan db:seed

# 5. Build assets & run
npm run dev
php artisan serve
```

Akses aplikasi di `http://localhost:8000/admin`

---

## 📖 Installation Guide

### Requirements Detail

#### Software Requirements

-   **PHP**: >= 8.2
-   **Node.js**: >= 18.x
-   **Composer**: Latest version
-   **Database**: MySQL 8.0+ atau PostgreSQL 13+ atau SQLite
-   **Web Server**: Apache/Nginx (untuk production)

#### PHP Extensions Required

-   ext-gd (untuk image processing)
-   ext-mbstring
-   ext-imagick
-   ext-pdo
-   ext-tokenizer
-   ext-xml
-   ext-ctype
-   ext-json
-   ext-zip
-   ext-fileinfo

#### System Requirements

-   **RAM**: Minimum 2GB (Recommended 4GB+)
-   **Storage**: Minimum 1GB free space
-   **OS**: Windows 10+, macOS 10.15+, atau Linux Ubuntu 20.04+

### 1. Persiapan Environment

#### Install PHP 8.2+

**Windows:**

```powershell
# Download PHP dari https://windows.php.net/download/
# Atau gunakan XAMPP/Laragon untuk kemudahan
```

**macOS:**

```bash
brew install php@8.2
```

**Ubuntu/Debian:**

```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-curl
```

#### Install Composer

```powershell
# Download dari https://getcomposer.org/download/
# Jalankan installer untuk Windows
```

#### Install Node.js

```powershell
# Download dari https://nodejs.org/
# Install versi LTS terbaru
```

### 2. Clone Repository

```powershell
git clone https://github.com/AdityaAWP/systemallinone-tvku.git
cd systemallinone-tvku
```

### 3. Install Dependencies

#### Install PHP Dependencies

```powershell
composer install
```

#### Install Node.js Dependencies

```powershell
npm install
```

### 4. Konfigurasi Environment

#### Copy Environment File

```powershell
cp .env.example .env
```

#### Generate Application Key

```powershell
php artisan key:generate
```

#### Edit File .env

Buka file `.env` dan sesuaikan konfigurasi berikut:

```env
# Aplikasi
APP_NAME="Sistem All In One TVKU"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE="Asia/Jakarta"

# Database Configuration
# Untuk SQLite (Recommended untuk development)
DB_CONNECTION=sqlite

# Atau untuk MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=systemallinone_tvku
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="admin@tvku.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration (Required untuk email)
QUEUE_CONNECTION=database

# Cache Configuration
CACHE_STORE=database

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### 5. Setup Database

#### Untuk SQLite (Recommended untuk development)

```powershell
# Buat file database SQLite
New-Item -Path "database\database.sqlite" -ItemType File -Force
```

#### Untuk MySQL

1. Buat database baru:

```sql
CREATE DATABASE systemallinone_tvku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Update konfigurasi database di `.env`

### 6. Jalankan Migrasi dan Seeder

```powershell
# Jalankan migrasi database
php artisan migrate

# Jalankan seeder (data sample)
php artisan db:seed
```

### 7. Setup Storage dan Permissions

```powershell
# Buat storage link
php artisan storage:link

# Set permissions (untuk Linux/macOS)
# chmod -R 755 storage
# chmod -R 755 bootstrap/cache
```

### 8. Install Filament Shield (Permission System)

```powershell
# Publish Filament Shield
php artisan vendor:publish --tag="filament-shield-config"

# Generate permissions
php artisan shield:generate --all
```

### 9. Setup Email dan Queue System

#### Konfigurasi Email (Production)

Untuk production, update konfigurasi email di `.env`:

```env
# Gmail SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="admin@tvku.com"
MAIL_FROM_NAME="Sistem All In One TVKU"

# Queue Configuration (Required)
QUEUE_CONNECTION=database
```

**Setup Gmail App Password:**

1. Login ke Google Account Settings
2. Enable 2-Factor Authentication
3. Generate App Password untuk aplikasi
4. Gunakan App Password sebagai `MAIL_PASSWORD`

#### Setup Queue Worker untuk Email

**Development (Windows PowerShell):**

```powershell
# Jalankan queue worker
php artisan queue:work

# Atau dengan timeout dan retry
php artisan queue:work --timeout=60 --tries=3

# Monitor queue jobs
php artisan queue:listen --tries=1
```

**Test Email Configuration:**

```powershell
# Test email via tinker
php artisan tinker

# Di dalam tinker, jalankan:
Mail::raw('Test email dari sistem TVKU', function($message) {
    $message->to('test@example.com')->subject('Test Email');
});

# Cek queue jobs
php artisan queue:work --once
```

### 10. Build Assets

```powershell
# Untuk development
npm run dev

# Untuk production
npm run build
```

### 11. Jalankan Server Development

**Opsi 1: Manual (3 Terminal terpisah)**

````powershell
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Queue Worker (untuk email & notifikasi)
php artisan queue:work --tries=3


**Opsi 2: All-in-One dengan Composer Script (Recommended)**
```powershell
composer run dev
````

Script `composer run dev` akan menjalankan:

-   Laravel development server (http://localhost:8000)
-   Queue worker untuk background jobs (email, notifications)
-   Log monitoring dengan Pail
-   Vite development server untuk hot reloading

### 🔍 Verifikasi Instalasi

#### 1. Akses Aplikasi

Buka browser dan akses: `http://localhost:8000`

#### 2. Login Admin

-   URL Admin: `http://localhost:8000/admin`
-   Default admin credentials akan tersedia setelah menjalankan seeder

#### 3. Test Fitur Utama

-   ✅ Dashboard loading correctly
-   ✅ User management working
-   ✅ Database connections established
-   ✅ File uploads working
-   ✅ Export functionality working

### 🛠️ Konfigurasi Production

#### 1. Environment Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

#### 2. Optimize untuk Production

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer install --optimize-autoloader --no-dev
npm run build
```

#### 3. Setup Queue Worker (Production)

**Supervisor Configuration (Linux):**

```bash
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/systemallinone-tvku/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/systemallinone-tvku/storage/logs/worker.log
stopwaitsecs=3600
```

**Windows Service dengan NSSM:**

```powershell
# Download NSSM dari https://nssm.cc/
nssm install "Laravel Queue Worker" "C:\php\php.exe"
nssm set "Laravel Queue Worker" AppParameters "C:\path\to\systemallinone-tvku\artisan queue:work --tries=3"
nssm set "Laravel Queue Worker" AppDirectory "C:\path\to\systemallinone-tvku"
nssm start "Laravel Queue Worker"
```

---

## 👨‍💻 User Guide

### Role dan Hak Akses

Sistem ini memiliki beberapa level pengguna dengan hak akses berbeda:

**1. Super Admin**

-   Akses penuh ke semua fitur
-   Manajemen user dan role
-   Konfigurasi sistem

**2. Admin HR**

-   Manajemen data karyawan dan intern
-   Persetujuan cuti dan overtime
-   Akses laporan comprehensive

**3. Supervisor/Manager**

-   Manajemen divisi
-   Persetujuan laporan dan tugas
-   View laporan tim

**4. Intern/Staff**

-   Input laporan harian
-   Pengajuan cuti dan overtime
-   View data personal

### Login dan Dashboard

#### Cara Login

1. Buka browser dan akses `http://localhost:8000/admin`
2. Masukkan email dan password yang diberikan oleh admin
3. Klik "Sign In"

#### Navigasi Dashboard

**Panel Utama:**

-   **Statistik Cards**: Menampilkan ringkasan data penting
-   **Chart Analytics**: Grafik aktivitas dan performa
-   **Recent Activities**: Aktivitas terbaru dalam sistem
-   **Quick Actions**: Shortcut untuk aksi yang sering dilakukan

**Menu Sidebar:**

-   🏠 **Dashboard**: Halaman utama
-   👥 **Interns**: Manajemen data intern
-   📊 **Reports**: Laporan harian dan analytics
-   🏖️ **Leaves**: Manajemen cuti
-   ⏰ **Overtime**: Manajemen lembur
-   📧 **Letters**: Sistem surat menyurat
-   📦 **Inventory**: Manajemen inventaris
-   ⚙️ **Settings**: Konfigurasi sistem

### Manajemen Intern

#### Menambah Data Intern Baru

**Langkah-langkah:**

1. Klik menu **"Interns"** di sidebar
2. Klik tombol **"New Intern"**
3. Isi form dengan data lengkap:

**Data Personal:**

-   **Nama Lengkap**: Sesuai identitas resmi
-   **Email**: Email aktif yang akan digunakan login
-   **Phone**: Nomor WhatsApp aktif
-   **Address**: Alamat lengkap
-   **Birth Date**: Tanggal lahir
-   **Gender**: Jenis kelamin

**Data Akademik:**

-   **School**: Pilih atau tambah sekolah/universitas
-   **Major**: Jurusan/program studi
-   **Student ID**: NIM/NIS
-   **Semester**: Semester saat ini

**Data Magang:**

-   **Division**: Divisi penempatan
-   **Start Date**: Tanggal mulai magang
-   **End Date**: Tanggal selesai magang
-   **Supervisor**: Pembimbing lapangan
-   **Status**: Active/Inactive/Completed

4. Upload **foto profil** (opsional)
5. Klik **"Create"**

### Laporan Harian

#### Cara Membuat Laporan Harian (Untuk Intern)

**Langkah-langkah:**

1. Login ke sistem
2. Klik menu **"Daily Reports"**
3. Klik **"New Report"**
4. Isi form laporan:

**Data Wajib:**

-   **Date**: Tanggal laporan (default hari ini)
-   **Check In**: Waktu masuk
-   **Check Out**: Waktu pulang (bisa diisi nanti)
-   **Activities**: Deskripsi kegiatan yang dilakukan
-   **Division**: Divisi (auto-filled)

**Data Opsional:**

-   **Tasks Completed**: Tugas yang diselesaikan
-   **Challenges**: Kendala yang dihadapi
-   **Notes**: Catatan tambahan
-   **Attachments**: Foto/dokumen pendukung

5. Klik **"Submit Report"**

#### Tips Laporan Harian yang Baik

-   **Spesifik**: Jelaskan aktivitas secara detail
-   **Terukur**: Sertakan target dan pencapaian
-   **Jujur**: Laporkan kendala yang dihadapi
-   **Konsisten**: Buat laporan setiap hari kerja
-   **Foto**: Lampirkan foto kegiatan jika memungkinkan

### Manajemen Cuti

#### Mengajukan Cuti (Untuk Intern/Staff)

**Langkah-langkah:**

1. Klik menu **"Leaves"**
2. Klik **"Request Leave"**
3. Isi form pengajuan:

**Data Cuti:**

-   **Leave Type**:
    -   Annual Leave (Cuti Tahunan)
    -   Sick Leave (Sakit)
    -   Emergency Leave (Darurat)
    -   Personal Leave (Keperluan Pribadi)
-   **Start Date**: Tanggal mulai cuti
-   **End Date**: Tanggal selesai cuti
-   **Total Days**: Otomatis terhitung
-   **Reason**: Alasan cuti (detail)
-   **Emergency Contact**: Kontak darurat
-   **Backup Person**: Pengganti selama cuti

4. Upload **dokumen pendukung** (jika diperlukan)
5. Klik **"Submit Request"**

### Sistem Surat Menyurat

#### Surat Masuk (Incoming Letters)

**Menambah Surat Masuk:**

1. Klik **"Incoming Letters"**
2. Klik **"New Incoming Letter"**
3. Isi data:

    - **Letter Number**: Nomor surat
    - **From**: Pengirim
    - **Subject**: Perihal
    - **Date Received**: Tanggal terima
    - **Classification**: Penting/Biasa/Rahasia
    - **Status**: Pending/Processed/Archived

4. Upload **file surat** (PDF/gambar)
5. Assign ke **PIC** yang menangani
6. Klik **"Save"**

### Manajemen Inventaris

#### Sistem Peminjaman (Loan Items)

**Mengajukan Peminjaman:**

1. Klik **"Loan Items"**
2. Klik **"New Loan Request"**
3. Pilih **item** yang akan dipinjam
4. Set **loan period** (tanggal mulai-selesai)
5. Isi **purpose** peminjaman
6. Submit untuk **approval**

---

## 📧 Email & Queue System

### Queue Configuration

#### Environment Setup

**Development (.env):**

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Email Configuration (Development - Log only)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="admin@tvku.com"
MAIL_FROM_NAME="Sistem All In One TVKU"
```

**Production (.env):**

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Email Configuration (Production - SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="admin@tvku.com"
MAIL_FROM_NAME="Sistem All In One TVKU"
```

### Email Providers Setup

#### Gmail SMTP

**Requirements:**

-   Google Account dengan 2-Factor Authentication
-   App Password (bukan password Gmail biasa)

**Steps:**

1. Login ke Google Account Settings
2. Security → 2-Step Verification → App passwords
3. Generate password untuk "Mail"
4. Use generated password di `.env`

**Configuration:**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=generated-app-password
MAIL_ENCRYPTION=tls
```

#### Outlook/Hotmail SMTP

**Configuration:**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### Running Queue Workers

#### Development Environment

**Single Worker:**

```powershell
# Basic queue worker
php artisan queue:work

# With options
php artisan queue:work --sleep=3 --tries=3 --timeout=60

# Process specific queue
php artisan queue:work --queue=emails
```

#### Production Environment

**Supervisor Configuration:**

```ini
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/systemallinone-tvku/artisan queue:work --queue=high,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/systemallinone-tvku/storage/logs/worker-high.log
stopwaitsecs=3600
```

### Monitoring & Troubleshooting

#### Queue Commands

```powershell
# Monitor queue status
php artisan queue:monitor

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart all workers
php artisan queue:restart
```

---

#### PHP Standards

-   Follow **PSR-12** coding style
-   Use **strict types** declaration
-   Write **docblocks** for all methods
-   Use **type hints** untuk parameters dan returns

---

## ❓ FAQ & Support

### Common Issues

#### Q: Email tidak terkirim?

**A:**

1. Cek queue worker berjalan: `php artisan queue:work`
2. Verifikasi konfigurasi SMTP di `.env`
3. Cek failed jobs: `php artisan queue:failed`
4. Test email setup dengan tinker

#### Q: Queue worker sering berhenti?

**A:**

-   Set memory limit yang cukup
-   Gunakan supervisor untuk auto-restart
-   Monitor logs untuk error
-   Restart worker secara berkala

#### Q: Upload file gagal?

**A:**

-   Cek ukuran file (max 10MB)
-   Pastikan format file didukung
-   Verify storage permissions
-   Clear browser cache

### Troubleshooting Guide

#### Error: "Class not found"

```powershell
composer dump-autoload
php artisan clear-compiled
php artisan config:cache
```

#### Error: Database connection

1. Pastikan database service berjalan
2. Verifikasi kredensial di `.env`
3. Test koneksi: `php artisan tinker` → `DB::connection()->getPdo()`

#### Error: Permission denied

```powershell
# Windows: Run PowerShell as Administrator
# Linux/macOS:
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Contact Support

#### Documentation

-   [Laravel Documentation](https://laravel.com/docs)
-   [Filament Documentation](https://filamentphp.com/docs)

#### Support Channels

-   **Email**: support@tvku.com
-   **GitHub Issues**: [Create Issue](https://github.com/AdityaAWP/systemallinone-tvku/issues)
-   **Phone**: +62-xxx-xxxx-xxxx

### Performance Monitoring

```powershell
# Monitor queue jobs
php artisan queue:monitor

# View logs real-time
php artisan pail

# Clear all caches
php artisan optimize:clear
```

---

## 🎯 User Roles

### Super Admin

-   Full system access
-   User management
-   System configuration
-   Backup & maintenance

### Admin HR

-   Employee & intern management
-   Leave & overtime approval
-   Report generation
-   Document management

### Supervisor/Manager

-   Team management
-   Report approval
-   Division analytics
-   Task assignment

### Intern/Staff

-   Daily report submission
-   Leave & overtime request
-   Personal data view
-   Document access

---

## 🔄 Development Workflow

### Development Mode

```bash
# Run all development services
composer run dev
```

Ini akan menjalankan:

-   Laravel development server (port 8000)
-   Queue worker untuk background jobs
-   Log monitoring dengan Pail
-   Vite development server untuk hot reloading

### Production Deployment

```bash
# Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
npm run build
```

---

## 🔐 Security Features

-   **CSRF Protection**: Laravel built-in CSRF protection
-   **SQL Injection**: Eloquent ORM dengan parameterized queries
-   **XSS Protection**: Blade templating dengan auto-escaping
-   **Role-based Access**: Spatie Permission dengan granular control
-   **File Upload**: Validation dan sanitization untuk uploads
-   **Session Security**: Secure session management
-   **Backup Encryption**: Encrypted backup files

---

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

### Development Guidelines

-   Follow **PSR-12** coding standards
-   Write **tests** untuk new features
-   Update **documentation** untuk changes
-   Use **conventional commits** untuk messages

---

## 📝 Changelog

### Version 1.0.0 (Latest)

-   ✅ Initial release
-   ✅ Complete HR management system
-   ✅ Filament 3.x admin panel
-   ✅ Multi-role user system
-   ✅ Export & reporting system
-   ✅ Mobile responsive interface
-   ✅ Email & queue system
-   ✅ Comprehensive documentation

### Roadmap

-   🔄 **v1.1**: Mobile app integration
-   🔄 **v1.2**: Advanced analytics & BI
-   🔄 **v1.3**: API untuk third-party integration
-   🔄 **v1.4**: Multi-tenant support

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 🙏 Acknowledgments

-   [Laravel Framework](https://laravel.com/)
-   [Filament Admin Panel](https://filamentphp.com/)
-   [Spatie Packages](https://spatie.be/)
-   [Tailwind CSS](https://tailwindcss.com/)

---

<div align="center">

**[⬆ Back to Top](#sistem-all-in-one-tvku)**

Made with by TVKU Development Team

**Last Updated**: August 2025 | **Version**: 1.0.0

</div>
