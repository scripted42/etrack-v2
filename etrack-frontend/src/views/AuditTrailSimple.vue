<template>
  <div class="audit-trail-simple">
    <div class="mb-4">
      <h1>Audit Trail & Log Aktivitas</h1>
      <p class="text-grey-600">Pantau semua aktivitas sistem dan keamanan</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <p class="mt-4">Memuat data audit...</p>
    </div>

    <!-- Error State -->
    <v-alert v-if="error" type="error" class="mb-4">
      {{ error }}
    </v-alert>

    <!-- Statistics Cards -->
    <div v-if="!loading && statistics" class="mb-6">
      <v-row>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-icon color="primary" size="40" class="mr-3">mdi-history</v-icon>
              <div>
                <h3 class="text-h6">{{ statistics.total_logs || 0 }}</h3>
                <p class="text-caption text-grey-600">Total Aktivitas</p>
              </div>
            </div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-icon color="success" size="40" class="mr-3">mdi-account-multiple</v-icon>
              <div>
                <h3 class="text-h6">{{ statistics.unique_users || 0 }}</h3>
                <p class="text-caption text-grey-600">User Aktif</p>
              </div>
            </div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-icon color="warning" size="40" class="mr-3">mdi-shield-alert</v-icon>
              <div>
                <h3 class="text-h6">{{ statistics.unique_ips || 0 }}</h3>
                <p class="text-caption text-grey-600">IP Address</p>
              </div>
            </div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4">
            <div class="d-flex align-center">
              <v-icon color="info" size="40" class="mr-3">mdi-chart-line</v-icon>
              <div>
                <h3 class="text-h6">{{ Object.keys(statistics.logs_by_event_type || {}).length }}</h3>
                <p class="text-caption text-grey-600">Jenis Aktivitas</p>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Filters -->
    <v-card class="mb-4">
      <v-card-title>Filter & Pencarian</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="searchQuery"
              label="Cari aktivitas..."
              prepend-inner-icon="mdi-magnify"
              clearable
              variant="outlined"
              density="compact"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedAction"
              :items="actionOptions"
              label="Jenis Aktivitas"
              clearable
              variant="outlined"
              density="compact"
            ></v-select>
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedUser"
              :items="userOptions"
              label="User"
              clearable
              variant="outlined"
              density="compact"
            ></v-select>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Activity List -->
    <v-card>
      <v-card-title>
        Aktivitas Terbaru
        <v-spacer></v-spacer>
        <v-chip color="primary" variant="outlined">
          {{ filteredLogs.length }} dari {{ logs.length }} aktivitas
        </v-chip>
      </v-card-title>
      <v-card-text>
        <div v-if="filteredLogs.length === 0" class="text-center py-8">
          <v-icon size="64" color="grey">mdi-information-outline</v-icon>
          <p class="mt-4 text-grey-600">Tidak ada aktivitas yang ditemukan</p>
        </div>
        
        <v-timeline v-else density="compact" class="mt-4">
          <v-timeline-item
            v-for="log in displayedLogs"
            :key="log.id"
            :dot-color="getActionColor(log.action)"
            size="small"
          >
            <template v-slot:icon>
              <v-icon size="16">{{ getActionIcon(log.action) }}</v-icon>
            </template>
            
            <v-card class="mb-2" variant="outlined">
              <v-card-text class="py-2">
                <div class="d-flex justify-space-between align-center">
                  <div>
                    <h4 class="text-subtitle-2">{{ getActionTitle(log.action) }}</h4>
                    <p class="text-caption text-grey-600 mb-1">
                      {{ getActionDescription(log.action) }}
                    </p>
                    <div class="d-flex align-center text-caption text-grey-500">
                      <v-icon size="12" class="mr-1">mdi-account</v-icon>
                      {{ log.user?.name || `User ${log.user_id}` }}
                      <v-icon size="12" class="ml-3 mr-1">mdi-clock</v-icon>
                      {{ formatDate(log.created_at) }}
                      <v-icon size="12" class="ml-3 mr-1">mdi-map-marker</v-icon>
                      {{ log.ip_address }}
                    </div>
                  </div>
                  <v-btn
                    icon="mdi-chevron-down"
                    size="small"
                    variant="text"
                    @click="toggleDetails(log.id)"
                  ></v-btn>
                </div>
                
                <!-- Details Panel -->
                <v-expand-transition>
                  <div v-if="expandedLogs.includes(log.id)" class="mt-3">
                    <v-divider class="mb-3"></v-divider>
                    <div class="detail-content">
                      <div class="detail-item" v-for="(value, key) in log.details" :key="key">
                        <strong>{{ formatDetailKey(key) }}:</strong>
                        <span class="ml-2">{{ formatObjectValue(value) }}</span>
                      </div>
                    </div>
                  </div>
                </v-expand-transition>
              </v-card-text>
            </v-card>
          </v-timeline-item>
        </v-timeline>

        <!-- Load More Button -->
        <div v-if="displayedLogs.length < filteredLogs.length" class="text-center mt-4">
          <v-btn
            color="primary"
            variant="outlined"
            @click="loadMoreLogs"
            :loading="loading"
          >
            Tampilkan Lebih Banyak ({{ filteredLogs.length - displayedLogs.length }} tersisa)
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { fetchAuditLogs, fetchAuditStatistics } from '@/services/auditLog';

