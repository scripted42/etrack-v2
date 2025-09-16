# 🔧 DASHBOARD DATA TROUBLESHOOTING

## ❌ **MASALAH: Data Dashboard Tidak Tampil**

### **🔍 DIAGNOSIS MASALAH:**

**Gejala:**
- Dashboard menampilkan "0" untuk semua nilai
- Menampilkan "Data tidak tersedia"
- ISO Compliance Score menunjukkan 50%
- Semua KPI cards kosong

**Penyebab Kemungkinan:**
1. **Backend server tidak berjalan**
2. **Authentication token tidak valid**
3. **API endpoint tidak accessible**
4. **Frontend service error**
5. **Data structure mismatch**

---

## ✅ **SOLUSI STEP-BY-STEP:**

### **1. Cek Backend Server**
```bash
# Pastikan backend server berjalan
cd etrack-backend
php artisan serve --host=0.0.0.0 --port=8000
```

**Test API:**
```bash
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/dashboard/statistics"
```

### **2. Cek Frontend Server**
```bash
# Pastikan frontend server berjalan
cd etrack-frontend
npm run dev
```

**Test Frontend:**
```bash
curl -s "http://localhost:5173"
```

### **3. Cek Authentication**
- Pastikan user sudah login
- Cek token di localStorage
- Cek network tab di browser untuk 401/403 errors

### **4. Cek Console Browser**
- Buka Developer Tools (F12)
- Cek Console tab untuk error messages
- Cek Network tab untuk failed requests

---

## 🔧 **PERBAIKAN YANG SUDAH DILAKUKAN:**

### **1. Update Interface DashboardStats**
```typescript
// Sebelum (tidak sesuai dengan API response)
export interface DashboardStats {
  overview: { ... };
  students_by_status: Record<string, number>;
  // ...
}

// Sesudah (sesuai dengan API response)
export interface DashboardStats {
  kpi: {
    total_students: number;
    total_employees: number;
    utilization_rate: number;
    // ...
  };
  distribution: { ... };
  quality: { ... };
  // ...
}
```

### **2. Tambahkan Error Handling**
```typescript
async function loadDashboardData() {
  try {
    console.log('Loading dashboard data...');
    const [statsResponse, healthResponse] = await Promise.all([
      fetchDashboardStats(),
      fetchSystemHealth()
    ]);
    
    console.log('Dashboard stats response:', statsResponse);
    
    if (statsResponse.success) {
      stats.value = statsResponse.data;
      console.log('Stats loaded successfully:', stats.value);
    } else {
      console.error('Failed to load stats:', statsResponse);
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
    // Set default values if API fails
    stats.value = { /* default values */ };
  }
}
```

### **3. Tambahkan Logging untuk Debug**
- Console log untuk tracking data loading
- Error logging untuk troubleshooting
- Response validation

---

## 🎯 **CARA TESTING:**

### **1. Test API Langsung**
```bash
# Test tanpa authentication (harus return 401)
curl "http://localhost:8000/api/dashboard/statistics"

# Test dengan authentication
curl -H "Authorization: Bearer TOKEN" "http://localhost:8000/api/dashboard/statistics"
```

### **2. Test Frontend**
1. Buka browser ke `http://localhost:5173`
2. Login dengan `admin` / `admin123`
3. Buka Developer Tools (F12)
4. Cek Console untuk log messages
5. Cek Network tab untuk API calls

### **3. Expected Response**
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
      "students_by_class": { "7A": 1, "7B": 1, "X IPA 1": 4, ... },
      "students_by_level": { "X": 14, "XI": 1, "XII": 1 },
      "employee_analysis": {
        "total": 14,
        "active": 14,
        "teachers": 13,
        "staff": 1,
        "active_percentage": 100
      }
    },
    "quality": {
      "data_quality": {
        "completeness_rate": 88.9,
        "complete_records": 16,
        "incomplete_records": 2,
        "status": "good"
      },
      "performance_indicators": {
        "data_accuracy": 95.5,
        "system_uptime": 99.8,
        "user_satisfaction": 4.2,
        "response_time": 1.2
      }
    },
    "system": {
      "activity": {
        "activity_24h": 70,
        "activity_7d": 70,
        "successful_logins": 4,
        "failed_logins": 6,
        "security_score": 40
      },
      "alerts": [
        {
          "type": "info",
          "title": "Jumlah Siswa Rendah",
          "message": "Total siswa hanya 18. Pertimbangkan strategi penerimaan siswa baru.",
          "priority": "medium"
        }
      ]
    }
  }
}
```

---

## 🚨 **TROUBLESHOOTING CHECKLIST:**

### **Backend Issues:**
- [ ] Backend server berjalan di port 8000
- [ ] Database connection OK
- [ ] API endpoint `/api/dashboard/statistics` accessible
- [ ] Authentication working
- [ ] Data exists in database

### **Frontend Issues:**
- [ ] Frontend server berjalan di port 5173
- [ ] User sudah login
- [ ] Token valid di localStorage
- [ ] API service working
- [ ] No console errors
- [ ] Network requests successful

### **Data Issues:**
- [ ] Interface matches API response
- [ ] Data structure correct
- [ ] Default values set
- [ ] Error handling working

---

## ✅ **STATUS PERBAIKAN:**

- ✅ **Backend API:** Berfungsi (data real dari database)
- ✅ **Frontend Server:** Berfungsi
- ✅ **Interface:** Diperbaiki sesuai API response
- ✅ **Error Handling:** Ditambahkan
- ✅ **Logging:** Ditambahkan untuk debug
- ✅ **Default Values:** Ditambahkan untuk fallback

**Dashboard sekarang sudah diperbaiki dan seharusnya menampilkan data real dari database!** 🎉

**Silakan refresh browser dan cek console untuk log messages.**
