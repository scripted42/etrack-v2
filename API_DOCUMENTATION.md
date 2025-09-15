# E-Track API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
API menggunakan Laravel Sanctum untuk authentication. Semua request yang memerlukan authentication harus menyertakan header:
```
Authorization: Bearer {token}
```

## Response Format
Semua response mengikuti format berikut:
```json
{
  "success": true|false,
  "message": "Optional message",
  "data": {}, // Response data
  "errors": {} // Validation errors (if any)
}
```

## Endpoints

### Authentication

#### POST /login
Login user ke sistem.

**Request Body:**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "name": "Administrator",
      "email": "admin@smpn14sby.sch.id",
      "role_id": 1,
      "status": "aktif",
      "role": {
        "id": 1,
        "name": "Admin",
        "description": "Administrator sistem dengan akses penuh"
      }
    },
    "token": "1|abc123..."
  }
}
```

#### POST /logout
Logout user dari sistem.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

#### GET /me
Mendapatkan informasi user yang sedang login.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "name": "Administrator",
    "email": "admin@smpn14sby.sch.id",
    "role_id": 1,
    "status": "aktif",
    "role": {
      "id": 1,
      "name": "Admin",
      "description": "Administrator sistem dengan akses penuh"
    },
    "student": null,
    "employee": null
  }
}
```

#### POST /change-password
Mengubah password user.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword",
  "new_password_confirmation": "newpassword"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password berhasil diubah"
}
```

### Dashboard

#### GET /dashboard/statistics
Mendapatkan statistik dashboard.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "total_students": 1200,
    "total_employees": 45,
    "students_by_class": [
      {
        "kelas": "VII A",
        "total": 32
      },
      {
        "kelas": "VII B",
        "total": 30
      }
    ],
    "employees_by_position": [
      {
        "jabatan": "Guru",
        "total": 35
      },
      {
        "jabatan": "TU",
        "total": 8
      }
    ],
    "recent_activities": [
      {
        "id": 1,
        "user_id": 1,
        "action": "LOGIN_SUCCESS",
        "details": {
          "username": "admin"
        },
        "ip_address": "127.0.0.1",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "user": {
          "id": 1,
          "name": "Administrator"
        }
      }
    ],
    "login_stats": {
      "today": 25,
      "this_week": 150,
      "this_month": 600
    }
  }
}
```

#### GET /dashboard/chart-data
Mendapatkan data untuk chart dashboard.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "students_growth": [
      {
        "year": 2024,
        "month": 1,
        "total": 45
      },
      {
        "year": 2024,
        "month": 2,
        "total": 52
      }
    ],
    "employees_by_status": [
      {
        "status": "aktif",
        "total": 40
      },
      {
        "status": "cuti",
        "total": 3
      },
      {
        "status": "pensiun",
        "total": 2
      }
    ],
    "students_by_status": [
      {
        "status": "aktif",
        "total": 1150
      },
      {
        "status": "lulus",
        "total": 45
      },
      {
        "status": "pindah",
        "total": 5
      }
    ]
  }
}
```

### Students

#### GET /students
Mendapatkan daftar siswa dengan pagination dan filter.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Halaman (default: 1)
- `per_page` (optional): Jumlah data per halaman (default: 15)
- `status` (optional): Filter berdasarkan status (aktif, lulus, pindah)
- `kelas` (optional): Filter berdasarkan kelas
- `search` (optional): Pencarian berdasarkan nama atau NIS

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "nis": "2024001",
        "nama": "Ahmad Rizki",
        "kelas": "VII A",
        "jurusan": null,
        "status": "aktif",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "user": {
          "id": 2,
          "username": "ahmad.rizki",
          "name": "Ahmad Rizki",
          "email": "ahmad.rizki@smpn14sby.sch.id",
          "role": {
            "id": 5,
            "name": "Siswa"
          }
        }
      }
    ],
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  }
}
```

