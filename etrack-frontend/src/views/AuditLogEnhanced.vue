<template>
  <div class="pa-4">
    <!-- Header dengan Breadcrumb -->
    <div class="d-flex align-center mb-6">
      <v-icon class="mr-3" size="large" color="primary">mdi:history</v-icon>
      <div>
        <h1 class="text-h4 font-weight-bold">Audit Trail Dashboard</h1>
        <p class="text-body-2 text-grey">Monitor dan analisis aktivitas sistem secara real-time</p>
      </div>
    </div>
    
    <!-- Loading State dengan Skeleton -->
    <div v-if="loading" class="mb-6">
      <v-skeleton-loader type="card" class="mb-4"></v-skeleton-loader>
      <v-row>
        <v-col cols="12" md="3" v-for="i in 4" :key="i">
          <v-skeleton-loader type="card"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>
    
    <!-- Error State -->
    <v-alert v-if="error" type="error" class="mb-4" closable @click:close="error = ''">
      <v-alert-title>Error Loading Data</v-alert-title>
      {{ error }}
      <template v-slot:append>
        <v-btn @click="loadData" color="error" variant="outlined" size="small">
          <v-icon class="mr-1">mdi:refresh</v-icon>
          Retry
        </v-btn>
      </template>
    </v-alert>
    
    <!-- Dashboard Content -->
    <div v-else>
      <!-- Quick Stats dengan Animasi -->
      <v-row class="mb-6">
        <v-col cols="12" md="3" v-for="(stat, key) in quickStats" :key="key">
          <v-card 
            :color="stat.color" 
            variant="tonal" 
            class="stat-card"
            :class="{ 'animate-pulse': stat.loading }"
          >
            <v-card-text class="d-flex align-center">
              <v-avatar :color="stat.color" size="48" class="mr-4">
                <v-icon :color="stat.iconColor" size="24">{{ stat.icon }}</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stat.value }}</div>
                <div class="text-caption">{{ stat.label }}</div>
                <div v-if="stat.trend" class="d-flex align-center mt-1">
                  <v-icon 
                    :color="stat.trend > 0 ? 'success' : 'error'" 
                    size="16"
                    class="mr-1"
                  >
                    {{ stat.trend > 0 ? 'mdi:trending-up' : 'mdi:trending-down' }}
                  </v-icon>
                  <span class="text-caption">{{ Math.abs(stat.trend) }}%</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filter Section dengan Advanced Options -->
      <v-card class="mb-6">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi:filter</v-icon>
          Filter & Search
          <v-spacer></v-spacer>
          <v-btn 
            @click="showAdvancedFilters = !showAdvancedFilters" 
            variant="text" 
            size="small"
          >
            {{ showAdvancedFilters ? 'Hide' : 'Show' }} Advanced
          </v-btn>
        </v-card-title>
        
        <v-card-text>
          <!-- Quick Filters -->
          <v-row class="mb-4">
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                label="Search actions or users"
                prepend-inner-icon="mdi:magnify"
                clearable
                variant="outlined"
                density="compact"
                @input="debouncedSearch"
              />
            </v-col>
            
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.event_type"
                label="Event Type"
                :items="eventTypeOptions"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              />
            </v-col>
            
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.action"
                label="Action"
                :items="actionOptions"
                clearable
                variant="outlined"
                density="compact"
                @update:model-value="applyFilters"
              />
            </v-col>
            
            <v-col cols="12" md="2">
              <v-btn @click="applyFilters" color="primary" variant="elevated" block size="default">
                <v-icon class="mr-1">mdi:filter</v-icon>
                Apply
              </v-btn>
            </v-col>
          </v-row>

          <!-- Advanced Filters -->
          <v-expand-transition>
            <div v-show="showAdvancedFilters">
              <v-divider class="mb-4"></v-divider>
              <v-row>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.date_from"
                    label="Date From"
                    type="date"
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.date_to"
                    label="Date To"
                    type="date"
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.ip_address"
                    label="IP Address"
                    placeholder="192.168.1.1"
                    clearable
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.user_id"
                    label="User ID"
                    type="number"
                    clearable
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
              </v-row>
              
              <v-row class="mt-2">
                <v-col cols="12" class="d-flex gap-2">
                  <v-btn @click="clearFilters" color="secondary" variant="outlined">
                    <v-icon class="mr-1">mdi:filter-off</v-icon>
                    Clear All
                  </v-btn>
                  
                  <v-btn @click="exportLogs" color="success" variant="outlined" :loading="exporting">
                    <v-icon class="mr-1">mdi:download</v-icon>
                    Export
                  </v-btn>
                  
                  <v-btn @click="refreshData" color="primary" variant="outlined" :loading="loading">
                    <v-icon class="mr-1">mdi:refresh</v-icon>
                    Refresh
                  </v-btn>
                </v-col>
              </v-row>
            </div>
          </v-expand-transition>
        </v-card-text>
      </v-card>

      <!-- Charts Section -->
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi:chart-pie</v-icon>
              Activity Distribution
            </v-card-title>
            <v-card-text>
              <div v-if="Object.keys(statistics.logs_by_event_type || {}).length > 0">
                <div v-for="(count, eventType) in statistics.logs_by_event_type" :key="eventType" class="mb-3">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <div class="d-flex align-center">
                      <v-icon :color="getEventTypeColor(eventType)" class="mr-2" size="20">
                        {{ getEventTypeIcon(eventType) }}
                      </v-icon>
                      <span class="text-body-1">{{ formatEventType(eventType) }}</span>
                    </div>
                    <v-chip :color="getEventTypeColor(eventType)" size="small">{{ count }}</v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(count / statistics.total_logs) * 100"
                    :color="getEventTypeColor(eventType)"
                    height="6"
                    rounded
                  />
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:chart-pie-outline</v-icon>
                <div class="text-h6 mt-2">No Data Available</div>
                <div class="text-caption">Activity data will appear here</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="success">mdi:chart-bar</v-icon>
              Top Actions
            </v-card-title>
            <v-card-text>
              <div v-if="Object.keys(statistics.top_actions || {}).length > 0">
                <div v-for="(count, action) in statistics.top_actions" :key="action" class="mb-3">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-body-1">{{ formatAction(action) }}</span>
                    <v-chip :color="getActionColor(action)" size="small">{{ count }}</v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(count / Math.max(...Object.values(statistics.top_actions))) * 100"
                    :color="getActionColor(action)"
                    height="6"
                    rounded
                  />
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:chart-bar</v-icon>
                <div class="text-h6 mt-2">No Data Available</div>
                <div class="text-caption">Action data will appear here</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Recent Activity dengan Virtual Scrolling -->
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2" color="info">mdi:clock-outline</v-icon>
          Recent Activity
          <v-spacer></v-spacer>
          <v-chip color="primary" variant="tonal">
            {{ logs.length }} logs
          </v-chip>
        </v-card-title>
        
        <v-card-text>
          <div v-if="logs.length > 0">
            <v-virtual-scroll
              :items="logs"
              height="400"
              item-height="80"
            >
              <template v-slot:default="{ item }">
                <v-list-item class="mb-2" :key="item.id">
                  <template v-slot:prepend>
                    <v-avatar :color="getActionColor(item.action)" size="40">
                      <v-icon :color="'white'" size="20">
                        {{ getActionIcon(item.action) }}
                      </v-icon>
                    </v-avatar>
                  </template>
                  
                  <v-list-item-title class="font-weight-bold">
                    {{ formatAction(item.action) }}
                  </v-list-item-title>
                  
                  <v-list-item-subtitle>
                    <div class="d-flex align-center">
                      <v-icon size="16" class="mr-1">mdi:account</v-icon>
                      {{ item.user?.name || 'System' }}
                      <v-divider vertical class="mx-2"></v-divider>
                      <v-icon size="16" class="mr-1">mdi:clock</v-icon>
                      {{ formatDate(item.created_at) }}
                      <v-divider vertical class="mx-2"></v-divider>
                      <v-icon size="16" class="mr-1">mdi:map-marker</v-icon>
                      {{ item.ip_address || 'N/A' }}
                    </div>
                  </v-list-item-subtitle>
                  
                  <template v-slot:append>
                    <v-btn 
                      @click="viewLogDetail(item)" 
                      color="primary" 
                      variant="outlined" 
                      size="small"
                    >
                      Detail
                    </v-btn>
                  </template>
                </v-list-item>
                <v-divider></v-divider>
              </template>
            </v-virtual-scroll>
          </div>
          <div v-else class="text-center text-grey py-8">
            <v-icon size="64" color="grey">mdi:history</v-icon>
            <div class="text-h6 mt-2">No Activity Logs</div>
            <div class="text-caption">Activity logs will appear here</div>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Log Detail Dialog dengan Enhanced UI -->
    <v-dialog v-model="showLogDialog" max-width="900" scrollable>
      <v-card v-if="selectedLog">
        <v-card-title class="d-flex align-center">
          <v-avatar :color="getActionColor(selectedLog.action)" size="40" class="mr-3">
            <v-icon :color="'white'" size="20">
              {{ getActionIcon(selectedLog.action) }}
            </v-icon>
          </v-avatar>
          <div>
            <div class="text-h6">{{ formatAction(selectedLog.action) }}</div>
            <div class="text-caption">{{ formatDate(selectedLog.created_at) }}</div>
          </div>
          <v-spacer></v-spacer>
          <v-btn @click="showLogDialog = false" icon="mdi:close" variant="outlined" size="small" />
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-card variant="outlined">
                <v-card-title class="text-h6">Basic Information</v-card-title>
                <v-card-text>
                  <div class="mb-3">
                    <strong>Action:</strong>
                    <v-chip :color="getActionColor(selectedLog.action)" size="small" class="ml-2">
                      {{ selectedLog.action }}
                    </v-chip>
                  </div>
                  
                  <div class="mb-3">
                    <strong>User:</strong>
                    <div v-if="selectedLog.user" class="mt-1">
                      <div class="d-flex align-center">
                        <v-avatar size="32" class="mr-2">
                          <v-icon>mdi:account</v-icon>
                        </v-avatar>
                        <div>
                          <div class="font-weight-bold">{{ selectedLog.user.name }}</div>
                          <div class="text-caption">{{ selectedLog.user.username }} • {{ selectedLog.user.email }}</div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-grey mt-1">
                      <v-icon class="mr-1">mdi:robot</v-icon>
                      System Generated
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <strong>IP Address:</strong>
                    <div class="mt-1">
                      <v-chip color="info" size="small">
                        <v-icon class="mr-1">mdi:map-marker</v-icon>
                        {{ selectedLog.ip_address || 'N/A' }}
                      </v-chip>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <strong>Timestamp:</strong>
                    <div class="mt-1">
                      <v-chip color="primary" size="small">
                        <v-icon class="mr-1">mdi:clock</v-icon>
                        {{ formatDate(selectedLog.created_at) }}
                      </v-chip>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            
            <v-col cols="12" md="6">
              <v-card variant="outlined">
                <v-card-title class="text-h6">Detailed Information</v-card-title>
                <v-card-text>
                  <v-expansion-panels variant="accordion">
                    <v-expansion-panel>
                      <v-expansion-panel-title>
                        <v-icon class="mr-2">mdi:information</v-icon>
                        Raw Details
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <pre class="text-caption bg-grey-lighten-5 pa-3 rounded">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                    
                    <v-expansion-panel v-if="selectedLog.details">
                      <v-expansion-panel-title>
                        <v-icon class="mr-2">mdi:format-list-bulleted</v-icon>
                        Formatted Details
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <div class="text-body-2">{{ formatAuditLogDetails(selectedLog.details) }}</div>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showLogDialog = false" color="primary" variant="elevated" size="default">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar untuk Notifications -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
    >
      <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn @click="snackbar.show = false" variant="outlined" size="small">
          <v-icon>mdi:close</v-icon>
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { fetchAuditLogs, fetchAuditStatistics, exportAuditLogs, formatAuditLogDetails } from '@/services/auditLog';

