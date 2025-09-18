# 📊 **IMPORT FORMAT UPDATE - SESUAI FORMAT YANG SUDAH ADA**

## 📋 **OVERVIEW**

Dokumen ini menjelaskan update format import data siswa agar sesuai dengan format yang sudah digunakan di menu data siswa, dengan kolom-kolom lengkap termasuk data identitas, kontak, dan wali.

---

## 🔄 **PERUBAHAN FORMAT IMPORT**

### **❌ Format Lama (Sebelumnya):**
```
nis, nama, kelas, status, email
```

### **✅ Format Baru (Sesuai Format Existing):**
```
nis, nama, kelas, status, nisn, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, no_hp, wali_nama, wali_hubungan, wali_no_hp
```

---

## 📊 **DETAIL FORMAT BARU**

### **1. Kolom Utama (Required):**
- **`nis`** - Nomor Induk Siswa (wajib)
- **`nama`** - Nama lengkap siswa (wajib)
- **`kelas`** - Kelas siswa (opsional)
- **`status`** - Status siswa: aktif/nonaktif (opsional, default: aktif)

### **2. Kolom Identitas (Optional):**
- **`nisn`** - Nomor Induk Siswa Nasional
- **`tempat_lahir`** - Tempat lahir
- **`tanggal_lahir`** - Tanggal lahir (format: d/m/Y, d-m-Y, Y-m-d)
- **`jenis_kelamin`** - Jenis kelamin: L (Laki-laki) / P (Perempuan)
- **`agama`** - Agama

### **3. Kolom Kontak (Optional):**
- **`no_hp`** - Nomor HP siswa

### **4. Kolom Wali (Optional):**
- **`wali_nama`** - Nama wali
- **`wali_hubungan`** - Hubungan wali (Ayah, Ibu, dll)
- **`wali_no_hp`** - Nomor HP wali

---

## 🏗️ **IMPLEMENTASI TEKNIS**

### **1. Template Export Update**

#### **📁 `app/Exports/StudentTemplateExport.php`**
```php
// Sample Data dengan format lengkap
[
    '91001', 'Rizky', '7A', 'aktif', '12345678', 'Surabaya', '1/2/2012', 'L', 'Islam', '811111111', 'Bapak Satu', 'Ayah', '811111110'
]

// Headers lengkap
[
    'nis', 'nama', 'kelas', 'status', 'nisn', 'tempat_lahir', 'tanggal_lahir', 
    'jenis_kelamin', 'agama', 'no_hp', 'wali_nama', 'wali_hubungan', 'wali_no_hp'
]
```

### **2. Import Logic Update**

#### **📁 `app/Imports/StudentImport.php`**
```php
// Enhanced model() method dengan:
// - Student profile creation
// - Student identity creation (student_identities table)
// - Student contact creation (student_contacts table)  
// - Student guardian creation (student_guardians table)
// - Date parsing untuk berbagai format tanggal
// - Validation rules untuk semua kolom
```

### **3. Database Integration**

#### **Tabel yang Terlibat:**
1. **`students`** - Data utama siswa
2. **`student_identities`** - Data identitas (NISN, tempat lahir, dll)
3. **`student_contacts`** - Data kontak (no HP)
4. **`student_guardians`** - Data wali
5. **`users`** - User account untuk login

#### **Relasi Data:**
```sql
students.id -> student_identities.student_id
students.id -> student_contacts.student_id  
students.id -> student_guardians.student_id
students.user_id -> users.id
```

---

## 📋 **SAMPLE DATA TEMPLATE**

### **Format Excel Template:**
| nis | nama | kelas | status | nisn | tempat_lahir | tanggal_lahir | jenis_kelamin | agama | no_hp | wali_nama | wali_hubungan | wali_no_hp |
|-----|------|-------|--------|------|--------------|---------------|---------------|-------|-------|-----------|---------------|------------|
| 91001 | Rizky | 7A | aktif | 12345678 | Surabaya | 1/2/2012 | L | Islam | 811111111 | Bapak Satu | Ayah | 811111110 |
| 91002 | Donna | 7B | aktif | 12345679 | Sidoarjo | 3/4/2012 | P | Kristen | 822222222 | Ibu Dua | Ibu | 822222220 |
| 91003 | Ahmad Fauzi | 8A | aktif | 12345680 | Malang | 5/6/2011 | L | Islam | 833333333 | Bapak Tiga | Ayah | 833333330 |

---

## 🔧 **FITUR YANG DITINGKATKAN**

### **1. ✅ Data Parsing yang Fleksibel**

#### **Date Parsing:**
```php
// Support multiple date formats:
// - d/m/Y (1/2/2012)
// - d-m-Y (1-2-2012)  
// - Y-m-d (2012-02-01)
// - d/m/y (1/2/12)
// - d-m-y (1-2-12)
```