// Types
interface AuditLog {
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

interface AuditStatistics {
  total_logs: number;
  logs_by_event_type: Record<string, number>;
  top_actions: Record<string, number>;
  unique_users: number;
  unique_ips: number;
}

// State
const loading = ref(false);
const error = ref('');
const logs = ref<AuditLog[]>([]);
const statistics = ref<AuditStatistics | null>(null);
const searchQuery = ref('');
const selectedAction = ref('');
const selectedUser = ref<number | null>(null);
const expandedLogs = ref<number[]>([]);
const displayedLogsCount = ref(20);

// Computed
const filteredLogs = computed(() => {
  let filtered = logs.value;

  if (searchQuery.value) {
    const search = searchQuery.value.toLowerCase();
    filtered = filtered.filter(log =>
      log.action.toLowerCase().includes(search) ||
      log.user?.name?.toLowerCase().includes(search) ||
      log.ip_address.includes(search) ||
      JSON.stringify(log.details)?.toLowerCase().includes(search)
    );
  }

  if (selectedAction.value) {
    filtered = filtered.filter(log => log.action === selectedAction.value);
  }

  if (selectedUser.value) {
    filtered = filtered.filter(log => log.user_id === selectedUser.value);
  }

  return filtered;
});

const displayedLogs = computed(() => {
  return filteredLogs.value.slice(0, displayedLogsCount.value);
});

const actionOptions = computed(() => {
  const actions = [...new Set(logs.value.map(log => log.action))];
  return actions.map(action => ({
    title: getActionTitle(action),
    value: action
  }));
});

const userOptions = computed(() => {
  const users = [...new Set(logs.value.map(log => log.user_id))];
  return users.map(userId => {
    const user = logs.value.find(log => log.user_id === userId);
    return {
      title: user?.user?.name || `User ${userId}`,
      value: userId
    };
  });
});

// Methods
async function loadAuditData() {
  loading.value = true;
  error.value = '';
  
  try {
    const [logsResponse, statsResponse] = await Promise.all([
      fetchAuditLogs(),
      fetchAuditStatistics()
    ]);
    
    if (logsResponse.success) {
      if (logsResponse.data && logsResponse.data.logs) {
        logs.value = logsResponse.data.logs;
      } else {
        logs.value = logsResponse.data || [];
      }
    }
    
    if (statsResponse.success) {
      statistics.value = statsResponse.data;
    }
  } catch (err) {
    error.value = 'Gagal memuat data audit. Silakan coba lagi.';
    console.error('Error loading audit data:', err);
  } finally {
    loading.value = false;
  }
}

function toggleDetails(logId: number) {
  const index = expandedLogs.value.indexOf(logId);
  if (index > -1) {
    expandedLogs.value.splice(index, 1);
  } else {
    expandedLogs.value.push(logId);
  }
}

function loadMoreLogs() {
  displayedLogsCount.value += 20;
}

function getActionColor(action: string): string {
  const colors: Record<string, string> = {
    'LOGIN_SUCCESS': 'success',
    'LOGIN_FAILED': 'error',
    'LOGOUT': 'info',
    'create': 'primary',
    'update': 'warning',
    'delete': 'error',
    'SECURITY_ALERT': 'error',
    'DATA_EXPORT': 'info',
    'DATA_IMPORT': 'success'
  };
  return colors[action] || 'grey';
}

function getActionIcon(action: string): string {
  const icons: Record<string, string> = {
    'LOGIN_SUCCESS': 'mdi-login',
    'LOGIN_FAILED': 'mdi-login-variant',
    'LOGOUT': 'mdi-logout',
    'create': 'mdi-plus',
    'update': 'mdi-pencil',
    'delete': 'mdi-delete',
    'SECURITY_ALERT': 'mdi-shield-alert',
    'DATA_EXPORT': 'mdi-download',
    'DATA_IMPORT': 'mdi-upload'
  };
  return icons[action] || 'mdi-information';
}

function getActionTitle(action: string): string {
  const titles: Record<string, string> = {
    'LOGIN_SUCCESS': 'Login Berhasil',
    'LOGIN_FAILED': 'Login Gagal',
    'LOGOUT': 'Logout',
    'create': 'Data Dibuat',
    'update': 'Data Diperbarui',
    'delete': 'Data Dihapus',
    'SECURITY_ALERT': 'Peringatan Keamanan',
    'DATA_EXPORT': 'Data Diekspor',
    'DATA_IMPORT': 'Data Diimpor'
  };
  return titles[action] || action;
}

function getActionDescription(action: string): string {
  const descriptions: Record<string, string> = {
    'LOGIN_SUCCESS': 'User berhasil masuk ke sistem',
    'LOGIN_FAILED': 'User gagal masuk ke sistem',
    'LOGOUT': 'User keluar dari sistem',
    'create': 'Data baru telah dibuat',
    'update': 'Data telah diperbarui',
    'delete': 'Data telah dihapus',
    'SECURITY_ALERT': 'Terjadi peringatan keamanan',
    'DATA_EXPORT': 'Data telah diekspor',
    'DATA_IMPORT': 'Data telah diimpor'
  };
  return descriptions[action] || 'Aktivitas sistem';
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatDetailKey(key: string): string {
  const keyMap: Record<string, string> = {
    'username': 'Username',
    'email': 'Email',
    'role': 'Role',
    'nis': 'NIS',
    'nama': 'Nama',
    'kelas': 'Kelas',
    'status': 'Status',
    'reason': 'Alasan',
    'severity': 'Tingkat Keparahan',
    'ip': 'IP Address',
    'user_agent': 'User Agent'
  };
  return keyMap[key] || key.charAt(0).toUpperCase() + key.slice(1);
}

function formatObjectValue(value: any): string {
  if (typeof value === 'object' && value !== null) {
    return JSON.stringify(value, null, 2);
  }
  return String(value);
}

// Watchers
watch([searchQuery, selectedAction, selectedUser], () => {
  displayedLogsCount.value = 20;
  expandedLogs.value = [];
});

// Lifecycle
onMounted(() => {
  loadAuditData();
});
</script>

<style scoped>
.audit-trail-simple {
  padding: 20px;
}

.detail-content {
  max-height: 300px;
  overflow-y: auto;
  background-color: #f5f5f5;
  border-radius: 8px;
  padding: 12px;
}

.detail-item {
  margin-bottom: 8px;
  padding: 4px 0;
  border-bottom: 1px solid #e0e0e0;
}

.detail-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

@media (max-width: 768px) {
  .audit-trail-simple {
    padding: 10px;
  }
}
</style>