// State
const loading = ref(false);
const exporting = ref(false);
const error = ref('');
const logs = ref([]);
const statistics = ref({
  total_logs: 0,
  logs_by_event_type: {},
  top_actions: {},
  unique_users: 0,
  unique_ips: 0
});

// Filters
const showAdvancedFilters = ref(false);
const filters = ref({
  search: '',
  action: '',
  event_type: '',
  date_from: '',
  date_to: '',
  ip_address: '',
  user_id: '',
  limit: 50,
  offset: 0
});

// Dialog
const showLogDialog = ref(false);
const selectedLog = ref(null);

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
  icon: 'mdi:check',
  timeout: 3000
});

// Computed
const quickStats = computed(() => [
  {
    label: 'Total Logs',
    value: statistics.value.total_logs || 0,
    icon: 'mdi:history',
    color: 'primary',
    iconColor: 'white',
    trend: 12
  },
  {
    label: 'Active Users',
    value: statistics.value.unique_users || 0,
    icon: 'mdi:account-group',
    color: 'success',
    iconColor: 'white',
    trend: 5
  },
  {
    label: 'Unique IPs',
    value: statistics.value.unique_ips || 0,
    icon: 'mdi:map-marker',
    color: 'info',
    iconColor: 'white',
    trend: -2
  },
  {
    label: 'Event Types',
    value: Object.keys(statistics.value.logs_by_event_type || {}).length,
    icon: 'mdi:tag-multiple',
    color: 'warning',
    iconColor: 'white',
    trend: 0
  }
]);

