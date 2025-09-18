# Sistem Absensi E-Track v2 - Panduan Lengkap

## 🎯 Overview
Sistem absensi E-Track v2 menggunakan teknologi pengenalan wajah (Face Recognition) untuk proses absensi yang akurat dan efisien.

## 🚀 Fitur Sistem Absensi

### 1. **Face Recognition Attendance**
- **Registrasi Wajah**: Daftarkan wajah pegawai untuk sistem
- **Absensi Wajah**: Proses absensi dengan pengenalan wajah
- **Riwayat Absensi**: Lihat riwayat absensi lengkap
- **Statistik**: Analitik dan laporan absensi

### 2. **Komponen Sistem**

#### Backend (Laravel)
- **Controller**: `FaceAttendanceController.php`
- **Model**: `FaceAttendance.php`, `Employee.php`
- **Database**: `face_attendances`, `employees` tables
- **API Endpoints**: 4 endpoint utama

#### Frontend (Vue.js)
- **FaceAttendance.vue**: Halaman absensi wajah
- **FaceRegistration.vue**: Halaman registrasi wajah
- **FaceAttendanceHistory.vue**: Riwayat absensi

## 📋 API Endpoints

### 1. **Process Face Attendance**
```http
POST /api/attendance/face-recognition
Content-Type: application/json
Authorization: Bearer {token}

{
  "employee_id": 1,
  "photo": "base64_image_data",
  "location": "Sekolah",
  "attendance_type": "on_time"
}
```

### 2. **Get Attendance History**
```http
GET /api/attendance/face-recognition/history
Authorization: Bearer {token}
```

### 3. **Register Face**
```http
POST /api/attendance/face-recognition/register-face
Content-Type: application/json
Authorization: Bearer {token}

{
  "employee_id": 1,
  "photo": "base64_image_data"
}
```

### 4. **Get Statistics**
```http
GET /api/attendance/face-recognition/statistics
Authorization: Bearer {token}
```

## 🗄️ Database Schema

### Table: `face_attendances`
```sql
CREATE TABLE face_attendances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  attendance_time TIME NOT NULL,
  attendance_type ENUM('early', 'on_time', 'late', 'overtime') DEFAULT 'on_time',
  photo_path VARCHAR(255) NULL,
  confidence_score DECIMAL(3,2) DEFAULT 0.95,
  location VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  status ENUM('present', 'absent', 'late') DEFAULT 'present',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  INDEX idx_employee_date (employee_id, attendance_date),
  INDEX idx_date_type (attendance_date, attendance_type)
);
```

### Table: `employees` (Face Recognition Columns)
```sql
ALTER TABLE employees ADD COLUMN face_photo_path VARCHAR(255) NULL;
ALTER TABLE employees ADD COLUMN face_descriptor TEXT NULL;
ALTER TABLE employees ADD COLUMN face_registered_at TIMESTAMP NULL;
```

## 🎨 Frontend Components

### 1. **FaceAttendance.vue**
- **Fitur**: Fullscreen camera untuk absensi
- **Status Bar**: Menampilkan status sistem
- **Action Buttons**: Tombol untuk capture dan proses
- **Result Dialog**: Menampilkan hasil absensi

### 2. **FaceRegistration.vue**
- **Employee List**: Daftar pegawai untuk registrasi
- **Camera Dialog**: Modal untuk capture wajah
- **Registration Process**: Proses registrasi wajah

### 3. **FaceAttendanceHistory.vue**
- **Attendance List**: Daftar riwayat absensi
- **Filter Options**: Filter berdasarkan tanggal, pegawai
- **Export Features**: Ekspor data absensi

## 🔧 Setup dan Konfigurasi

### 1. **Backend Setup**
```bash
# Run migrations
php artisan migrate

# Check face attendance routes
php artisan route:list --name=face-recognition
```

### 2. **Frontend Setup**
```bash
# Install dependencies
npm install

# Start development server
npm run dev
```

### 3. **Database Setup**
```bash
# Create face attendance tables
php artisan migrate --path=database/migrations/2025_09_18_190141_create_face_attendances_table.php
php artisan migrate --path=database/migrations/2025_09_18_190154_add_face_recognition_columns_to_employees_table.php
```

