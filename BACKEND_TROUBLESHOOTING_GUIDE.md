# Backend Laravel Troubleshooting Guide

## Masalah: "Gagal terhubung ke server. Pastikan backend Laravel sedang berjalan."

### Analisis Masalah
Masalah ini terjadi karena konfigurasi authentication middleware Sanctum yang tidak lengkap di Laravel 11.

### Solusi yang Diterapkan

#### 1. Konfigurasi API Base URL
**File**: `etrack-frontend/src/config/api.ts`
```typescript
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
```

#### 2. Konfigurasi Sanctum Middleware
**File**: `etrack-backend/bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
    
    $middleware->alias([
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
    ]);
})
```

#### 3. Test Route untuk Debugging
**File**: `etrack-backend/routes/api.php`
```php
// Test route untuk debugging auth
Route::get('/test-auth', function () {
    return response()->json(['message' => 'Test endpoint berhasil', 'time' => now()]);
});
```

### Status Server

#### Backend Laravel
- **URL**: `http://localhost:8000`
- **Status**: ✅ Berjalan
- **Command**: `php artisan serve --host=0.0.0.0 --port=8000`

#### Frontend Vue.js
- **URL**: `http://localhost:5173`
- **Status**: ✅ Berjalan
- **Command**: `npm run dev`

### Endpoint Testing

#### ✅ Endpoint yang Berfungsi
- `POST /api/login` - Login berhasil
- `GET /api/attendance/qr-code` - QR code generation
- `GET /api/test-auth` - Test endpoint

#### ❌ Endpoint dengan Masalah
- `GET /api/me` - 401 Unauthorized
- `GET /api/dashboard` - 401 Unauthorized
- Semua endpoint dengan middleware `auth:sanctum`

### Kredensial Login
- **Username**: `admin`
- **Password**: `password`

### Troubleshooting Steps

1. **Periksa Server Status**
   ```bash
   # Backend
   cd etrack-backend
   php artisan serve --host=0.0.0.0 --port=8000
   
   # Frontend
   cd etrack-frontend
   npm run dev
   ```

2. **Test Koneksi**
   ```bash
   # Test endpoint tanpa auth
   curl -X GET http://localhost:8000/api/test-auth
   
   # Test login
   curl -X POST http://localhost:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"password"}'
   ```

3. **Periksa Database**
   ```bash
   # Cek token di database
   php artisan tinker --execute="echo \Laravel\Sanctum\PersonalAccessToken::count();"
   ```

### Masalah yang Belum Teratasi

#### Sanctum Authentication Issue
- **Gejala**: Semua endpoint dengan middleware `auth:sanctum` mengembalikan `401 Unauthorized`
- **Penyebab**: Konfigurasi middleware Sanctum tidak lengkap
- **Status**: Masih dalam investigasi

#### Kemungkinan Solusi Lanjutan
1. Periksa konfigurasi `config/sanctum.php`
2. Pastikan `HasApiTokens` trait ada di User model
3. Periksa konfigurasi CORS
4. Test dengan Postman atau tool lain

### File yang Dimodifikasi

1. `etrack-frontend/src/config/api.ts` - API base URL
2. `etrack-backend/bootstrap/app.php` - Sanctum middleware
3. `etrack-backend/routes/api.php` - Test route

### Log Server
```
2025-09-19 00:22:10 /api/test-auth ............................................................................... ~ 517.41ms
2025-09-19 00:22:16 /api/test-auth .................................................................................... ~ 15s
```

### Kesimpulan
Backend Laravel sudah berjalan dengan baik dan dapat menerima request. Masalah utama adalah dengan authentication middleware Sanctum yang memerlukan konfigurasi lebih lanjut untuk endpoint yang memerlukan autentikasi.