#### **Gender Validation:**
```php
// Valid values: L (Laki-laki), P (Perempuan)
'jenis_kelamin' => 'nullable|string|in:L,P'
```

### **2. ✅ Database Integration Lengkap**

#### **Student Identity:**
- NISN, tempat lahir, tanggal lahir, jenis kelamin, agama
- Tersimpan di tabel `student_identities`

#### **Student Contact:**
- Nomor HP siswa
- Tersimpan di tabel `student_contacts`

#### **Student Guardian:**
- Nama wali, hubungan, nomor HP wali
- Tersimpan di tabel `student_guardians`

### **3. ✅ Validation Rules Lengkap**

```php
public function rules(): array
{
    return [
        'nis' => 'required|string|max:20',
        'nama' => 'required|string|max:255',
        'kelas' => 'nullable|string|max:10',
        'status' => 'nullable|string|in:aktif,nonaktif',
        'nisn' => 'nullable|string|max:20',
        'tempat_lahir' => 'nullable|string|max:100',
        'tanggal_lahir' => 'nullable|string',
        'jenis_kelamin' => 'nullable|string|in:L,P',
        'agama' => 'nullable|string|max:50',
        'no_hp' => 'nullable|string|max:20',
        'wali_nama' => 'nullable|string|max:255',
        'wali_hubungan' => 'nullable|string|max:50',
        'wali_no_hp' => 'nullable|string|max:20',
    ];
}
```

---

## 🎯 **BENEFITS**

### **1. Konsistensi Data:**
- **Format Sama** - Import menggunakan format yang sama dengan menu data siswa
- **Data Lengkap** - Import data identitas, kontak, dan wali sekaligus
- **Struktur Database** - Data tersimpan di tabel yang sesuai

### **2. User Experience:**
- **Template Lengkap** - Template dengan sample data yang jelas
- **Format Familiar** - Format yang sudah dikenal user
- **Data Validation** - Validasi yang sesuai dengan struktur database

### **3. Data Quality:**
- **Complete Records** - Import data siswa lengkap sekaligus
- **Data Integrity** - Data tersimpan di tabel yang tepat
- **Relationship Management** - Relasi data terjaga dengan baik

---

## 📊 **TESTING SCENARIOS**

### **✅ Template Download:**
1. **Download Template** - Template Excel dengan 13 kolom
2. **Sample Data** - 5 contoh data siswa lengkap
3. **Format Validation** - Headers sesuai dengan database

### **✅ Import Functionality:**
1. **Basic Import** - Import dengan NIS dan Nama saja
2. **Complete Import** - Import dengan semua data lengkap
3. **Partial Import** - Import dengan data sebagian
4. **Error Handling** - Error handling untuk data tidak valid

### **✅ Database Integration:**
1. **Student Table** - Data utama tersimpan
2. **Identity Table** - Data identitas tersimpan
3. **Contact Table** - Data kontak tersimpan
4. **Guardian Table** - Data wali tersimpan
5. **User Table** - User account dibuat

---

## 🚀 **CARA PENGGUNAAN**

### **1. Download Template:**
1. Buka menu **Import Data**
2. Klik **Download Template** di section Import Data Siswa
3. File Excel akan didownload dengan format lengkap (13 kolom)
4. Buka file dan lihat sample data

### **2. Prepare Data:**
1. Isi data sesuai format template
2. **Required:** NIS dan Nama (wajib)
3. **Optional:** Data identitas, kontak, dan wali
4. **Date Format:** Gunakan format d/m/Y (contoh: 1/2/2012)

### **3. Import Data:**
1. Pilih file Excel/CSV yang sudah disiapkan
2. Klik **Import Siswa**
3. Sistem akan:
   - Validasi data
   - Buat user account
   - Simpan data siswa
   - Simpan data identitas (jika ada)
   - Simpan data kontak (jika ada)
   - Simpan data wali (jika ada)

### **4. Check Results:**
1. Lihat hasil import (berhasil/gagal)
2. Cek data di menu **Data Siswa**
3. Lihat riwayat import di **Import History**

---

## ✅ **CONCLUSION**

Format import data siswa telah **100% disesuaikan** dengan format yang sudah digunakan di menu data siswa:

### **✅ Completed Updates:**
1. **Template Format** - 13 kolom sesuai format existing
2. **Sample Data** - Data contoh yang lengkap
3. **Import Logic** - Logic import untuk semua tabel
4. **Data Validation** - Validasi untuk semua kolom
5. **Date Parsing** - Support multiple date formats
6. **Database Integration** - Integrasi dengan semua tabel terkait

### **🎯 Ready for Production:**
Sistem import sekarang menggunakan format yang **konsisten** dengan menu data siswa yang sudah ada, memungkinkan import data siswa lengkap dengan identitas, kontak, dan data wali sekaligus!

**Format import sekarang sesuai dengan format yang sudah digunakan di menu data siswa!** 🎉



