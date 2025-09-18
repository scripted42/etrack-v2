# 🚨 ATTENDANCE ERROR 400 - SOLUSI LENGKAP

## ❌ **Error: "Gagal melakukan absensi: Request failed with status code 400"**

### 🔍 **Penyebab Utama Error 400:**

#### **1. QR Code Expired (Paling Umum)**
```
❌ QR code hanya berlaku 10 detik
❌ User terlalu lama sebelum submit
❌ QR code sudah digunakan
```

#### **2. GPS Location Invalid**
```
❌ User tidak berada di area sekolah (radius 500m)
❌ GPS tidak akurat
❌ Koordinat GPS salah
```

#### **3. Authentication Issue**
```
❌ Token expired
❌ Token tidak valid
❌ Session timeout
```

### ✅ **SOLUSI STEP-BY-STEP:**

#### **STEP 1: Cek QR Code**
```javascript
// Pastikan QR code masih valid
console.log('QR Code:', scannedQRCode.value)
console.log('QR Code Length:', scannedQRCode.value?.length)

// Jika QR code null atau kosong, generate ulang
if (!scannedQRCode.value) {
  await startQRScanner() // Generate QR code baru
}
```

#### **STEP 2: Cek GPS Location**
```javascript
// Pastikan GPS location valid
console.log('GPS Location:', gpsLocation.value)
console.log('GPS Lat:', gpsLocation.value?.lat)
console.log('GPS Lng:', gpsLocation.value?.lng)

// Jika GPS tidak valid, request ulang
if (!gpsLocation.value || !gpsLocation.value.lat || !gpsLocation.value.lng) {
  await requestGPSPermission() // Request GPS ulang
}
```

#### **STEP 3: Cek Selfie Photo**
```javascript
// Pastikan selfie photo valid
console.log('Selfie Photo Length:', capturedPhoto.value?.length)

// Jika selfie tidak ada, ambil ulang
if (!capturedPhoto.value) {
  // User harus ambil foto selfie ulang
}
```

### 🎯 **CARA MENGATASI ERROR 400:**

#### **Solusi 1: QR Code Expired**
1. **Generate QR code baru** dari monitor lobby
2. **Scan QR code segera** setelah generate
3. **Submit attendance segera** setelah scan (dalam 10 detik)

#### **Solusi 2: GPS Location Invalid**
1. **Pastikan berada di area sekolah** (radius 500m)
2. **Aktifkan GPS dengan akurasi tinggi**
3. **Tunggu GPS lock** sebelum absensi
4. **Cek koordinat GPS** di console browser

#### **Solusi 3: Authentication Issue**
1. **Login ulang** ke sistem
2. **Refresh halaman** browser
3. **Cek token** di console browser
4. **Pastikan koneksi internet** stabil

### 🔧 **DEBUGGING STEPS:**

#### **Step 1: Cek Console Browser**
```javascript
// Buka Developer Tools (F12)
// Cek Console untuk error detail
console.log('Submitting attendance data:', attendanceData)
console.log('QR Code:', scannedQRCode.value)
console.log('GPS Location:', gpsLocation.value)
console.log('Selfie Photo Length:', capturedPhoto.value?.length)
```

#### **Step 2: Cek Network Tab**
```
1. Buka Developer Tools (F12)
2. Pilih tab "Network"
3. Coba submit attendance
4. Cek request ke /api/attendance/process
5. Lihat response error detail
```

#### **Step 3: Cek Response Error**
```javascript
// Error response akan menampilkan:
{
  "success": false,
  "message": "QR code tidak valid atau sudah expired"
}
// atau
{
  "success": false,
  "message": "Lokasi tidak valid. Pastikan Anda berada di area sekolah."
}
```

### 🚨 **EMERGENCY FIX:**

Jika error 400 masih terjadi:

#### **1. Refresh Halaman**
```
- Tekan F5 atau Ctrl+R
- Login ulang ke sistem
- Coba absensi lagi
```

#### **2. Generate QR Code Baru**
```
- Buka halaman QR code generator
- Generate QR code baru
- Scan QR code segera
- Submit attendance segera
```

#### **3. Cek GPS Location**
```
- Pastikan GPS aktif
- Tunggu GPS lock
- Pastikan berada di area sekolah
- Cek koordinat di console
```

#### **4. Ambil Selfie Ulang**
```
- Buka kamera
- Ambil foto selfie baru
- Pastikan foto jelas
- Submit attendance
```

### 📞 **SUPPORT:**

Jika masalah masih berlanjut:

1. **Cek console browser** untuk error detail
2. **Screenshot error message** yang muncul
3. **Cek koneksi internet** stabil
4. **Hubungi admin sistem** dengan detail error

### 🎯 **BEST PRACTICES:**

#### **1. Timing:**
- Generate QR code → Scan segera → Submit segera
- Jangan delay lebih dari 10 detik
- Pastikan semua data siap sebelum submit

#### **2. Location:**
- Pastikan berada di area sekolah
- Tunggu GPS lock dengan akurasi tinggi
- Jangan pindah lokasi saat absensi

#### **3. Photo:**
- Ambil foto selfie dengan pencahayaan baik
- Pastikan wajah terlihat jelas
- Jangan gunakan foto lama

---
**Last Updated:** 2025-09-18  
**Version:** 1.0  
**Status:** ✅ Ready for Production