#### POST /students
Membuat data siswa baru.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "username": "ahmad.rizki",
  "name": "Ahmad Rizki",
  "email": "ahmad.rizki@smpn14sby.sch.id",
  "password": "password123",
  "nis": "2024001",
  "nama": "Ahmad Rizki",
  "kelas": "VII A",
  "jurusan": null,
  "status": "aktif"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Data siswa berhasil ditambahkan",
  "data": {
    "id": 1,
    "user_id": 2,
    "nis": "2024001",
    "nama": "Ahmad Rizki",
    "kelas": "VII A",
    "jurusan": null,
    "status": "aktif",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "user": {
      "id": 2,
      "username": "ahmad.rizki",
      "name": "Ahmad Rizki",
      "email": "ahmad.rizki@smpn14sby.sch.id",
      "role": {
        "id": 5,
        "name": "Siswa"
      }
    }
  }
}
```

#### GET /students/{id}
Mendapatkan detail siswa berdasarkan ID.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 2,
    "nis": "2024001",
    "nama": "Ahmad Rizki",
    "kelas": "VII A",
    "jurusan": null,
    "status": "aktif",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "user": {
      "id": 2,
      "username": "ahmad.rizki",
      "name": "Ahmad Rizki",
      "email": "ahmad.rizki@smpn14sby.sch.id",
      "role": {
        "id": 5,
        "name": "Siswa"
      },
      "documents": []
    }
  }
}
```

#### PUT /students/{id}
Memperbarui data siswa.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "username": "ahmad.rizki",
  "name": "Ahmad Rizki",
  "email": "ahmad.rizki@smpn14sby.sch.id",
  "nis": "2024001",
  "nama": "Ahmad Rizki",
  "kelas": "VII B",
  "jurusan": null,
  "status": "aktif"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Data siswa berhasil diperbarui",
  "data": {
    "id": 1,
    "user_id": 2,
    "nis": "2024001",
    "nama": "Ahmad Rizki",
    "kelas": "VII B",
    "jurusan": null,
    "status": "aktif",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T11:30:00.000000Z",
    "user": {
      "id": 2,
      "username": "ahmad.rizki",
      "name": "Ahmad Rizki",
      "email": "ahmad.rizki@smpn14sby.sch.id",
      "role": {
        "id": 5,
        "name": "Siswa"
      }
    }
  }
}
```

#### DELETE /students/{id}
Menghapus data siswa.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Data siswa berhasil dihapus"
}
```

### Employees

Endpoints untuk employees mengikuti pola yang sama dengan students:

- `GET /employees` - List employees
- `POST /employees` - Create employee
- `GET /employees/{id}` - Get employee detail
- `PUT /employees/{id}` - Update employee
- `DELETE /employees/{id}` - Delete employee

**Request/Response format sama dengan students, dengan field:**
- `nip` (string, unique)
- `nama` (string)
- `jabatan` (string)
- `status` (enum: aktif, cuti, pensiun)

### Users

Endpoints untuk users mengikuti pola yang sama:

- `GET /users` - List users
- `POST /users` - Create user
- `GET /users/{id}` - Get user detail
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user

### Audit Logs

#### GET /audit-logs
Mendapatkan daftar audit log.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Halaman
- `per_page` (optional): Jumlah data per halaman
- `user_id` (optional): Filter berdasarkan user ID
- `action` (optional): Filter berdasarkan action
- `date_from` (optional): Filter dari tanggal
- `date_to` (optional): Filter sampai tanggal

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "action": "LOGIN_SUCCESS",
        "details": {
          "username": "admin"
        },
        "ip_address": "127.0.0.1",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "user": {
          "id": 1,
          "name": "Administrator"
        }
      }
    ],
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

#### GET /audit-logs/{id}
Mendapatkan detail audit log.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "action": "LOGIN_SUCCESS",
    "details": {
      "username": "admin"
    },
    "ip_address": "127.0.0.1",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@smpn14sby.sch.id"
    }
  }
}
```

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "username": ["Username sudah digunakan"],
    "email": ["Email sudah digunakan"]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Tidak memiliki akses untuk mengelola data siswa"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Data tidak ditemukan"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Terjadi kesalahan saat menyimpan data",
  "error": "Database connection failed"
}
```

## Rate Limiting

API menggunakan rate limiting dengan konfigurasi:
- 60 requests per minute untuk authenticated users
- 10 requests per minute untuk unauthenticated requests

## CORS

API dikonfigurasi untuk menerima request dari:
- `http://localhost:3000`
- `http://localhost:5173`
- Domain production (dikonfigurasi di `.env`)

## Testing

Untuk testing API, gunakan tools seperti:
- Postman
- Insomnia
- curl
- HTTPie

Contoh curl command:
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Get students (with token)
curl -X GET http://localhost:8000/api/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```
