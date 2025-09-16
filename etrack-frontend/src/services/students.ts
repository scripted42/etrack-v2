import api from '@/services/api';

export interface Student {
  id: number;
  user_id: number;
  nis: string;
  nama: string;
  kelas: string;
  jurusan?: string | null;
  status: 'aktif' | 'lulus' | 'pindah';
  created_at?: string;
  updated_at?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total?: number;
  current_page?: number;
  per_page?: number;
}

export async function fetchStudents(params: { page?: number; perPage?: number; status?: string; kelas?: string; search?: string } = {}) {
  const { page = 1, perPage = 10, status, kelas, search } = params;
  const per_page = perPage === -1 ? -1 : perPage; // -1 berarti ALL, kirim -1 ke backend
  const res = await api.get('/students', { params: { page, per_page, status, kelas, search } });
  // Return the full response for pagination handling
  return res.data;
}

export async function fetchStudent(id: number) {
  const res = await api.get(`/students/${id}`);
  return res.data?.data ?? res.data;
}

export async function createStudent(payload: any) {
  // If payload contains File (photo), use multipart
  if (payload?.photo instanceof File) {
    const form = new FormData();
    Object.entries(payload).forEach(([k, v]: any) => {
      if (v === undefined || v === null) return;
      if (typeof v === 'object' && !(v instanceof File)) {
        form.append(k, JSON.stringify(v));
      } else {
        form.append(k, v as any);
      }
    });
    const res = await api.post('/students', form, { headers: { 'Content-Type': 'multipart/form-data' } });
    return res.data?.data ?? res.data;
  }
  const res = await api.post('/students', payload);
  return res.data?.data ?? res.data;
}

export async function updateStudent(id: number, payload: any) {
  if (payload?.photo instanceof File) {
    // Use dedicated photo upload endpoint
    const form = new FormData();
    form.append('photo', payload.photo);
    const res = await api.post(`/students/${id}/photo`, form, { headers: { 'Content-Type': 'multipart/form-data' } });
    return res.data?.data ?? res.data;
  }
  const res = await api.put(`/students/${id}`, payload);
  return res.data?.data ?? res.data;
}

export async function deleteStudent(id: number) {
  const res = await api.delete(`/students/${id}`);
  return res.data?.data ?? res.data;
}

// Import CSV Students
export async function importStudentsCsv(file: File) {
  const formData = new FormData();
  formData.append('file', file);
  const res = await api.post('/students/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  return res.data;
}


