<template>
  <div class="pa-4">
    <div class="mb-4">
      <h1 class="text-h4 font-weight-bold">Backup & Restore System</h1>
      <p class="text-body-2 text-grey">Kelola backup database MySQL secara otomatis dan manual</p>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat data backup...</div>
    </div>
    
    <!-- Backup Dashboard Content -->
    <div v-else>
      <!-- Quick Actions -->
      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="primary" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi-backup-restore</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.total_backups || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Total Backups</div>
                <div class="text-caption text-grey">File backup tersimpan</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi-check-circle</v-icon>
                  Sistem aktif
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="success" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi-harddisk</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.total_size_formatted || '0 B' }}</div>
                <div class="text-body-2 font-weight-medium">Total Size</div>
                <div class="text-caption text-grey">Ukuran semua backup</div>
                <div class="text-caption text-info mt-1">
                  <v-icon size="16" class="mr-1">mdi-information</v-icon>
                  {{ statistics?.compression_enabled ? 'Compressed' : 'Uncompressed' }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="info" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi-clock-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ getLastBackupAge() }}</div>
                <div class="text-body-2 font-weight-medium">Last Backup</div>
                <div class="text-caption text-grey">Backup terakhir</div>
                <div class="text-caption text-warning mt-1">
                  <v-icon size="16" class="mr-1">mdi-alert</v-icon>
                  {{ getLastBackupStatus() }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="warning" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi-settings</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ config?.max_backups || 30 }}</div>
                <div class="text-body-2 font-weight-medium">Max Backups</div>
                <div class="text-caption text-grey">Batas penyimpanan</div>
                <div class="text-caption text-primary mt-1">
                  <v-icon size="16" class="mr-1">mdi-cog</v-icon>
                  Auto cleanup
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Action Buttons -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi-play-circle</v-icon>
              Quick Actions
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <v-btn
                    color="primary"
                    variant="tonal"
                    block
                    @click="createBackupAction('manual')"
                    :loading="creatingBackup"
                    :disabled="creatingBackup"
                    prepend-icon="mdi-backup-restore"
                  >
                    Create Manual Backup
                  </v-btn>
                </v-col>
                <v-col cols="12" md="3">
                  <v-btn
                    color="success"
                    variant="tonal"
                    block
                    @click="testBackupSystemAction"
                    :loading="testingBackup"
                    :disabled="testingBackup"
                    prepend-icon="mdi-test-tube"
                  >
                    Test Backup System
                  </v-btn>
                </v-col>
                <v-col cols="12" md="3">
                  <v-btn
                    color="info"
                    variant="tonal"
                    block
                    @click="refreshData"
                    :loading="loading"
                    prepend-icon="mdi-refresh"
                  >
                    Refresh Data
                  </v-btn>
                </v-col>
                <v-col cols="12" md="3">
                  <v-btn
                    color="secondary"
                    variant="tonal"
                    block
                    @click="showConfigDialog = true"
                    prepend-icon="mdi-cog"
                  >
                    Configuration
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Backup List -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi-database</v-icon>
              Backup Files
              <v-spacer></v-spacer>
              <v-chip color="primary" variant="tonal" size="small">
                {{ backups.length }} files
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="backups.length > 0">
                <v-data-table
                  :headers="backupHeaders"
                  :items="backups"
                  :loading="loading"
                  class="elevation-1"
                >
                  <template v-slot:item.type="{ item }">
                    <v-chip
                      :color="getBackupTypeColor(item.type)"
                      size="small"
                      variant="tonal"
                    >
                      <v-icon start :icon="getBackupTypeIcon(item.type)"></v-icon>
                      {{ formatBackupType(item.type) }}
                    </v-chip>
                  </template>
                  
                  <template v-slot:item.created_at="{ item }">
                    <div>
                      <div class="text-body-2">{{ formatBackupDate(item.created_at) }}</div>
                      <div class="text-caption text-grey">{{ getBackupAge(item.created_at) }}</div>
                    </div>
                  </template>
                  
                  <template v-slot:item.size_formatted="{ item }">
                    <div>
                      <div class="text-body-2">{{ item.size_formatted }}</div>
                      <div class="text-caption text-grey">
                        <v-icon size="12" class="mr-1">mdi-package-variant</v-icon>
                        {{ item.compressed ? 'Compressed' : 'Raw' }}
                      </div>
                    </div>
                  </template>
                  
                  <template v-slot:item.status="{ item }">
                    <v-chip
                      :color="getBackupStatus(item.created_at).color"
                      size="small"
                      variant="tonal"
                    >
                      {{ getBackupStatus(item.created_at).status }}
                    </v-chip>
                  </template>
                  
                  <template v-slot:item.actions="{ item }">
                    <v-btn
                      size="small"
                      variant="text"
                      color="primary"
                      @click="downloadBackup(item.filename)"
                      prepend-icon="mdi-download"
                    >
                      Download
                    </v-btn>
                    <v-btn
                      size="small"
                      variant="text"
                      color="warning"
                      @click="confirmRestore(item)"
                      prepend-icon="mdi-restore"
                    >
                      Restore
                    </v-btn>
                    <v-btn
                      size="small"
                      variant="text"
                      color="error"
                      @click="confirmDelete(item)"
                      prepend-icon="mdi-delete"
                    >
                      Delete
                    </v-btn>
                  </template>
                </v-data-table>
              </div>
              <div v-else class="text-center text-grey py-8">
                <v-icon size="64" color="grey">mdi-database-off</v-icon>
                <div class="text-h6 mt-4">Tidak ada backup tersimpan</div>
                <div class="text-caption">Buat backup pertama untuk memulai</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Backup Statistics -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="success">mdi-chart-pie</v-icon>
              Backup by Type
            </v-card-title>
            <v-card-text>
              <div v-if="statistics?.backups_by_type">
                <div v-for="(count, type) in statistics.backups_by_type" :key="type" class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">{{ formatBackupType(type) }}</div>
                      <div class="text-caption text-grey">{{ getBackupTypeDescription(type) }}</div>
                    </div>
                    <v-chip :color="getBackupTypeColor(type)" size="small">
                      {{ count }} files
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(count / statistics.total_backups) * 100"
                    :color="getBackupTypeColor(type)"
                    height="8"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((count / statistics.total_backups) * 100).toFixed(1) }}% dari total backup
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi-chart-pie-outline</v-icon>
                <div class="text-h6 mt-2">Belum ada data backup</div>
                <div class="text-caption">Statistik akan muncul setelah ada backup</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="info">mdi-information</v-icon>
              System Information
            </v-card-title>
            <v-card-text>
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <div>
                    <div class="text-body-1 font-weight-bold">Compression</div>
                    <div class="text-caption text-grey">File compression status</div>
                  </div>
                  <v-chip :color="config?.compression_enabled ? 'success' : 'warning'" size="small">
                    {{ config?.compression_enabled ? 'Enabled' : 'Disabled' }}
                  </v-chip>
                </div>
                <v-progress-linear
                  :model-value="config?.compression_enabled ? 100 : 0"
                  :color="config?.compression_enabled ? 'success' : 'warning'"
                  height="6"
                  rounded
                />
              </div>
              
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <div>
                    <div class="text-body-1 font-weight-bold">Auto Backup</div>
                    <div class="text-caption text-grey">Automatic backup status</div>
                  </div>
                  <v-chip :color="config?.auto_backup_enabled ? 'success' : 'error'" size="small">
                    {{ config?.auto_backup_enabled ? 'Enabled' : 'Disabled' }}
                  </v-chip>
                </div>
                <v-progress-linear
                  :model-value="config?.auto_backup_enabled ? 100 : 0"
                  :color="config?.auto_backup_enabled ? 'success' : 'error'"
                  height="6"
                  rounded
                />
              </div>
              
              <v-alert type="info" variant="tonal" class="mt-4">
                <v-alert-title>Backup Schedule</v-alert-title>
                Automatic backups are scheduled to run {{ config?.backup_schedule || 'daily' }}.
                Maximum {{ config?.max_backups || 30 }} backup files will be kept.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Restore Confirmation Dialog -->
    <v-dialog v-model="showRestoreDialog" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Konfirmasi Restore</v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" class="mb-4">
            <v-alert-title>Peringatan!</v-alert-title>
            Restore akan mengganti semua data database dengan data dari backup. 
            Pastikan Anda sudah membuat backup terbaru sebelum melakukan restore.
          </v-alert>
          <div class="text-body-1">
            <strong>File:</strong> {{ selectedBackup?.filename }}<br>
            <strong>Type:</strong> {{ selectedBackup ? formatBackupType(selectedBackup.type) : '' }}<br>
            <strong>Created:</strong> {{ selectedBackup ? formatBackupDate(selectedBackup.created_at) : '' }}
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="showRestoreDialog = false">Cancel</v-btn>
          <v-btn color="warning" @click="restoreBackup" :loading="restoringBackup">Restore</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Konfirmasi Hapus</v-card-title>
        <v-card-text>
          <div class="text-body-1">
            Apakah Anda yakin ingin menghapus backup file ini?
          </div>
          <div class="text-body-2 mt-2">
            <strong>File:</strong> {{ selectedBackup?.filename }}<br>
            <strong>Size:</strong> {{ selectedBackup?.size_formatted }}<br>
            <strong>Created:</strong> {{ selectedBackup ? formatBackupDate(selectedBackup.created_at) : '' }}
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteBackup" :loading="deletingBackup">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Configuration Dialog -->
    <v-dialog v-model="showConfigDialog" max-width="600px">
      <v-card>
        <v-card-title class="text-h5">Backup Configuration</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            <v-alert-title>Information</v-alert-title>
            Konfigurasi backup saat ini. Untuk mengubah konfigurasi, edit file .env atau config/backup.php
          </v-alert>
          <div v-if="config">
            <v-row>
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-3">
                  <div class="text-body-2 font-weight-bold mb-2">Max Backups</div>
                  <div class="text-h6">{{ config.max_backups }}</div>
                  <div class="text-caption text-grey">Maximum backup files to keep</div>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-3">
                  <div class="text-body-2 font-weight-bold mb-2">Compression</div>
                  <div class="text-h6">{{ config.compression_enabled ? 'Enabled' : 'Disabled' }}</div>
                  <div class="text-caption text-grey">File compression status</div>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-3">
                  <div class="text-body-2 font-weight-bold mb-2">Auto Backup</div>
                  <div class="text-h6">{{ config.auto_backup_enabled ? 'Enabled' : 'Disabled' }}</div>
                  <div class="text-caption text-grey">Automatic backup status</div>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-3">
                  <div class="text-body-2 font-weight-bold mb-2">Schedule</div>
                  <div class="text-h6">{{ config.backup_schedule }}</div>
                  <div class="text-caption text-grey">Backup schedule</div>
                </v-card>
              </v-col>
            </v-row>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="showConfigDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Notification Popup Dialog -->
    <v-dialog
      v-model="notification.show"
      max-width="400"
      @click:outside="notification.show = false"
    >
      <v-card :color="getNotificationColor(notification.type)">
        <v-card-title class="d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon 
              :color="notification.type === 'success' ? 'green' : notification.type === 'error' ? 'red' : notification.type === 'warning' ? 'orange' : 'blue'"
              size="24"
              class="mr-3"
            >
              {{ getNotificationIcon(notification.type) }}
            </v-icon>
            <span class="text-h6 font-weight-bold">{{ notification.title }}</span>
          </div>
          <v-btn
            icon
            variant="text"
            size="small"
            @click="notification.show = false"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-card-text class="pt-2">
          <div class="text-body-1">{{ notification.message }}</div>
        </v-card-text>
        
        <v-card-actions class="justify-end">
          <v-btn
            color="white"
            variant="text"
            @click="notification.show = false"
          >
            <v-icon class="mr-2">mdi-check</v-icon>
            OK
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { 
  fetchBackups, 
  createBackup, 
  fetchBackupStatistics, 
  fetchBackupConfig,
  testBackupSystem,
  restoreBackup as restoreBackupAPI,
  deleteBackup as deleteBackupAPI,
  downloadBackup,
  formatBackupType,
  getBackupTypeColor,
  getBackupTypeIcon,
  formatBackupDate,
  getBackupAge,
  getBackupStatus,
  type BackupFile,
  type BackupStatistics,
  type BackupConfig
} from '@/services/backup';

