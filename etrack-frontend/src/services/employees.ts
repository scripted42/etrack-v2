import api from '@/services/api';

export interface Employee {
  id: number;
  user_id: number;
  nip: string;
  nama: string;
  jabatan: string;
  status: string;
  photo_path?: string;
  qr_value?: string;
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
    employee_id: number;
    nik?: string;
    tempat_lahir?: string;
    tanggal_lahir?: string;
    jenis_kelamin?: string;
    agama?: string;
  };
  contact?: {
    id: number;
    employee_id: number;
    alamat?: string;
    kota?: string;
    provinsi?: string;
    kode_pos?: string;
    no_hp?: string;
    email?: string;
  };
  families?: Array<{
    id: number;
    employee_id: number;
    nama: string;
    hubungan?: string;
    tanggal_lahir?: string;
    pekerjaan?: string;
    no_hp?: string;
  }>;
}

export async function fetchEmployees(filters: { page?: number; perPage?: number; status?: string; jabatan?: string; search?: string } = {}) {
  console.log('Fetching employees with filters:', filters);
  try {
    const res = await api.get('/employees', { params: filters });
    console.log('Employees API response:', res.data);
    return res.data;
  } catch (error) {
    console.error('Error fetching employees:', error);
    throw error;
  }
}

export async function createEmployee(payload: any) {
  const res = await api.post('/employees', payload);
  return res.data?.data ?? res.data;
}

export async function updateEmployee(id: number, payload: any) {
  if (payload?.photo instanceof File) {
    // Use dedicated photo upload endpoint
    const form = new FormData();
    form.append('photo', payload.photo);
    const res = await api.post(`/employees/${id}/photo`, form, { headers: { 'Content-Type': 'multipart/form-data' } });
    return res.data?.data ?? res.data;
  }
  const res = await api.put(`/employees/${id}`, payload);
  return res.data?.data ?? res.data;
}

export async function deleteEmployee(id: number) {
  const res = await api.delete(`/employees/${id}`);
  return res.data;
}

export async function importEmployeesCsv(file: File) {
  if (!file) {
    throw new Error('File tidak valid');
  }
  
  const formData = new FormData();
  formData.append('file', file);
  
  const res = await api.post('/employees/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  return res.data;
}

export async function downloadEmployeeTemplate() {
  const res = await api.get('/employees/template');
  return res.data;
}
