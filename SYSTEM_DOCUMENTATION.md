# E-Track v2 System Documentation

## Overview
E-Track v2 adalah sistem manajemen sekolah yang dibangun dengan Laravel 11 (Backend) dan Vue.js 3 (Frontend).

## Architecture

### Backend (Laravel 11)
- **Framework**: Laravel 11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Port**: 8000
- **URL**: `http://localhost:8000`

### Frontend (Vue.js 3)
- **Framework**: Vue.js 3 + Vite
- **UI Library**: Vuetify
- **State Management**: Pinia
- **Port**: 5173
- **URL**: `http://localhost:5173`

## Features Implemented

### 1. Authentication System
- **Login/Logout**: Basic authentication
- **MFA (Multi-Factor Authentication)**: Email OTP (hidden from UI)
- **Password Policy**: Strong password requirements
- **User Management**: Admin, Teacher, Student roles

### 2. Face Recognition Attendance
- **Face Registration**: Register employee faces
- **Face Attendance**: Process attendance using face recognition
- **Attendance History**: View attendance records
- **Statistics**: Attendance analytics

### 3. Data Management
- **Student Management**: CRUD operations for students
- **Employee Management**: CRUD operations for employees
- **User Management**: User account management
- **Role Management**: Permission-based access control

### 4. Dashboard & Analytics
- **Dashboard**: Overview statistics
- **Charts**: Data visualization
- **Reports**: Various reporting features
- **Audit Trail**: Activity logging

## Database Schema

### Core Tables
- `users` - User accounts
- `roles` - User roles
- `students` - Student data
- `employees` - Employee data
- `face_attendances` - Face recognition attendance
- `mfa_otps` - MFA OTP codes
- `personal_access_tokens` - Sanctum tokens

### Key Relationships
- Users → Roles (Many-to-One)
- Employees → Face Attendances (One-to-Many)
- Users → MFA OTPs (One-to-Many)

## API Endpoints

### Public Endpoints
- `POST /api/login` - User login
- `GET /api/attendance/qr-code` - QR code generation
- `GET /api/test-auth` - Test endpoint

### Protected Endpoints (Requires Authentication)
- `GET /api/me` - Get current user
- `POST /api/logout` - User logout
- `GET /api/dashboard` - Dashboard data
- `GET /api/students` - Student management
- `GET /api/employees` - Employee management

### Face Recognition Endpoints
- `POST /api/attendance/face-recognition` - Process face attendance
- `GET /api/attendance/face-recognition/history` - Attendance history
- `POST /api/attendance/face-recognition/register-face` - Register face
- `GET /api/attendance/face-recognition/statistics` - Statistics

## Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+

### Backend Setup
```bash
cd etrack-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

### Frontend Setup
```bash
cd etrack-frontend
npm install
npm run dev
```

## Configuration

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

### Sanctum Configuration
```php
// bootstrap/app.php
$middleware->api(prepend: [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);
```

## Security Features

### Authentication
- Laravel Sanctum for API authentication
- Token-based authentication
- Password hashing with bcrypt
- Rate limiting on API endpoints

### Authorization
- Role-based access control
- Permission-based middleware
- Route protection
- CSRF protection

### Data Protection
- Input validation
- SQL injection prevention
- XSS protection
- Secure file uploads

## Troubleshooting

### Common Issues

#### 1. "Gagal terhubung ke server"
- **Cause**: Backend not running or wrong API URL
- **Solution**: Check server status and API configuration

#### 2. "401 Unauthorized"
- **Cause**: Sanctum authentication issue
- **Solution**: Check middleware configuration

#### 3. CORS Issues
- **Cause**: Cross-origin requests blocked
- **Solution**: Configure CORS in Laravel

### Debug Commands
```bash
# Check server status
curl -X GET http://localhost:8000/api/test-auth

# Test login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Check database
php artisan tinker --execute="echo \Laravel\Sanctum\PersonalAccessToken::count();"
```

## Development Guidelines

### Code Structure
- **Controllers**: API logic
- **Models**: Database relationships
- **Services**: Business logic
- **Middleware**: Request processing
- **Routes**: API endpoints

### Best Practices
- Use Eloquent relationships
- Implement proper validation
- Add error handling
- Write clean, readable code
- Follow Laravel conventions

## Deployment

### Production Setup
1. Configure environment variables
2. Run migrations
3. Set up web server (Apache/Nginx)
4. Configure SSL certificates
5. Set up monitoring

### Performance Optimization
- Enable caching
- Optimize database queries
- Use CDN for static assets
- Implement rate limiting

## Support & Maintenance

### Log Files
- Laravel logs: `storage/logs/laravel.log`
- Server logs: Check web server configuration
- Database logs: MySQL error logs

### Monitoring
- Check server status regularly
- Monitor database performance
- Review error logs
- Update dependencies

## Version History

### v2.0.0 (Current)
- Laravel 11 upgrade
- Vue.js 3 implementation
- Face recognition attendance
- MFA implementation
- Enhanced security

### Future Roadmap
- Mobile app development
- Advanced analytics
- Integration with external systems
- Performance optimizations

## Contact & Support
- **Developer**: AI Assistant
- **Project**: E-Track v2
- **Repository**: GitHub
- **Documentation**: This file
