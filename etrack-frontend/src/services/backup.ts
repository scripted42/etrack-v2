import api from '@/services/api';

export interface BackupFile {
  filename: string;
  type: string;
  created_at: string;
  size: number;
  size_formatted: string;
  compressed: boolean;
  filepath: string;
}

export interface BackupStatistics {
  total_backups: number;
  total_size: number;
  total_size_formatted: string;
  oldest_backup: string | null;
  newest_backup: string | null;
  backups_by_type: Record<string, number>;
  compression_enabled: boolean;
  max_backups: number;
}

export interface BackupConfig {
  max_backups: number;
  compression_enabled: boolean;
  backup_path: string;
  auto_backup_enabled: boolean;
  backup_schedule: string;
}

export interface BackupResponse {
  success: boolean;
  data: {
    backups: BackupFile[];
    statistics: BackupStatistics;
  };
}

export interface BackupCreateResponse {
  success: boolean;
  message: string;
  data: {
    filename: string;
    filepath: string;
    size: number;
    size_formatted: string;
    type: string;
    created_at: string;
    compressed: boolean;
  };
}

export interface BackupTestResponse {
  success: boolean;
  message: string;
  data: {
    message: string;
    tested_at: string;
  };
}

/**
 * Get list of available backups
 */
export async function fetchBackups(): Promise<BackupResponse> {
  const response = await api.get('/backups');
  return response.data;
}

/**
 * Create a new backup
 */
export async function createBackup(type: string = 'manual'): Promise<BackupCreateResponse> {
  const response = await api.post('/backups', { type });
  return response.data;
}

/**
 * Get backup statistics
 */
export async function fetchBackupStatistics(): Promise<{ success: boolean; data: BackupStatistics }> {
  const response = await api.get('/backups/statistics');
  return response.data;
}

/**
 * Get backup configuration
 */
export async function fetchBackupConfig(): Promise<{ success: boolean; data: BackupConfig }> {
  const response = await api.get('/backups/config');
  return response.data;
}

/**
 * Test backup system
 */
export async function testBackupSystem(): Promise<BackupTestResponse> {
  const response = await api.post('/backups/test');
  return response.data;
}

/**
 * Restore from backup
 */
export async function restoreBackup(filename: string): Promise<{ success: boolean; message: string; data: any }> {
  const response = await api.post(`/backups/${filename}/restore`);
  return response.data;
}

/**
 * Download backup file
 */
export async function downloadBackup(filename: string): Promise<Blob> {
  const response = await api.get(`/backups/${filename}/download`, {
    responseType: 'blob'
  });
  return response.data;
}

/**
 * Delete backup file
 */
export async function deleteBackup(filename: string): Promise<{ success: boolean; message: string; data: any }> {
  const response = await api.delete(`/backups/${filename}`);
  return response.data;
}

/**
 * Helper function to format backup type for display
 */
export function formatBackupType(type: string): string {
  const typeMap: Record<string, string> = {
    'manual': 'Manual',
    'auto': 'Automatic',
    'daily': 'Daily',
    'weekly': 'Weekly',
    'monthly': 'Monthly'
  };
  return typeMap[type] || type;
}

/**
 * Helper function to get backup type color
 */
export function getBackupTypeColor(type: string): string {
  const colorMap: Record<string, string> = {
    'manual': 'primary',
    'auto': 'success',
    'daily': 'info',
    'weekly': 'warning',
    'monthly': 'secondary'
  };
  return colorMap[type] || 'grey';
}

/**
 * Helper function to get backup type icon
 */
export function getBackupTypeIcon(type: string): string {
  const iconMap: Record<string, string> = {
    'manual': 'mdi:backup-restore',
    'auto': 'mdi:robot',
    'daily': 'mdi:calendar-today',
    'weekly': 'mdi:calendar-week',
    'monthly': 'mdi:calendar-month'
  };
  return iconMap[type] || 'mdi:database';
}

/**
 * Helper function to format file size
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Helper function to format date
 */
export function formatBackupDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

/**
 * Helper function to get backup age
 */
export function getBackupAge(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffMinutes = Math.floor(diffMs / (1000 * 60));

  if (diffDays > 0) {
    return `${diffDays} hari yang lalu`;
  } else if (diffHours > 0) {
    return `${diffHours} jam yang lalu`;
  } else if (diffMinutes > 0) {
    return `${diffMinutes} menit yang lalu`;
  } else {
    return 'Baru saja';
  }
}

/**
 * Helper function to get backup status
 */
export function getBackupStatus(dateString: string): { status: string; color: string } {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

  if (diffHours < 24) {
    return { status: 'Fresh', color: 'success' };
  } else if (diffHours < 72) {
    return { status: 'Recent', color: 'warning' };
  } else {
    return { status: 'Old', color: 'error' };
  }
}