// State
const loading = ref(true);
const creatingBackup = ref(false);
const testingBackup = ref(false);
const restoringBackup = ref(false);
const deletingBackup = ref(false);
const backups = ref<BackupFile[]>([]);
const statistics = ref<BackupStatistics | null>(null);
const config = ref<BackupConfig | null>(null);

// Dialog states
const showRestoreDialog = ref(false);
const showDeleteDialog = ref(false);
const showConfigDialog = ref(false);
const selectedBackup = ref<BackupFile | null>(null);

// Notification states
const notification = ref({
  show: false,
  type: 'success' as 'success' | 'error' | 'warning' | 'info',
  title: '',
  message: ''
});

// Table headers
const backupHeaders = [
  { title: 'Filename', key: 'filename', sortable: true },
  { title: 'Type', key: 'type', sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Size', key: 'size_formatted', sortable: true },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false }
];

// Methods
async function loadData() {
  loading.value = true;
  try {
    const [backupsResponse, statsResponse, configResponse] = await Promise.all([
      fetchBackups(),
      fetchBackupStatistics(),
      fetchBackupConfig()
    ]);
    
    if (backupsResponse.success) {
      backups.value = backupsResponse.data.backups;
      statistics.value = backupsResponse.data.statistics;
    }
    
    if (statsResponse.success) {
      statistics.value = statsResponse.data;
    }
    
    if (configResponse.success) {
      config.value = configResponse.data;
    }
  } catch (error) {
    console.error('Error loading backup data:', error);
  } finally {
    loading.value = false;
  }
}

async function refreshData() {
  await loadData();
}

async function createBackupAction(type: string) {
  console.log('=== CREATE BACKUP ACTION ===');
  console.log('Type:', type);
  creatingBackup.value = true;
  try {
    const response = await createBackup(type);
    console.log('Response:', response);
    if (response.success) {
      await loadData();
      console.log('Showing success notification');
      showNotification('success', 'Backup Berhasil!', `Backup ${type} berhasil dibuat.`);
    } else {
      console.log('Showing error notification');
      showNotification('error', 'Backup Gagal!', response.message || 'Terjadi kesalahan saat membuat backup.');
    }
  } catch (error: any) {
    console.error('Error creating backup:', error);
    console.log('Showing error notification for catch');
    showNotification('error', 'Backup Gagal!', error.response?.data?.message || error.message || 'Terjadi kesalahan saat membuat backup.');
  } finally {
    creatingBackup.value = false;
  }
}

async function testBackupSystemAction() {
  console.log('=== TEST BACKUP SYSTEM ACTION ===');
  testingBackup.value = true;
  try {
    const response = await testBackupSystem();
    console.log('Test response:', response);
    if (response.success) {
      console.log('Showing success notification for test');
      showNotification('success', 'Test Backup Berhasil!', 'Sistem backup berfungsi dengan baik.');
    } else {
      console.log('Showing error notification for test');
      showNotification('error', 'Test Backup Gagal!', response.message || 'Terjadi kesalahan saat test backup system.');
    }
  } catch (error: any) {
    console.error('Error testing backup system:', error);
    console.log('Showing error notification for test catch');
    showNotification('error', 'Test Backup Gagal!', error.response?.data?.message || error.message || 'Terjadi kesalahan saat test backup system.');
  } finally {
    testingBackup.value = false;
  }
}

function confirmRestore(backup: BackupFile) {
  selectedBackup.value = backup;
  showRestoreDialog.value = true;
}

async function restoreBackup() {
  if (!selectedBackup.value) return;
  
  restoringBackup.value = true;
  try {
    const response = await restoreBackupAPI(selectedBackup.value.filename);
    if (response.success) {
      showRestoreDialog.value = false;
      await loadData();
      // Show success notification
      showNotification('success', 'Database berhasil di-restore!', 'Data database telah berhasil dipulihkan dari backup.');
    } else {
      // Show error notification
      showNotification('error', 'Gagal restore database', response.message || 'Terjadi kesalahan saat restore database.');
    }
  } catch (error: any) {
    console.error('Error restoring backup:', error);
    // Show error notification
    showNotification('error', 'Gagal restore database', error.response?.data?.message || error.message || 'Terjadi kesalahan saat restore database.');
  } finally {
    restoringBackup.value = false;
  }
}

function confirmDelete(backup: BackupFile) {
  selectedBackup.value = backup;
  showDeleteDialog.value = true;
}

async function deleteBackup() {
  if (!selectedBackup.value) return;
  
  deletingBackup.value = true;
  try {
    const response = await deleteBackupAPI(selectedBackup.value.filename);
    if (response.success) {
      showDeleteDialog.value = false;
      await loadData();
    }
  } catch (error) {
    console.error('Error deleting backup:', error);
  } finally {
    deletingBackup.value = false;
  }
}

async function downloadBackupFile(filename: string) {
  try {
    const blob = await downloadBackup(filename);
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    console.error('Error downloading backup:', error);
  }
}

function getLastBackupAge(): string {
  if (!statistics.value?.newest_backup) return 'Never';
  return getBackupAge(statistics.value.newest_backup);
}

function getLastBackupStatus(): string {
  if (!statistics.value?.newest_backup) return 'No backup';
  return getBackupStatus(statistics.value.newest_backup).status;
}

function getBackupTypeDescription(type: string): string {
  const descriptions: Record<string, string> = {
    'manual': 'Backup dibuat secara manual',
    'auto': 'Backup otomatis sistem',
    'daily': 'Backup harian terjadwal',
    'weekly': 'Backup mingguan terjadwal',
    'monthly': 'Backup bulanan terjadwal'
  };
  return descriptions[type] || 'Backup sistem';
}

// Notification function
function getNotificationColor(type: string): string {
  switch (type) {
    case 'success': return 'green-lighten-4';
    case 'error': return 'red-lighten-4';
    case 'warning': return 'orange-lighten-4';
    case 'info': return 'blue-lighten-4';
    default: return 'grey-lighten-4';
  }
}

function getNotificationIcon(type: string): string {
  switch (type) {
    case 'success': return 'mdi-check-circle';
    case 'error': return 'mdi-alert-circle';
    case 'warning': return 'mdi-alert';
    case 'info': return 'mdi-information';
    default: return 'mdi-bell';
  }
}

function showNotification(type: 'success' | 'error' | 'warning' | 'info', title: string, message: string) {
  console.log('=== SHOW NOTIFICATION ===');
  console.log('Type:', type);
  console.log('Title:', title);
  console.log('Message:', message);
  
  notification.value = {
    show: true,
    type,
    title,
    message
  };
  
  console.log('Notification state:', notification.value);
  
  // Auto hide after 8 seconds for popup
  setTimeout(() => {
    notification.value.show = false;
  }, 8000);
}

// Lifecycle
onMounted(() => {
  loadData();
});
</script>

<style scoped>
.h-100 {
  height: 100%;
}
</style>
