# E-Track v2 - School Management System

## 🎯 Overview
E-Track v2 adalah sistem manajemen sekolah modern yang dibangun dengan teknologi terbaru untuk mengelola data siswa, pegawai, dan sistem absensi dengan pengenalan wajah.

## 🚀 Features

### 🔐 Authentication & Security
- **Multi-Factor Authentication (MFA)** dengan Email OTP
- **Role-based Access Control** (Admin, Teacher, Student)
- **Laravel Sanctum** untuk API authentication
- **Password Policy** dengan persyaratan keamanan tinggi

### 👥 User Management
- **Student Management** - Kelola data siswa lengkap
- **Employee Management** - Kelola data pegawai
- **User Roles** - Sistem peran dan izin
- **Profile Management** - Pengelolaan profil pengguna

### 📊 Face Recognition Attendance
- **Face Registration** - Daftarkan wajah pegawai
- **Face Attendance** - Absensi dengan pengenalan wajah
- **Attendance History** - Riwayat absensi lengkap
- **Statistics** - Analitik dan laporan absensi

### 📈 Dashboard & Analytics
- **Real-time Dashboard** - Statistik real-time
- **Interactive Charts** - Visualisasi data interaktif
- **Reports** - Berbagai laporan sistem
- **Audit Trail** - Log aktivitas pengguna

### 🔧 System Features
- **Data Export** - Ekspor data ke Excel/CSV
- **Backup System** - Sistem backup otomatis
- **Mobile Responsive** - Tampilan mobile-friendly
- **API-First Architecture** - Arsitektur berbasis API

## 🛠 Technology Stack

### Backend
- **Laravel 11** - PHP Framework
- **MySQL 8.0** - Database
- **Laravel Sanctum** - API Authentication
- **Laravel Excel** - Data Export

### Frontend
- **Vue.js 3** - JavaScript Framework
- **Vuetify 3** - Material Design Components
- **Pinia** - State Management
- **Vite** - Build Tool

## 📋 Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Git

## 🚀 Installation

### 1. Clone Repository
```bash
git clone https://github.com/scripted42/etrack-v2.git
cd etrack-v2
```

### 2. Backend Setup
```bash
cd etrack-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Frontend Setup
```bash
cd etrack-frontend
npm install
npm run dev
```

## 🔧 Configuration

### Environment Variables
```env
# Backend (.env)
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=etrack
DB_USERNAME=root
DB_PASSWORD=

# Frontend (.env.local)
VITE_API_URL=http://localhost:8000/api
```

### Default Login Credentials
- **Username**: `admin`
- **Password**: `password`

## 📚 Documentation

### System Documentation
- [System Documentation](SYSTEM_DOCUMENTATION.md) - Dokumentasi lengkap sistem
- [Backend Troubleshooting](BACKEND_TROUBLESHOOTING_GUIDE.md) - Panduan troubleshooting
- [Face Attendance System](FACE_ATTENDANCE_SYSTEM.md) - Dokumentasi sistem absensi wajah

### API Documentation
- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Content-Type**: `application/json`

### Key Endpoints
```
POST /api/login - User login
GET  /api/me - Get current user
GET  /api/dashboard - Dashboard data
POST /api/attendance/face-recognition - Process face attendance
GET  /api/attendance/face-recognition/history - Attendance history
```

## 🐛 Troubleshooting

### Common Issues

#### 1. "Gagal terhubung ke server"
```bash
# Check backend status
curl -X GET http://localhost:8000/api/test-auth

# Restart backend
cd etrack-backend
php artisan serve --host=0.0.0.0 --port=8000
```

#### 2. Authentication Issues
```bash
# Test login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

#### 3. Database Issues
```bash
# Run migrations
php artisan migrate

# Check database connection
php artisan tinker --execute="echo 'DB Connected: ' . (DB::connection()->getPdo() ? 'YES' : 'NO');"
```

## 🧪 Testing

### Manual Testing
```bash
# Test backend
curl -X GET http://localhost:8000/api/test-auth

# Test frontend
# Open http://localhost:5173 in browser
```

### Automated Testing
```bash
# Backend tests
cd etrack-backend
php artisan test

# Frontend tests
cd etrack-frontend
npm run test
```

## 📊 Database Schema

### Core Tables
- `users` - User accounts
- `roles` - User roles
- `students` - Student data
- `employees` - Employee data
- `face_attendances` - Face recognition attendance
- `mfa_otps` - MFA OTP codes
- `personal_access_tokens` - Sanctum tokens

## 🔒 Security Features

- **Laravel Sanctum** untuk API authentication
- **Password Hashing** dengan bcrypt
- **Rate Limiting** pada API endpoints
- **CSRF Protection** untuk web routes
- **Input Validation** untuk semua input
- **SQL Injection Prevention**
- **XSS Protection**

## 🚀 Deployment

### Production Setup
1. Configure environment variables
2. Run migrations: `php artisan migrate`
3. Set up web server (Apache/Nginx)
4. Configure SSL certificates
5. Set up monitoring

### Performance Optimization
- Enable caching: `php artisan config:cache`
- Optimize database queries
- Use CDN for static assets
- Implement rate limiting

## 📈 Roadmap

### v2.1.0 (Planned)
- [ ] Mobile app development
- [ ] Advanced analytics dashboard
- [ ] Integration with external systems
- [ ] Performance optimizations

### v2.2.0 (Future)
- [ ] AI-powered insights
- [ ] Real-time notifications
- [ ] Advanced reporting
- [ ] Multi-language support

## 🤝 Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Documentation**: [System Documentation](SYSTEM_DOCUMENTATION.md)
- **Issues**: [GitHub Issues](https://github.com/scripted42/etrack-v2/issues)
- **Discussions**: [GitHub Discussions](https://github.com/scripted42/etrack-v2/discussions)

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Community
- Vuetify Team
- All contributors

---

**E-Track v2** - Modern School Management System with Face Recognition Attendance