import api from '@/services/api';

export interface AuditLog {
  id: number;
  user_id: number;
  action: string;
  details: Record<string, any>;
  ip_address: string;
  created_at: string;
  user?: {
    id: number;
    name: string;
    username: string;
    email: string;
  };
}

export interface AuditLogFilters {
  user_id?: number;
  action?: string;
  event_type?: string;
  date_from?: string;
  date_to?: string;
  ip_address?: string;
  limit?: number;
  offset?: number;
}

export interface AuditLogResponse {
  success: boolean;
  data: {
    logs: AuditLog[];
    statistics: AuditStatistics;
    pagination: {
      limit: number;
      offset: number;
      has_more: boolean;
    };
  };
}

export interface AuditStatistics {
  total_logs: number;
  logs_by_event_type: Record<string, number>;
  top_actions: Record<string, number>;
  unique_users: number;
  unique_ips: number;
}

export interface AuditLogDetailResponse {
  success: boolean;
  data: AuditLog;
}

export interface AuditStatisticsResponse {
  success: boolean;
  data: AuditStatistics;
}

export interface AuditExportResponse {
  success: boolean;
  data: {
    logs: AuditLog[];
    export_info: {
      exported_at: string;
      total_records: number;
      filters: Record<string, any>;
    };
  };
}

/**
 * Get audit logs with filters
 */
export async function fetchAuditLogs(filters: AuditLogFilters = {}): Promise<AuditLogResponse> {
  const params = new URLSearchParams();
  
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.append(key, value.toString());
    }
  });

  const response = await api.get(`/audit-logs?${params.toString()}`);
  return response.data;
}

/**
 * Get specific audit log by ID
 */
export async function fetchAuditLog(id: number): Promise<AuditLogDetailResponse> {
  const response = await api.get(`/audit-logs/${id}`);
  return response.data;
}

/**
 * Get audit statistics
 */
export async function fetchAuditStatistics(filters: Partial<AuditLogFilters> = {}): Promise<AuditStatisticsResponse> {
  const params = new URLSearchParams();
  
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.append(key, value.toString());
    }
  });

  const response = await api.get(`/audit-logs/statistics/overview?${params.toString()}`);
  return response.data;
}

/**
 * Export audit logs
 */
export async function exportAuditLogs(filters: AuditLogFilters = {}): Promise<AuditExportResponse> {
  const response = await api.post('/audit-logs/export', filters);
  return response.data;
}

/**
 * Helper function to format audit log details
 */
export function formatAuditLogDetails(details: Record<string, any>): string {
  if (!details || typeof details !== 'object') {
    return 'No details available';
  }

  const formattedDetails: string[] = [];

  // Format common fields
  if (details.event_type) {
    formattedDetails.push(`Event: ${details.event_type}`);
  }

  if (details.model) {
    formattedDetails.push(`Model: ${details.model}`);
  }

  if (details.operation) {
    formattedDetails.push(`Operation: ${details.operation}`);
  }

  if (details.username) {
    formattedDetails.push(`Username: ${details.username}`);
  }

  if (details.role) {
    formattedDetails.push(`Role: ${details.role}`);
  }

  if (details.nis) {
    formattedDetails.push(`NIS: ${details.nis}`);
  }

  if (details.nama) {
    formattedDetails.push(`Nama: ${details.nama}`);
  }

  if (details.kelas) {
    formattedDetails.push(`Kelas: ${details.kelas}`);
  }

  if (details.status) {
    formattedDetails.push(`Status: ${details.status}`);
  }

  if (details.reason) {
    formattedDetails.push(`Reason: ${details.reason}`);
  }

  if (details.severity) {
    formattedDetails.push(`Severity: ${details.severity}`);
  }

  if (details.record_count) {
    formattedDetails.push(`Records: ${details.record_count}`);
  }

  if (details.data_type) {
    formattedDetails.push(`Data Type: ${details.data_type}`);
  }

  if (details.url) {
    formattedDetails.push(`URL: ${details.url}`);
  }

  if (details.method) {
    formattedDetails.push(`Method: ${details.method}`);
  }

  if (details.status_code) {
    formattedDetails.push(`Status Code: ${details.status_code}`);
  }

  if (details.response_size) {
    formattedDetails.push(`Response Size: ${details.response_size} bytes`);
  }

  if (details.user_agent) {
    formattedDetails.push(`User Agent: ${details.user_agent}`);
  }

  if (details.timestamp) {
    formattedDetails.push(`Timestamp: ${new Date(details.timestamp).toLocaleString()}`);
  }

  return formattedDetails.join(' | ');
}

/**
 * Helper function to get event type color
 */
export function getEventTypeColor(eventType: string): string {
  switch (eventType) {
    case 'authentication':
      return 'primary';
    case 'crud_operation':
      return 'success';
    case 'data_transfer':
      return 'info';
    case 'system_event':
      return 'warning';
    case 'security_event':
      return 'error';
    default:
      return 'grey';
  }
}

/**
 * Helper function to get event type icon
 */
export function getEventTypeIcon(eventType: string): string {
  switch (eventType) {
    case 'authentication':
      return 'mdi:account-key';
    case 'crud_operation':
      return 'mdi:database-edit';
    case 'data_transfer':
      return 'mdi:file-export';
    case 'system_event':
      return 'mdi:cog';
    case 'security_event':
      return 'mdi:shield-alert';
    default:
      return 'mdi:information';
  }
}

/**
 * Helper function to get action color
 */
export function getActionColor(action: string): string {
  if (action.includes('CREATE') || action.includes('Create')) {
    return 'success';
  } else if (action.includes('UPDATE') || action.includes('Update')) {
    return 'info';
  } else if (action.includes('DELETE') || action.includes('Delete')) {
    return 'error';
  } else if (action.includes('LOGIN') || action.includes('Login')) {
    return 'primary';
  } else if (action.includes('LOGOUT') || action.includes('Logout')) {
    return 'warning';
  } else if (action.includes('FAILED') || action.includes('Failed')) {
    return 'error';
  } else {
    return 'grey';
  }
}