const eventTypeOptions = computed(() => [
  { title: 'Authentication', value: 'authentication' },
  { title: 'CRUD Operation', value: 'crud_operation' },
  { title: 'Data Transfer', value: 'data_transfer' },
  { title: 'System Event', value: 'system_event' },
  { title: 'Security Event', value: 'security_event' }
]);

const actionOptions = computed(() => {
  const actions = Object.keys(statistics.value.top_actions || {});
  return actions.map(action => ({
    title: formatAction(action),
    value: action
  }));
});

// Methods
async function loadData() {
  loading.value = true;
  error.value = '';
  
  try {
    // Load logs
    const logsResponse = await fetchAuditLogs(filters.value);
    if (logsResponse.success) {
      logs.value = logsResponse.data.logs;
    }
    
    // Load statistics
    const statsResponse = await fetchAuditStatistics();
    if (statsResponse.success) {
      statistics.value = statsResponse.data;
    }
    
    showSnackbar('Data loaded successfully', 'success', 'mdi:check');
  } catch (err) {
    error.value = err.message || 'Error loading data';
    showSnackbar('Error loading data', 'error', 'mdi:alert');
    console.error('Error loading audit data:', err);
  } finally {
    loading.value = false;
  }
}

async function refreshData() {
  await loadData();
}

