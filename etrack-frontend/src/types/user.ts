export interface User {
  id: number;
  username: string;
  name: string;
  email: string;
  role_id: number;
  status: 'aktif' | 'nonaktif';
  last_login?: string;
  created_at: string;
  updated_at: string;
  role?: Role;
  student?: Student;
  employee?: Employee;
}

export interface Role {
  id: number;
  name: string;
  description?: string;
  created_at: string;
  updated_at: string;
  permissions?: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  description?: string;
  created_at: string;
  updated_at: string;
}

export interface Student {
  id: number;
  user_id: number;
  nis: string;
  nama: string;
  kelas: string;
  jurusan?: string;
  status: 'aktif' | 'lulus' | 'pindah';
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface Employee {
  id: number;
  user_id: number;
  nip: string;
  nama: string;
  jabatan: string;
  status: 'aktif' | 'cuti' | 'pensiun';
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface Document {
  id: number;
  user_id: number;
  type: string;
  file_path: string;
  hash: string;
  uploaded_at: string;
  user?: User;
}

export interface AuditLog {
  id: number;
  user_id?: number;
  action: string;
  details?: any;
  ip_address?: string;
  created_at: string;
  user?: User;
}

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface ChangePasswordData {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: any;
}

export interface PaginatedResponse<T = any> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}
