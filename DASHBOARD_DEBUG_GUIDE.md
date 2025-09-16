# 🔍 DASHBOARD DEBUG GUIDE

## 🚨 **MASALAH YANG DITEMUKAN:**

Dashboard menampilkan semua data sebagai **"0"** atau **kosong**, padahal:
- ✅ **Backend API** mengembalikan data yang benar
- ✅ **Database** memiliki data real (18 siswa, 14 pegawai)
- ✅ **API Response** menunjukkan data lengkap

## 🔧 **DEBUGGING STEPS:**

### **1. Dashboard Debug Version:**
Saya sudah membuat `DashboardDebug.vue` untuk mengidentifikasi masalah:

**Fitur Debug:**
- ✅ **Authentication Debug** - Cek token dan user
- ✅ **API Test Button** - Test API call manual
- ✅ **Raw Data Display** - Tampilkan response lengkap
- ✅ **Console Logging** - Log detail untuk tracking

### **2. Cara Testing:**

#### **Akses Dashboard Debug:**
- URL: `http://localhost:5173`
- Login: `admin` / `admin123`
- Dashboard akan menampilkan versi debug

#### **Cek Authentication Debug:**
- **Token:** Harus "Present"
- **User:** Harus menampilkan username admin
- **API Base URL:** Harus `http://localhost:8000/api`

#### **Test API Call:**
- Klik tombol **"Test API Call"**
- Cek **API Result** - Harus menampilkan data lengkap
- Cek **API Error** - Harus kosong jika berhasil

#### **Cek Console Browser:**
- Buka F12 → Console tab
- Lihat log messages:
  ```
  === DASHBOARD DEBUG MOUNTED ===
  === API TEST DEBUG ===
  1. Testing API call...
  2. Token: [token value]
  3. User: [user object]
  4. API Base URL: http://localhost:8000/api
  5. API Response: [response object]
  ```

### **3. Expected Results:**

**Jika API berhasil:**
- **Total Siswa:** 18 (bukan 0)
- **Total Pegawai:** 14 (bukan 0)
- **Utilisasi:** 3.6% (bukan 0%)
- **Raw Data:** Menampilkan object lengkap

**Jika API gagal:**
- **API Error:** Menampilkan error message
- **Console:** Menampilkan error details

---

## 🎯 **KEMUNGKINAN PENYEBAB:**

### **1. Authentication Issues:**
- Token tidak valid atau expired
- User tidak ter-authenticate
- API base URL salah

### **2. Service Issues:**
- `fetchDashboardStats()` tidak dipanggil
- Response tidak di-assign ke state
- Error handling yang tidak tepat

### **3. Component Issues:**
- Data tidak di-render dengan benar
- Template binding salah
- Reactive state tidak update

### **4. Network Issues:**
- CORS problems
- API endpoint tidak accessible
- Request timeout

---

## 🔧 **TROUBLESHOOTING:**

### **Jika Authentication Debug menunjukkan masalah:**

#### **Token Missing:**
```javascript
// Cek di console browser
localStorage.getItem('token')
// Harus mengembalikan token string
```

#### **User Not Logged In:**
```javascript
// Cek di console browser
localStorage.getItem('user')
// Harus mengembalikan user object
```

### **Jika API Test gagal:**

#### **Cek Network Tab:**
- Buka F12 → Network tab
- Klik "Test API Call"
- Cek apakah ada request ke `/api/dashboard/statistics`
- Cek status response (harus 200 OK)

#### **Cek Console Errors:**
- Buka F12 → Console tab
- Lihat apakah ada error messages
- Cek apakah ada CORS errors

### **Jika API berhasil tapi data tidak tampil:**

#### **Cek Data Assignment:**
```javascript
// Cek di console browser
console.log('Dashboard Data:', dashboardData.value)
// Harus menampilkan object dengan data
```

#### **Cek Template Binding:**
```vue
<!-- Cek apakah binding benar -->
{{ dashboardData?.kpi?.total_students || 'Loading...' }}
```

---

## 📊 **EXPECTED API RESPONSE:**

```json
{
  "success": true,
  "data": {
    "kpi": {
      "total_students": 18,
      "total_employees": 14,
      "utilization_rate": 3.6,
      "max_capacity": 500,
      "teacher_student_ratio": {
        "ratio": 1.4,
        "students": 18,
        "teachers": 13,
        "status": "excellent"
      }
    },
    "distribution": {
      "students_by_class": {
        "7A": 1, "7B": 1, "X IPA 1": 4, "X IPA 10": 1,
        "X IPA 2": 3, "X IPA 4": 1, "X IPA 5": 1, "X IPA 6": 1,
        "X IPA 7": 1, "X IPA 8": 1, "X IPA 9": 1, "XI IPS 1": 1, "XII IPA 1": 1
      },
      "students_by_level": {"X": 14, "XI": 1, "XII": 1},
      "employee_analysis": {
        "total": 14, "active": 14, "teachers": 13, "staff": 1, "active_percentage": 100
      }
    },
    "quality": {
      "data_quality": {
        "completeness_rate": 88.9,
        "complete_records": 16,
        "incomplete_records": 2,
        "status": "good"
      }
    }
  }
}
```

---

## ✅ **NEXT STEPS:**

1. **Akses Dashboard Debug** dan cek semua informasi
2. **Test API Call** dan lihat hasilnya
3. **Cek Console** untuk error messages
4. **Report hasil** untuk analisis lebih lanjut

**Dashboard Debug akan membantu mengidentifikasi masalah yang menyebabkan data tidak tampil!** 🔍
