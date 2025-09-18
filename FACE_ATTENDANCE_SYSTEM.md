# Sistem Absensi Face Recognition

## 🎯 Overview
Sistem absensi berbasis pengenalan wajah yang simple, minimalis, dan mudah digunakan untuk pegawai SMPN 14 Surabaya.

## ✨ Fitur Utama

### 1. **Absensi Wajah (FaceAttendance.vue)**
- **One-Click Attendance**: Satu tombol untuk mulai absensi
- **Fullscreen Camera**: Layar penuh untuk deteksi optimal
- **Real-time Detection**: Deteksi wajah secara real-time
- **Instant Feedback**: Notifikasi langsung berhasil/gagal
- **Auto Timeout**: Otomatis berhenti setelah 30 detik
- **Settings**: Pengaturan kamera dan sensitifitas

### 2. **Registrasi Wajah (FaceRegistration.vue)**
- **Employee Management**: Daftar pegawai dengan status registrasi wajah
- **Camera Registration**: Registrasi wajah pegawai dengan kamera
- **Photo Capture**: Ambil foto wajah untuk database
- **Status Tracking**: Melacak pegawai yang sudah/belum terdaftar

### 3. **Riwayat Absensi (FaceAttendanceHistory.vue)**
- **Attendance History**: Riwayat lengkap absensi wajah
- **Statistics Dashboard**: Statistik absensi dengan grafik
- **Filter & Search**: Filter berdasarkan tanggal, pegawai, tipe absensi
- **Photo Viewer**: Lihat foto absensi
- **Export Data**: Export data absensi

## 🏗️ Arsitektur Sistem

### Frontend (Vue.js + Vuetify)
```
📁 etrack-frontend/src/views/
├── FaceAttendance.vue          # Halaman absensi utama
├── FaceRegistration.vue         # Registrasi wajah pegawai
└── FaceAttendanceHistory.vue   # Riwayat absensi
```

### Backend (Laravel)
```
📁 etrack-backend/
├── app/Http/Controllers/Api/
│   └── FaceAttendanceController.php    # API Controller
├── app/Models/
│   ├── FaceAttendance.php              # Model absensi wajah
│   └── Employee.php                    # Model pegawai (updated)
└── database/migrations/
    ├── create_face_attendances_table.php
    └── add_face_recognition_columns_to_employees_table.php
```

## 🗄️ Database Schema

### Tabel `face_attendances`
```sql
- id (bigint, primary key)
- employee_id (foreign key to employees)
- attendance_date (date)
- attendance_time (time)
- attendance_type (enum: early, on_time, late, overtime)
- photo_path (string, nullable)
- confidence_score (decimal 3,2)
- location (string, nullable)
- ip_address (string, nullable)
- user_agent (text, nullable)
- status (enum: present, absent, late)
- created_at, updated_at
```

### Tabel `employees` (Updated)
```sql
+ face_photo_path (string, nullable)
+ face_descriptor (text, nullable)
+ face_registered_at (timestamp, nullable)
```

## 🚀 API Endpoints

### Face Recognition Attendance
```http
POST /api/attendance/face-recognition
GET  /api/attendance/face-recognition/history
POST /api/attendance/face-recognition/register-face
GET  /api/attendance/face-recognition/statistics
```

### Request/Response Examples

#### Process Face Attendance
```json
POST /api/attendance/face-recognition
{
  "employee_id": 1,
  "timestamp": "2024-09-18T08:30:00Z",
  "photo": "data:image/jpeg;base64,/9j/4AAQ...",
  "confidence": 0.95,
  "location": "Sekolah"
}

Response:
{
  "success": true,
  "message": "Absensi berhasil dicatat",
  "data": {
    "attendance": {...},
    "employee": {...},
    "attendance_time": "08:30:00",
    "attendance_type": "on_time"
  }
}
```

#### Register Face
```json
POST /api/attendance/face-recognition/register-face
{
  "employee_id": 1,
  "face_photo": "data:image/jpeg;base64,/9j/4AAQ...",
  "face_descriptor": "face_encoding_data"
}

Response:
{
  "success": true,
  "message": "Wajah berhasil didaftarkan",
  "data": {
    "employee": {...},
    "face_photo_path": "face_photos/face_1_1234567890.jpg"
  }
}
```

