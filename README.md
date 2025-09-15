# E-Track - Tracking & Manajemen Data SMPN 14 Surabaya

Sistem manajemen data siswa dan pegawai berbasis web untuk SMP Negeri 14 Surabaya dengan fitur tracking, audit trail, dan role-based access control.

## 🚀 Fitur Utama

- **Dashboard Real-time**: Statistik siswa, pegawai, dan aktivitas sistem
- **Manajemen Siswa**: CRUD data siswa dengan NIS, kelas, dan jurusan
- **Manajemen Pegawai**: CRUD data pegawai dengan NIP dan jabatan
- **Role-Based Access Control (RBAC)**: Admin, Kepala Sekolah, TU, Guru, Siswa
- **Audit Trail**: Log semua aktivitas pengguna sesuai standar ISO 27001
- **Manajemen Dokumen**: Upload dan tracking dokumen penting
- **Responsive Design**: Mobile-friendly interface
- **On-Premise Deployment**: Dapat berjalan di server lokal sekolah

## 🛠️ Tech Stack

### Backend
- **Laravel 11** - PHP Framework
- **MySQL 8** - Database
- **Laravel Sanctum** - API Authentication
- **Spatie Laravel Permission** - RBAC

### Frontend
- **Vue 3** - JavaScript Framework
- **Vuetify 3** - UI Component Library
- **TypeScript** - Type Safety
- **Pinia** - State Management
- **Vue Router** - Routing
- **ApexCharts** - Data Visualization

## 📋 Persyaratan Sistem

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8.0+
- Apache/Nginx

## 🚀 Instalasi & Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd Etrack-v2
```

### 2. Setup Backend (Laravel)

```bash
cd etrack-backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_management_sekolah
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate:fresh --seed

# Start development server
php artisan serve
```

### 3. Setup Frontend (Vue 3)

```bash
cd etrack-frontend

# Install dependencies
npm install

# Create environment file
echo "VITE_API_URL=http://localhost:8000/api" > .env

# Start development server
npm run dev
```

## 🔐 Default Login Credentials

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| Admin | admin | password123 | Administrator sistem |
| Kepala Sekolah | kepsek | password123 | Kepala sekolah |
| TU/Operator | tu | password123 | Tata usaha/operator |

## 📊 Database Schema

### Tabel Utama
- `users` - Data pengguna sistem
- `roles` - Role pengguna (Admin, Kepala Sekolah, dll)
- `permissions` - Permission granular
- `students` - Data siswa
- `employees` - Data pegawai
- `documents` - Dokumen pengguna
- `audit_logs` - Log aktivitas sistem

### Relasi
- User → Role (Many-to-One)
- Role → Permission (Many-to-Many)
- User → Student (One-to-One)
- User → Employee (One-to-One)
- User → Document (One-to-Many)
- User → AuditLog (One-to-Many)

## 🔒 Keamanan & Compliance

### ISO 27001 Compliance
- **A.9 (Access Control)**: RBAC dengan Spatie Laravel Permission
- **A.10 (Cryptography)**: Password hashing dengan bcrypt, SSL/TLS
- **A.12 (Logging)**: Audit trail lengkap dengan Laravel ActivityLog
- **A.17 (Continuity)**: Backup MySQL otomatis
- **A.18 (Compliance)**: Export log untuk audit eksternal

### Fitur Keamanan
- JWT Token Authentication
- Password Policy (min. 8 karakter)
- Auto logout setelah idle
- IP Address tracking
- Audit trail semua aktivitas

## 📱 API Documentation

### Authentication Endpoints
```
POST /api/login - Login user
POST /api/logout - Logout user
GET /api/me - Get current user
POST /api/change-password - Change password
```

### Dashboard Endpoints
```
GET /api/dashboard/statistics - Get dashboard statistics
GET /api/dashboard/chart-data - Get chart data
```

### Student Endpoints
```
GET /api/students - List students
POST /api/students - Create student
GET /api/students/{id} - Get student
PUT /api/students/{id} - Update student
DELETE /api/students/{id} - Delete student
```

### Employee Endpoints
```
GET /api/employees - List employees
POST /api/employees - Create employee
GET /api/employees/{id} - Get employee
PUT /api/employees/{id} - Update employee
DELETE /api/employees/{id} - Delete employee
```

## 🚀 Deployment

### Production Setup

1. **Server Requirements**
   - Ubuntu 20.04+ / CentOS 8+
   - PHP 8.3+ dengan extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
   - MySQL 8.0+
   - Apache/Nginx
   - Node.js 18+

2. **Laravel Deployment**
```bash
# Set production environment
cp .env.production .env

# Install dependencies (no dev)
composer install --optimize-autoloader --no-dev

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

3. **Vue.js Deployment**
```bash
# Build for production
npm run build

# Copy dist files to web server
cp -r dist/* /var/www/html/
```

### Docker Deployment (Optional)

```dockerfile
# Dockerfile for Laravel
FROM php:8.3-fpm
# ... (Laravel setup)

# Dockerfile for Vue.js
FROM node:18-alpine
# ... (Vue.js build)
```

## 📈 Monitoring & Maintenance

### Backup Strategy
```bash
# Daily MySQL backup
mysqldump -u username -p user_management_sekolah > backup_$(date +%Y%m%d).sql

# Automated backup script
0 2 * * * /path/to/backup_script.sh
```

### Log Monitoring
- Laravel logs: `storage/logs/laravel.log`
- Audit logs: Database table `audit_logs`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Email**: info@smpn14sby.sch.id
- **Phone**: (031) 5678901
- **Address**: Jl. Raya Darmo Permai III No. 1, Surabaya

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Community
- Vuetify Team
- SMP Negeri 14 Surabaya

---

**E-Track v1.0.0** - Sistem Manajemen Data Siswa & Pegawai SMPN 14 Surabaya
