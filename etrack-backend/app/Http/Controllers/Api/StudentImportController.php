<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentIdentity;
use App\Models\StudentContact;
use App\Models\StudentGuardian;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentImportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $auth = $request->user();
        if (!$auth->role->permissions->contains('name', 'manage_students')) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimetypes:text/plain,text/csv,text/tsv,text/plain,application/vnd.ms-excel',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['success' => false, 'message' => 'File tidak bisa dibaca'], 400);
        }

        // Expected header (lengkap):
        // nis,nama,kelas,status,qr_value,
        // nik,nisn,tempat_lahir,tanggal_lahir,jenis_kelamin,agama,
        // alamat,kota,provinsi,kode_pos,no_hp,email,
        // wali_nama,wali_hubungan,wali_no_hp,wali_alamat,wali_pekerjaan
        $header = fgetcsv($handle);
        $normalize = function($s){return strtolower(trim((string)$s));};
        $map = [];
        foreach ($header as $idx => $h) { $map[$normalize($h)] = $idx; }

        $requiredCols = ['nis','nama','kelas','status'];
        foreach ($requiredCols as $col) {
            if (!array_key_exists($col, $map)) {
                fclose($handle);
                return response()->json(['success'=>false,'message'=>"Kolom wajib hilang: $col"],422);
            }
        }

        $success = 0; $failed = 0; $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $nis = trim($row[$map['nis']] ?? '');
            $nama = trim($row[$map['nama']] ?? '');
            $kelas = trim($row[$map['kelas']] ?? '');
            $status = trim($row[$map['status']] ?? 'aktif');
            $qr = isset($map['qr_value']) ? trim((string)$row[$map['qr_value']]) : '';

            // identity
            $nik = isset($map['nik']) ? trim((string)$row[$map['nik']]) : null;
            $nisn = isset($map['nisn']) ? trim((string)$row[$map['nisn']]) : null;
            $tempat_lahir = isset($map['tempat_lahir']) ? trim((string)$row[$map['tempat_lahir']]) : null;
            $tanggal_lahir = isset($map['tanggal_lahir']) ? trim((string)$row[$map['tanggal_lahir']]) : null;
            $jenis_kelamin = isset($map['jenis_kelamin']) ? trim((string)$row[$map['jenis_kelamin']]) : null;
            $agama = isset($map['agama']) ? trim((string)$row[$map['agama']]) : null;

            // contact
            $alamat = isset($map['alamat']) ? trim((string)$row[$map['alamat']]) : null;
            $kota = isset($map['kota']) ? trim((string)$row[$map['kota']]) : null;
            $provinsi = isset($map['provinsi']) ? trim((string)$row[$map['provinsi']]) : null;
            $kode_pos = isset($map['kode_pos']) ? trim((string)$row[$map['kode_pos']]) : null;
            $no_hp = isset($map['no_hp']) ? trim((string)$row[$map['no_hp']]) : null;
            $email = isset($map['email']) ? trim((string)$row[$map['email']]) : null;

            // guardian
            $wali_nama = isset($map['wali_nama']) ? trim((string)$row[$map['wali_nama']]) : '';
            $wali_hubungan = isset($map['wali_hubungan']) ? trim((string)$row[$map['wali_hubungan']]) : '';
            $wali_nohp = isset($map['wali_no_hp']) ? trim((string)$row[$map['wali_no_hp']]) : '';
            $wali_alamat = isset($map['wali_alamat']) ? trim((string)$row[$map['wali_alamat']]) : '';
            $wali_pekerjaan = isset($map['wali_pekerjaan']) ? trim((string)$row[$map['wali_pekerjaan']]) : '';

            if ($nis === '' || $nama === '' || $kelas === '') {
                $failed++; $errors[] = "Baris nis=$nis: data wajib kosong"; continue;
            }
            if (Student::where('nis',$nis)->exists()) {
                $failed++; $errors[] = "Baris nis=$nis: NIS sudah ada"; continue;
            }
            // Perbaikan: jika user sudah ada tapi tidak punya student terkait, gunakan ulang user tsb
            $existingUser = User::where('username',$nis)->first();
            if ($existingUser) {
                $existingStudent = Student::where('user_id', $existingUser->id)->first();
                if ($existingStudent) {
                    $failed++; $errors[] = "Baris nis=$nis: username sudah ada"; continue;
                }
            }

            DB::beginTransaction();
            try {
                // Convert date format to MySQL format (YYYY-MM-DD)
                $tanggal_lahir_mysql = null;
                if ($tanggal_lahir) {
                    // Handle DD/MM/YYYY format (Indonesian format)
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tanggal_lahir, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $year = $matches[3];
                        $tanggal_lahir_mysql = $year . '-' . $month . '-' . $day;
                    } else {
                        // Try other formats with strtotime
                        $ts = strtotime($tanggal_lahir);
                        if ($ts) {
                            $tanggal_lahir_mysql = date('Y-m-d', $ts);
                        }
                    }
                }
                
                $year = null;
                if ($tanggal_lahir_mysql) { $year = date('Y', strtotime($tanggal_lahir_mysql)); }
                $password = $nis . ($year ?? '');
                if (strlen($password) < 8) { $password = str_pad($nis, 8, '0'); }

                if ($existingUser) {
                    // Reuse user yatim (tanpa student), pastikan properti diperbarui
                    $existingUser->update([
                        'name' => $nama,
                        'email' => $existingUser->email ?: ($nis.'@student.local'),
                        'password' => Hash::make($password),
                        'role_id' => 3, // Siswa role
                        'status' => 'aktif',
                    ]);
                    $user = $existingUser;
                } else {
                    $user = User::create([
                        'username' => $nis,
                        'name' => $nama,
                        'email' => $nis.'@student.local',
                        'password' => Hash::make($password),
                        'role_id' => 3, // Siswa role
                        'status' => 'aktif',
                    ]);
                }

                $student = Student::create([
                    'user_id' => $user->id,
                    'nis' => $nis,
                    'nama' => $nama,
                    'kelas' => $kelas,
                    'status' => $status ?: 'aktif',
                    'qr_value' => $qr !== '' ? $qr : $nis,
                ]);

                if ($nik || $nisn || $tempat_lahir || $tanggal_lahir_mysql || $jenis_kelamin || $agama) {
                    StudentIdentity::create([
                        'student_id' => $student->id,
                        'nik' => $nik ?: null,
                        'nisn' => $nisn ?: null,
                        'tempat_lahir' => $tempat_lahir ?: null,
                        'tanggal_lahir' => $tanggal_lahir_mysql ?: null,
                        'jenis_kelamin' => in_array($jenis_kelamin, ['L','P']) ? $jenis_kelamin : null,
                        'agama' => $agama ?: null,
                    ]);
                }

                if ($alamat || $kota || $provinsi || $kode_pos || $no_hp || $email) {
                    StudentContact::create([
                        'student_id' => $student->id,
                        'alamat' => $alamat ?: null,
                        'kota' => $kota ?: null,
                        'provinsi' => $provinsi ?: null,
                        'kode_pos' => $kode_pos ?: null,
                        'no_hp' => $no_hp ?: null,
                        'email' => $email ?: null,
                    ]);
                }

                if ($wali_nama) {
                    StudentGuardian::create([
                        'student_id' => $student->id,
                        'nama' => $wali_nama,
                        'hubungan' => $wali_hubungan ?: null,
                        'no_hp' => $wali_nohp ?: null,
                        'alamat' => $wali_alamat ?: null,
                        'pekerjaan' => $wali_pekerjaan ?: null,
                    ]);
                }

                AuditLog::create([
                    'user_id' => $auth->id,
                    'action' => 'IMPORT_STUDENT',
                    'details' => ['nis'=>$nis],
                    'ip_address' => $request->ip(),
                ]);

                DB::commit();
                $success++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $failed++; $errors[] = "Baris nis=$nis: ".$e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => 'Import selesai',
            'data' => [
                'imported' => $success,
                'failed' => $failed,
                'errors' => $errors,
            ]
        ]);
    }
}