## 🧪 Testing

### 1. **Test API Endpoints**
```bash
# Test face attendance system
curl -X GET http://localhost:8000/api/test-face-attendance

# Test with authentication (requires token)
curl -X GET http://localhost:8000/api/attendance/face-recognition/statistics \
  -H "Authorization: Bearer {token}"
```

### 2. **Test Frontend**
1. Buka `http://localhost:5173`
2. Login dengan kredensial admin
3. Navigasi ke menu "Absensi Wajah"
4. Test fitur registrasi dan absensi

### 3. **Test Camera Access**
```bash
# Test camera access
curl -X GET http://localhost:5173/test-camera.html
```

## 📊 Workflow Sistem

### 1. **Registrasi Wajah**
1. Admin buka menu "Registrasi Wajah"
2. Pilih pegawai dari daftar
3. Klik "Daftarkan Wajah"
4. Capture foto wajah pegawai
5. Sistem menyimpan deskriptor wajah

### 2. **Proses Absensi**
1. Pegawai buka menu "Absensi Wajah"
2. Sistem mengaktifkan kamera
3. Pegawai posisikan wajah di depan kamera
4. Sistem mengenali wajah dan proses absensi
5. Hasil absensi ditampilkan

### 3. **Lihat Riwayat**
1. Buka menu "Riwayat Absensi Wajah"
2. Filter berdasarkan tanggal/pegawai
3. Lihat detail absensi
4. Export data jika diperlukan

## 🔒 Security Features

### 1. **Authentication**
- Semua endpoint memerlukan Bearer token
- Rate limiting pada API endpoints
- CSRF protection

### 2. **Data Protection**
- Foto wajah disimpan dengan aman
- Deskriptor wajah dienkripsi
- Log aktivitas untuk audit

### 3. **Privacy**
- Foto hanya digunakan untuk absensi
- Data tidak dibagikan ke pihak ketiga
- Compliance dengan regulasi privasi

## 🐛 Troubleshooting

### 1. **Camera Issues**
```javascript
// Check camera permissions
navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => console.log('Camera OK'))
  .catch(err => console.error('Camera Error:', err));
```

### 2. **Authentication Issues**
```bash
# Check token validity
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer {token}"
```

### 3. **Database Issues**
```bash
# Check face attendance records
php artisan tinker --execute="echo \App\Models\FaceAttendance::count();"
```

## 📈 Performance Optimization

### 1. **Backend**
- Optimize database queries
- Implement caching
- Use queue for heavy operations

### 2. **Frontend**
- Lazy load components
- Optimize image processing
- Implement offline support

### 3. **Database**
- Add proper indexes
- Regular maintenance
- Monitor query performance

## 🚀 Deployment

### 1. **Production Setup**
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up file storage
php artisan storage:link
```

### 2. **Environment Variables**
```env
# Face recognition settings
FACE_RECOGNITION_ENABLED=true
FACE_RECOGNITION_CONFIDENCE=0.8
FACE_RECOGNITION_MODEL_PATH=/path/to/model

# Storage settings
FILESYSTEM_DISK=public
```

## 📚 Documentation

### Related Files
- `FACE_ATTENDANCE_SYSTEM.md` - Dokumentasi teknis
- `SYSTEM_DOCUMENTATION.md` - Dokumentasi sistem
- `BACKEND_TROUBLESHOOTING_GUIDE.md` - Panduan troubleshooting

### API Documentation
- Base URL: `http://localhost:8000/api`
- Authentication: Bearer Token
- Content-Type: `application/json`

## 🎯 Next Steps

### 1. **Immediate**
- Test semua fitur absensi
- Perbaiki masalah authentication
- Optimize performance

### 2. **Short Term**
- Add face recognition accuracy improvements
- Implement real-time notifications
- Add mobile app support

### 3. **Long Term**
- AI-powered insights
- Advanced analytics
- Integration with external systems

---

**Sistem Absensi E-Track v2** - Face Recognition Attendance System
