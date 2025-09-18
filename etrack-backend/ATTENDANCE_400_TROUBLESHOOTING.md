# 🔧 ATTENDANCE ERROR 400 TROUBLESHOOTING GUIDE

## ❌ **Error 400: "Request failed with status code 400"**

### 🔍 **Penyebab Error 400:**

#### **1. QR Code Tidak Valid atau Expired**
```
Error: "QR code tidak valid atau sudah expired"
```
**Penyebab:**
- QR code sudah expired (hanya berlaku 10 detik)
- QR code tidak ada di database
- QR code sudah digunakan

**Solusi:**
- Generate QR code baru
- Pastikan QR code masih aktif
- Jangan gunakan QR code yang sudah expired

#### **2. GPS Location Tidak Valid**
```
Error: "Lokasi tidak valid. Pastikan Anda berada di area sekolah."
```
**Penyebab:**
- User tidak berada di area sekolah (radius 500m)
- GPS tidak akurat
- Koordinat GPS salah

**Solusi:**
- Pastikan berada di area sekolah
- Aktifkan GPS dengan akurasi tinggi
- Tunggu GPS lock sebelum absensi

#### **3. Data Format Tidak Valid**
```
Error: "Data tidak valid"
```
**Penyebab:**
- GPS location format salah
- Selfie photo tidak valid
- Attendance type tidak sesuai

**Solusi:**
- Pastikan format data sesuai API
- Gunakan foto selfie yang valid
- Pilih attendance type yang benar

### ✅ **Cara Mengatasi Error 400:**

#### **Step 1: Cek QR Code**
```javascript
// Pastikan QR code masih valid
const qrResponse = await api.get('/attendance/qr-code')
if (qrResponse.data.success) {
  const qrToken = qrResponse.data.data.token
  // Gunakan token ini untuk absensi
}
```

#### **Step 2: Cek GPS Location**
```javascript
// Pastikan GPS location valid
if (!gpsLocation.value || !gpsLocation.value.lat || !gpsLocation.value.lng) {
  throw new Error('Lokasi GPS tidak valid. Pastikan GPS sudah diaktifkan.')
}
```

#### **Step 3: Cek Data Format**
```javascript
// Format data yang benar
const attendanceData = {
  qr_code: scannedQRCode.value,
  gps_location: {
    lat: gpsLocation.value.lat,
    lng: gpsLocation.value.lng,
    accuracy: gpsLocation.value.accuracy || 10,
    address: gpsLocation.value.address || 'Lokasi tidak diketahui'
  },
  selfie_photo: capturedPhoto.value,
  attendance_type: 'check_in',
  notes: attendanceNotes.value
}
```

### 🎯 **Best Practices:**

#### **1. QR Code Management:**
- Generate QR code baru setiap kali absensi
- Jangan simpan QR code lama
- Pastikan QR code masih aktif sebelum submit

#### **2. GPS Location:**
- Tunggu GPS lock sebelum absensi
- Pastikan berada di area sekolah
- Gunakan akurasi GPS tinggi

#### **3. Selfie Photo:**
- Pastikan foto selfie jelas
- Gunakan pencahayaan yang baik
- Jangan gunakan foto lama

### 🚨 **Emergency Fix:**

Jika error 400 masih terjadi:

1. **Refresh halaman** dan coba lagi
2. **Generate QR code baru** dari monitor lobby
3. **Pastikan GPS aktif** dan akurat
4. **Ambil foto selfie baru** dengan pencahayaan baik
5. **Submit segera** setelah semua data siap

### 📞 **Support:**

Jika masalah masih berlanjut:
- Cek console browser untuk error detail
- Pastikan koneksi internet stabil
- Hubungi admin sistem

---
**Last Updated:** 2025-09-18
**Version:** 1.0


