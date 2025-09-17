export interface Student {
  id: number;
  user_id: number;
  nis: string;
  nama: string;
  kelas: string;
  status: 'aktif' | 'lulus' | 'pindah';
  photo_path?: string;
  qr_value?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    username: string;
    name: string;
    email: string;
    role?: {
      id: number;
      name: string;
    };
  };
  identity?: {
    id: number;
    student_id: number;
    nik?: string;
    nisn?: string;
    tempat_lahir?: string;
    tanggal_lahir?: string;
    jenis_kelamin?: string;
    agama?: string;
  };
  contact?: {
    id: number;
    student_id: number;
    alamat?: string;
    kota?: string;
    provinsi?: string;
    kode_pos?: string;
    no_hp?: string;
    email?: string;
  };
  guardians?: Array<{
    id: number;
    student_id: number;
    nama: string;
    hubungan?: string;
    tanggal_lahir?: string;
    pekerjaan?: string;
    no_hp?: string;
  }>;
}