## 🎨 UI/UX Design Principles

### 1. **Minimalis & Simple**
- Fullscreen camera view
- Single action button
- Clear status indicators
- Minimal text, maximum visual feedback

### 2. **User-Friendly**
- One-click attendance
- Auto-detection without manual triggers
- Instant feedback with visual/audio cues
- Clear error messages

### 3. **Mobile-First**
- Responsive design
- Touch-friendly buttons
- Optimized for mobile cameras
- Fast loading and smooth interactions

## 🔧 Setup & Installation

### 1. **Backend Setup**
```bash
cd etrack-backend
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. **Frontend Setup**
```bash
cd etrack-frontend
npm install
npm run dev -- --host
```

### 3. **Storage Setup**
```bash
# Create storage directories
mkdir -p storage/app/public/attendance_photos
mkdir -p storage/app/public/face_photos
php artisan storage:link
```

## 📱 Usage Flow

### 1. **Registrasi Wajah Pegawai**
1. Admin masuk ke "Registrasi Wajah"
2. Pilih pegawai yang belum terdaftar
3. Klik "Daftarkan" → Kamera terbuka
4. Posisikan wajah di tengah frame
5. Klik "Ambil Foto" → Wajah tersimpan

### 2. **Absensi Harian**
1. Pegawai masuk ke "Absensi Wajah"
2. Klik "Mulai Absensi" → Kamera aktif
3. Sistem otomatis deteksi wajah
4. Notifikasi "Absensi Berhasil!" → Selesai

### 3. **Monitoring & Laporan**
1. Admin masuk ke "Riwayat Absensi Wajah"
2. Lihat statistik dan data absensi
3. Filter berdasarkan tanggal/pegawai
4. Export data jika diperlukan

## 🛡️ Security Features

### 1. **Data Protection**
- Foto absensi disimpan terenkripsi
- IP address dan user agent tracking
- Audit log untuk semua aktivitas

### 2. **Access Control**
- Authentication required
- Role-based permissions
- Rate limiting pada API

### 3. **Privacy**
- Foto hanya untuk keperluan absensi
- Data tidak dibagikan ke pihak ketiga
- Compliance dengan regulasi data

## 📊 Monitoring & Analytics

### 1. **Real-time Statistics**
- Total absensi hari ini
- Jumlah pegawai hadir/terlambat
- Rata-rata akurasi deteksi
- Grafik absensi per hari

### 2. **Employee Tracking**
- Status registrasi wajah
- Riwayat absensi individual
- Analisis pola kehadiran
- Alert untuk pegawai bermasalah

## 🔮 Future Enhancements

### 1. **Advanced Features**
- Face recognition dengan AI/ML
- Multi-face detection
- Emotion recognition
- Attendance analytics dashboard

### 2. **Integration**
- Integration dengan sistem payroll
- SMS/Email notifications
- Mobile app development
- Biometric device integration

### 3. **Performance**
- Offline mode support
- Caching optimization
- Real-time sync
- Progressive Web App (PWA)

## 🐛 Troubleshooting

### Common Issues

#### 1. **Camera Not Working**
- Check browser permissions
- Ensure HTTPS connection
- Try different browser
- Check camera hardware

#### 2. **Face Detection Failed**
- Improve lighting conditions
- Clean camera lens
- Adjust confidence threshold
- Re-register face data

#### 3. **API Errors**
- Check backend server status
- Verify authentication token
- Check network connectivity
- Review server logs

## 📞 Support

Untuk bantuan teknis atau pertanyaan:
- Email: support@smpn14sby.sch.id
- Documentation: [Link to docs]
- Issue Tracker: [GitHub Issues]

---

**Sistem Absensi Face Recognition v1.0**  
*SMP Negeri 14 Surabaya*  
*Dibuat dengan ❤️ untuk kemudahan absensi pegawai*
