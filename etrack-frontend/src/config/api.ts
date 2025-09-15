// API Configuration for E-Track
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const API_CONFIG = {
  BASE_URL: API_BASE_URL,
  TIMEOUT: 10000,
  ENDPOINTS: {
    AUTH: {
      LOGIN: '/login',
      LOGOUT: '/logout',
      ME: '/me',
      CHANGE_PASSWORD: '/change-password',
    },
    DASHBOARD: {
      STATISTICS: '/dashboard/statistics',
      CHART_DATA: '/dashboard/chart-data',
    },
    STUDENTS: '/students',
    EMPLOYEES: '/employees',
    USERS: '/users',
    AUDIT_LOGS: '/audit-logs',
  }
};

// School Information
export const SCHOOL_INFO = {
  NAME: 'SMP Negeri 14 Surabaya',
  ADDRESS: 'Jl. Raya Darmo Permai III No. 1, Surabaya',
  PHONE: '(031) 5678901',
  EMAIL: 'info@smpn14sby.sch.id',
  WEBSITE: 'www.smpn14sby.sch.id',
  LOGO: '/logo-smpn14.png'
};
