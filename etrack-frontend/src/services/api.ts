import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { API_CONFIG } from '@/config/api';

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_CONFIG.BASE_URL,
      timeout: API_CONFIG.TIMEOUT,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response interceptor
    this.api.interceptors.response.use(
      (response: AxiosResponse) => {
        return response;
      },
      (error) => {
        if (error.response?.status === 401) {
          // Token expired or invalid
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  // Auth methods
  async login(credentials: { username: string; password: string }) {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.AUTH.LOGIN, credentials);
    return response.data;
  }

  async logout() {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.AUTH.LOGOUT);
    return response.data;
  }

  async getMe() {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.AUTH.ME);
    return response.data;
  }

  async changePassword(data: { current_password: string; new_password: string; new_password_confirmation: string }) {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.AUTH.CHANGE_PASSWORD, data);
    return response.data;
  }

  // Dashboard methods
  async getDashboardStatistics() {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.DASHBOARD.STATISTICS);
    return response.data;
  }

  async getChartData() {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.DASHBOARD.CHART_DATA);
    return response.data;
  }

  // Student methods
  async getStudents(params?: any) {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.STUDENTS, { params });
    return response.data;
  }

  async getStudent(id: number) {
    const response = await this.api.get(`${API_CONFIG.ENDPOINTS.STUDENTS}/${id}`);
    return response.data;
  }

  async createStudent(data: any) {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.STUDENTS, data);
    return response.data;
  }

  async updateStudent(id: number, data: any) {
    const response = await this.api.put(`${API_CONFIG.ENDPOINTS.STUDENTS}/${id}`, data);
    return response.data;
  }

  async deleteStudent(id: number) {
    const response = await this.api.delete(`${API_CONFIG.ENDPOINTS.STUDENTS}/${id}`);
    return response.data;
  }

  // Employee methods
  async getEmployees(params?: any) {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.EMPLOYEES, { params });
    return response.data;
  }

  async getEmployee(id: number) {
    const response = await this.api.get(`${API_CONFIG.ENDPOINTS.EMPLOYEES}/${id}`);
    return response.data;
  }

  async createEmployee(data: any) {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.EMPLOYEES, data);
    return response.data;
  }

  async updateEmployee(id: number, data: any) {
    const response = await this.api.put(`${API_CONFIG.ENDPOINTS.EMPLOYEES}/${id}`, data);
    return response.data;
  }

  async deleteEmployee(id: number) {
    const response = await this.api.delete(`${API_CONFIG.ENDPOINTS.EMPLOYEES}/${id}`);
    return response.data;
  }

  // User methods
  async getUsers(params?: any) {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.USERS, { params });
    return response.data;
  }

  async getUser(id: number) {
    const response = await this.api.get(`${API_CONFIG.ENDPOINTS.USERS}/${id}`);
    return response.data;
  }

  async createUser(data: any) {
    const response = await this.api.post(API_CONFIG.ENDPOINTS.USERS, data);
    return response.data;
  }

  async updateUser(id: number, data: any) {
    const response = await this.api.put(`${API_CONFIG.ENDPOINTS.USERS}/${id}`, data);
    return response.data;
  }

  async deleteUser(id: number) {
    const response = await this.api.delete(`${API_CONFIG.ENDPOINTS.USERS}/${id}`);
    return response.data;
  }

  // Audit Log methods
  async getAuditLogs(params?: any) {
    const response = await this.api.get(API_CONFIG.ENDPOINTS.AUDIT_LOGS, { params });
    return response.data;
  }

  async getAuditLog(id: number) {
    const response = await this.api.get(`${API_CONFIG.ENDPOINTS.AUDIT_LOGS}/${id}`);
    return response.data;
  }
}

export default new ApiService();
