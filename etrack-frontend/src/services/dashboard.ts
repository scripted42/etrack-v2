import api from '@/services/api';

export interface DashboardStats {
  kpi: {
    total_students: number;
    total_employees: number;
    utilization_rate: number;
    max_capacity: number;
    teacher_student_ratio: {
      ratio: number;
      students: number;
      teachers: number;
      status: string;
    };
  };
  distribution: {
    students_by_class: Record<string, number>;
    students_by_level: Record<string, number>;
    employee_analysis: {
      total: number;
      active: number;
      teachers: number;
      staff: number;
      staff_breakdown: {
        tata_usaha: number;
        humas: number;
        security: number;
        lainnya: number;
      };
      active_percentage: number;
    };
  };
  quality: {
    data_quality: {
      completeness_rate: number;
      complete_records: number;
      incomplete_records: number;
      status: string;
    };
    performance_indicators: {
      data_completeness: number;
      activity_rate: number;
      utilization_rate: number;
      system_health: number;
    };
  };
  trends: {
    growth_trend: {
      months: string[];
      student_counts: number[];
      trend_direction: string;
    };
    monthly_stats: {
      months: string[];
      students: number[];
      employees: number[];
    };
  };
  system: {
    activity: {
      activity_24h: number;
      activity_7d: number;
      successful_logins: number;
      failed_logins: number;
      security_score: number;
    };
    alerts: Array<{
      type: string;
      title: string;
      message: string;
      priority: string;
    }>;
  };
  // Legacy data
  overview: {
    total_students: number;
    total_employees: number;
    total_users: number;
    active_users: number;
  };
}

export interface SystemHealth {
  database: string;
  storage: {
    total_space: string;
    used_space: string;
    free_space: string;
    usage_percentage: number;
  };
  errors: number;
  last_updated: string;
}

export async function fetchDashboardStats(): Promise<{ success: boolean; data: DashboardStats }> {
  const res = await api.get('/dashboard');
  return res.data;
}

export async function fetchSystemHealth(): Promise<{ success: boolean; data: SystemHealth }> {
  const res = await api.get('/dashboard/health');
  return res.data;
}
