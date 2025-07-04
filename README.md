# SISTEM ALL IN ONE TVKU
## Comprehensive Human Resource & Document Management System

<p align="center">
  <img src="https://via.placeholder.com/400x100/1e40af/ffffff?text=TVKU+SYSTEM" alt="TVKU System Logo" width="400">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-red?style=flat-square&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/Filament-3.x-yellow?style=flat-square&logo=laravel" alt="Filament Version">
  <img src="https://img.shields.io/badge/PHP-8.1+-blue?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/MySQL-8.0+-orange?style=flat-square&logo=mysql" alt="MySQL Version">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 📋 **DESKRIPSI PROYEK**

**Sistem All in One TVKU** adalah aplikasi manajemen terintegrasi berbasis web yang dirancang khusus untuk mengoptimalkan proses bisnis internal PT. TVKU. Sistem ini menggabungkan **manajemen sumber daya manusia**, **document management**, dan **workflow automation** dalam satu platform terpadu.

### **🎯 Tujuan Utama:**
- Digitalisasi proses pengajuan cuti karyawan
- Centralized document management system
- Otomatisasi workflow approval multi-level
- Real-time monitoring dan reporting
- Compliance dengan regulasi ketenagakerjaan

---

## 🚀 **FITUR UTAMA**

### **1. 🏖️ Sistem Manajemen Cuti**
- ✅ **Multi-type Leave Management** (Casual, Medical, Maternity, Other)
- ✅ **Automated Workflow Approval** (Employee → Manager → HRD)
- ✅ **Email Notifications** dengan approval links
- ✅ **Leave Quota Tracking** per karyawan
- ✅ **Working Days Calculator** (exclude weekends & holidays)
- ✅ **Export Reports** (Excel/PDF)
- ✅ **Real-time Dashboard Widgets**

### **2. 📄 Sistem Manajemen Surat-Surat**

#### **A. Surat Masuk (Incoming Letters)**
- ✅ **Auto-generate Agenda Number** (I-, U-, KP-)
- ✅ **File Attachments** (PDF, images)
- ✅ **Kategorisasi** (Internal, Umum, Kunjungan/Prakerin)
- ✅ **Rich Text Editor** untuk isi surat
- ✅ **Advanced Filtering** & search

#### **B. Surat Keluar (Outgoing Letters)**
- ✅ **Auto-generate Nomor Surat** (I-, U-)
- ✅ **Template Management**
- ✅ **File Attachments**
- ✅ **Tracking Status** pengiriman

#### **C. Surat Perintah Penugasan (Assignment Letters)**
- ✅ **Multi-level Approval** (Staff → Manager → Direktur)
- ✅ **Priority Management** (Normal, Penting, Sangat Penting)
- ✅ **Financial Tracking** (Budget & Marketing Expense)
- ✅ **PDF Generation** dengan template custom
- ✅ **Status Monitoring** real-time

### **3. 📊 Dashboard & Analytics**
- ✅ **Statistical Widgets** untuk monitoring
- ✅ **Real-time Notifications**
- ✅ **Export & Reporting System**
- ✅ **User Role Management**

---

## 🛠️ **TEKNOLOGI & FRAMEWORK**

### **Backend Stack:**
```
Laravel Framework 10.x      - Core PHP Framework
Filament v3                - Admin Panel Framework
Eloquent ORM               - Database Object-Relational Mapping
Laravel Mail               - Email System
Laravel Notifications     - Real-time Notifications
Laravel Storage            - File Management System
Laravel Excel              - Export/Import Functionality
DomPDF/TCPDF              - PDF Generation
```

### **Frontend Stack:**
```
Filament Blade Components  - UI Framework
Tailwind CSS 3.x          - Utility-first CSS Framework
Alpine.js                 - Reactive JavaScript Framework
Laravel Vite              - Asset Bundling
PostCSS                   - CSS Processing
```

### **Database & Storage:**
```
MySQL 8.0+                - Primary Database
Laravel Migrations        - Database Schema Management
Laravel Seeders           - Test Data Generation
Public/Private Storage    - File Storage System
```

---

## 📦 **INSTALASI & SETUP**

### **Persyaratan Sistem:**
- PHP >= 8.1
- Composer
- Node.js & NPM
- MySQL 8.0+
- Web Server (Apache/Nginx)

### **Langkah Instalasi:**