async function applyFilters() {
  filters.value.offset = 0;
  await loadData();
}

function clearFilters() {
  filters.value = {
    search: '',
    action: '',
    event_type: '',
    date_from: '',
    date_to: '',
    ip_address: '',
    user_id: '',
    limit: 50,
    offset: 0
  };
  applyFilters();
}

async function exportLogs() {
  exporting.value = true;
  try {
    const response = await exportAuditLogs(filters.value);
    if (response.success) {
      // Create and download file
      const dataStr = JSON.stringify(response.data, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      const url = URL.createObjectURL(dataBlob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `audit-logs-${new Date().toISOString().split('T')[0]}.json`;
      link.click();
      URL.revokeObjectURL(url);
      
      showSnackbar('Export completed successfully', 'success', 'mdi:download');
    }
  } catch (error) {
    showSnackbar('Export failed', 'error', 'mdi:alert');
    console.error('Error exporting logs:', error);
  } finally {
    exporting.value = false;
  }
}

function viewLogDetail(log) {
  selectedLog.value = log;
  showLogDialog.value = true;
}

function showSnackbar(message, color = 'success', icon = 'mdi:check') {
  snackbar.value = {
    show: true,
    message,
    color,
    icon,
    timeout: 3000
  };
}

// Debounced search
let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
}

// Helper functions
function getActionColor(action) {
  if (action.includes('CREATE') || action.includes('Create')) return 'success';
  if (action.includes('UPDATE') || action.includes('Update')) return 'info';
  if (action.includes('DELETE') || action.includes('Delete')) return 'error';
  if (action.includes('LOGIN') || action.includes('Login')) return 'primary';
  if (action.includes('FAILED') || action.includes('Failed')) return 'error';
  return 'grey';
}

function getActionIcon(action) {
  if (action.includes('CREATE') || action.includes('Create')) return 'mdi:plus';
  if (action.includes('UPDATE') || action.includes('Update')) return 'mdi:pencil';
  if (action.includes('DELETE') || action.includes('Delete')) return 'mdi:delete';
  if (action.includes('LOGIN') || action.includes('Login')) return 'mdi:login';
  if (action.includes('FAILED') || action.includes('Failed')) return 'mdi:alert';
  return 'mdi:information';
}

function getEventTypeColor(eventType) {
  switch (eventType) {
    case 'authentication': return 'primary';
    case 'crud_operation': return 'success';
    case 'data_transfer': return 'info';
    case 'system_event': return 'warning';
    case 'security_event': return 'error';
    default: return 'grey';
  }
}

function getEventTypeIcon(eventType) {
  switch (eventType) {
    case 'authentication': return 'mdi:account-key';
    case 'crud_operation': return 'mdi:database-edit';
    case 'data_transfer': return 'mdi:file-export';
    case 'system_event': return 'mdi:cog';
    case 'security_event': return 'mdi:shield-alert';
    default: return 'mdi:information';
  }
}

function formatAction(action) {
  return action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatEventType(eventType) {
  return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Lifecycle
onMounted(() => {
  loadData();
});
</script>

<style scoped>
.stat-card {
  transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.animate-pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.gap-2 {
  gap: 8px;
}
</style>
