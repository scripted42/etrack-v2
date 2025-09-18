import api from './api';

export interface FaceAttendanceRequest {
  employee_id: number;
  photo: string;
  location?: string;
  attendance_type?: 'early' | 'on_time' | 'late' | 'overtime';
}

export interface FaceRegistrationRequest {
  employee_id: number;
  photo: string;
}

export interface AttendanceHistoryFilters {
  start_date?: string;
  end_date?: string;
  employee_id?: number;
  attendance_type?: string;
  page?: number;
  per_page?: number;
}

export interface AttendanceRecord {
  id: number;
  employee_id: number;
  employee_name: string;
  attendance_date: string;
  attendance_time: string;
  attendance_type: string;
  photo_url?: string;
  confidence_score: number;
  location?: string;
  status: string;
  created_at: string;
}

export interface AttendanceStatistics {
  total_attendance: number;
  today_attendance: number;
  on_time_count: number;
  late_count: number;
  early_count: number;
  overtime_count: number;
  attendance_rate: number;
}

export const faceAttendanceService = {
  /**
   * Process face recognition attendance
   */
  async processAttendance(data: FaceAttendanceRequest) {
    try {
      const response = await api.post('/attendance/face-recognition', data);
      return response.data;
    } catch (error) {
      console.error('Face attendance error:', error);
      throw error;
    }
  },

  /**
   * Register employee face
   */
  async registerFace(data: FaceRegistrationRequest) {
    try {
      const response = await api.post('/attendance/face-recognition/register-face', data);
      return response.data;
    } catch (error) {
      console.error('Face registration error:', error);
      throw error;
    }
  },

  /**
   * Get attendance history
   */
  async getAttendanceHistory(filters: AttendanceHistoryFilters = {}) {
    try {
      const params = new URLSearchParams();
      
      if (filters.start_date) params.append('start_date', filters.start_date);
      if (filters.end_date) params.append('end_date', filters.end_date);
      if (filters.employee_id) params.append('employee_id', filters.employee_id.toString());
      if (filters.attendance_type) params.append('attendance_type', filters.attendance_type);
      if (filters.page) params.append('page', filters.page.toString());
      if (filters.per_page) params.append('per_page', filters.per_page.toString());

      const response = await api.get(`/attendance/face-recognition/history?${params.toString()}`);
      return response.data;
    } catch (error) {
      console.error('Attendance history error:', error);
      throw error;
    }
  },

  /**
   * Get attendance statistics
   */
  async getStatistics() {
    try {
      const response = await api.get('/attendance/face-recognition/statistics');
      return response.data;
    } catch (error) {
      console.error('Statistics error:', error);
      throw error;
    }
  },

  /**
   * Get employees list
   */
  async getEmployees() {
    try {
      const response = await api.get('/employees');
      return response.data;
    } catch (error) {
      console.error('Employees error:', error);
      throw error;
    }
  }
};

export default faceAttendanceService;