1. **Clone Repository:**
   ```bash
   git clone https://github.com/your-repo/systemallinone-tvku.git
   cd systemallinone-tvku
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tvku_system
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Database Migration & Seeding:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Storage Link:**
   ```bash
   php artisan storage:link
   ```

7. **Asset Compilation:**
   ```bash
   npm run build
   ```

8. **Start Development Server:**
   ```bash
   php artisan serve
   ```

---

## 📂 **STRUKTUR PROYEK**

```
systemallinone-tvku/
├── app/
│   ├── Filament/
│   │   ├── Resources/         # Filament CRUD Resources
│   │   ├── Widgets/          # Dashboard Widgets
│   │   └── Exports/          # Export Functionality
│   ├── Models/               # Eloquent Models
│   ├── Notifications/        # Email Notifications
│   ├── Http/Controllers/     # HTTP Controllers
│   └── Exports/             # Excel Export Classes
├── database/
│   ├── migrations/          # Database Migrations
│   └── seeders/            # Database Seeders
├── resources/
│   ├── views/              # Blade Templates
│   └── css/               # Stylesheets
├── storage/
│   └── app/public/        # File Storage
└── public/                # Public Assets
```

---

## 👥 **USER ROLES & PERMISSIONS**

### **1. Employee (Karyawan)**
- Submit leave requests
- View own leave history
- Access document templates

### **2. Manager**
- Approve/reject leave requests (first level)
- Manage team assignments
- Access team reports

### **3. HRD (Human Resource)**
- Final leave approval
- Manage leave quotas
- Generate HR reports
- Employee management

### **4. Admin**
- System configuration
- User management
- Full system access

### **5. Direktur**
- Final assignment approval
- Executive dashboard
- Strategic reports

---

## 🔧 **KONFIGURASI SISTEM**

### **Email Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tvku.com
MAIL_FROM_NAME="TVKU System"
```

### **File Storage Configuration:**
```env
FILESYSTEM_DISK=local
# For production use: s3, cloudinary, etc.
```

### **Queue Configuration (Optional):**
```env
QUEUE_CONNECTION=database
# For production use: redis, sqs, etc.
```

---

## 📊 **DATABASE SCHEMA**

### **Key Tables:**
- `users` - User accounts & profiles
- `leaves` - Leave requests & approvals
- `leave_quotas` - Annual leave quotas
- `incoming_letters` - Surat masuk
- `outgoing_letters` - Surat keluar
- `assignments` - Surat perintah penugasan
- `notifications` - System notifications

---

## 🔒 **KEAMANAN**

### **Implementasi Security:**
- ✅ **CSRF Protection** pada semua forms
- ✅ **XSS Protection** dengan Blade escaping
- ✅ **SQL Injection Prevention** via Eloquent ORM
- ✅ **File Upload Validation** & type restrictions
- ✅ **Role-based Access Control** (RBAC)
- ✅ **Email Token Verification** untuk approval
- ✅ **Session Management** yang aman

---

## 📈 **PERFORMANCE & OPTIMIZATION**

### **Optimisasi yang Diterapkan:**
- Database indexing untuk query performance
- Eager loading untuk mencegah N+1 queries
- File caching untuk static assets
- Lazy loading untuk large datasets
- Optimized image handling

---

## 🧪 **TESTING**

### **Test Coverage:**
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### **Test Categories:**
- Unit Tests untuk business logic
- Feature Tests untuk user workflows
- Browser Tests untuk UI interactions

---

## 📚 **DOKUMENTASI**

### **API Documentation:**
- Endpoint documentation available at `/docs/api`
- Postman collection included in `/docs/postman/`

### **User Manual:**
- Administrator Guide: `/docs/admin-guide.pdf`
- User Manual: `/docs/user-manual.pdf`

---

## 🚀 **DEPLOYMENT**

### **Production Deployment:**
1. Set `APP_ENV=production` in `.env`
2. Optimize configuration: `php artisan config:cache`
3. Optimize routes: `php artisan route:cache`
4. Optimize views: `php artisan view:cache`
5. Set proper file permissions
6. Configure web server (Apache/Nginx)
7. Set up SSL certificate
8. Configure backup system

---

## 🤝 **KONTRIBUSI**

### **Development Guidelines:**
- Follow PSR-12 coding standards
- Write comprehensive tests
- Document new features
- Use conventional commit messages

### **Contribution Process:**
1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Submit pull request

---

## 📞 **SUPPORT & CONTACT**

### **Technical Support:**
- **Developer:** [Your Name]
- **Email:** developer@tvku.com
- **Project Repository:** [GitHub Link]

### **Business Contact:**
- **PT. TVKU**
- **Address:** [Company Address]
- **Phone:** [Phone Number]
- **Email:** info@tvku.com

---

## 📄 **LICENSE**

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

---

## 🙏 **ACKNOWLEDGMENTS**

Terima kasih kepada:
- **Laravel Community** untuk framework yang powerful
- **Filament Team** untuk admin panel framework
- **PT. TVKU Management** untuk dukungan pengembangan
- **Development Team** yang telah berkontribusi

---

## 📊 **PROJECT STATISTICS**

- **Total Lines of Code:** ~15,000+ lines
- **Models:** 10+ Eloquent models
- **Resources:** 8+ Filament resources
- **Migrations:** 20+ database migrations
- **Tests:** 50+ automated tests
- **Features:** 3 major modules

---

**Last Updated:** January 2025  
**Version:** 1.0.0  
**Maintained by:** TVKU Development Team
