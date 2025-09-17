<template>
  <div>
    <div class="mb-4">
      <h1>Audit Trail Dashboard</h1>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat Audit Logs...</div>
    </div>
    
    <!-- Dashboard Content -->
    <div v-else>
      <!-- Statistics Cards -->
      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal">
            <v-card-title class="text-h6">Total Logs</v-card-title>
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ statistics.total_logs || 0 }}</div>
              <div class="text-caption">Total aktivitas</div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal">
            <v-card-title class="text-h6">Unique Users</v-card-title>
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ statistics.unique_users || 0 }}</div>
              <div class="text-caption">User aktif</div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal">
            <v-card-title class="text-h6">Unique IPs</v-card-title>
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ statistics.unique_ips || 0 }}</div>
              <div class="text-caption">Alamat IP</div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal">
            <v-card-title class="text-h6">Event Types</v-card-title>
            <v-card-text>
              <div class="text-h3 font-weight-bold">{{ Object.keys(statistics.logs_by_event_type || {}).length }}</div>
              <div class="text-caption">Jenis aktivitas</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title>Filter Audit Logs</v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.action"
                    label="Action"
                    placeholder="Cari action..."
                    clearable
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.event_type"
                    label="Event Type"
                    :items="eventTypes"
                    clearable
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                
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
              </v-row>
              
              <v-row>
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
                
                <v-col cols="12" md="6" class="d-flex align-center">
                  <v-btn @click="applyFilters" color="primary" class="mr-2">
                    <v-icon class="mr-2">mdi:filter</v-icon>
                    Apply Filters
                  </v-btn>
                  
                  <v-btn @click="clearFilters" color="secondary" class="mr-2">
                    <v-icon class="mr-2">mdi:filter-off</v-icon>
                    Clear
                  </v-btn>
                  
                  <v-btn @click="exportLogs" color="success" :loading="exporting">
                    <v-icon class="mr-2">mdi:download</v-icon>
                    Export
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Event Type Distribution -->
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Event Type Distribution</v-card-title>
            <v-card-text>
              <div v-if="Object.keys(statistics.logs_by_event_type || {}).length > 0">
                <div v-for="(count, eventType) in statistics.logs_by_event_type" :key="eventType" class="d-flex justify-space-between align-center mb-2">
                  <div class="d-flex align-center">
                    <v-icon :color="getEventTypeColor(eventType)" class="mr-2">{{ getEventTypeIcon(eventType) }}</v-icon>
                    <span class="text-body-1">{{ eventType.replace('_', ' ').toUpperCase() }}</span>
                  </div>
                  <v-chip :color="getEventTypeColor(eventType)" size="small">{{ count }}</v-chip>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                No data available
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Top Actions</v-card-title>
            <v-card-text>
              <div v-if="Object.keys(statistics.top_actions || {}).length > 0">
                <div v-for="(count, action) in statistics.top_actions" :key="action" class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">{{ action }}</span>
                  <v-chip :color="getActionColor(action)" size="small">{{ count }}</v-chip>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                No data available
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Audit Logs Table -->
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Audit Logs</span>
              <v-btn @click="loadLogs" :loading="loading" color="primary" size="small">
                <v-icon class="mr-2">mdi:refresh</v-icon>
                Refresh
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="headers"
                :items="logs"
                :loading="loading"
                :items-per-page="pagination.limit"
                :page="currentPage"
                @update:page="onPageChange"
                class="elevation-1"
              >
                <template v-slot:item.action="{ item }">
                  <v-chip :color="getActionColor(item.action)" size="small">
                    {{ item.action }}
                  </v-chip>
                </template>
                
                <template v-slot:item.details="{ item }">
                  <div class="text-caption">
                    {{ formatAuditLogDetails(item.details) }}
                  </div>
                </template>
                
                <template v-slot:item.user="{ item }">
                  <div v-if="item.user">
                    <div class="font-weight-bold">{{ item.user.name }}</div>
                    <div class="text-caption">{{ item.user.username }}</div>
                  </div>
                  <div v-else class="text-grey">
                    System
                  </div>
                </template>
                
                <template v-slot:item.created_at="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>
                
                <template v-slot:item.actions="{ item }">
                  <div class="d-flex justify-center" style="gap:8px">
                    <v-btn 
                      @click="viewLog(item)" 
                      color="primary" 
                      size="small" 
                      variant="outlined"
                    >
                      Detail
                    </v-btn>
                  </div>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Log Detail Dialog -->
    <v-dialog v-model="showLogDialog" max-width="800">
      <v-card v-if="selectedLog">
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Audit Log Details</span>
          <v-btn @click="showLogDialog = false" icon="mdi:close" variant="text" />
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <div class="mb-3">
                <strong>Action:</strong>
                <v-chip :color="getActionColor(selectedLog.action)" size="small" class="ml-2">
                  {{ selectedLog.action }}
                </v-chip>
              </div>
              
              <div class="mb-3">
                <strong>User:</strong>
                <div v-if="selectedLog.user" class="mt-1">
                  <div>{{ selectedLog.user.name }} ({{ selectedLog.user.username }})</div>
                  <div class="text-caption">{{ selectedLog.user.email }}</div>
                </div>
                <div v-else class="text-grey mt-1">System</div>
              </div>
              
              <div class="mb-3">
                <strong>IP Address:</strong>
                <div class="mt-1">{{ selectedLog.ip_address || 'N/A' }}</div>
              </div>
              
              <div class="mb-3">
                <strong>Timestamp:</strong>
                <div class="mt-1">{{ formatDate(selectedLog.created_at) }}</div>
              </div>
            </v-col>
            
            <v-col cols="12" md="6">
              <div class="mb-3">
                <strong>Details:</strong>
                <v-card variant="outlined" class="mt-2">
                  <v-card-text>
                    <pre class="text-caption">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
                  </v-card-text>
                </v-card>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { 
  fetchAuditLogs, 
  fetchAuditStatistics, 
  exportAuditLogs,
  formatAuditLogDetails,
  getEventTypeColor,
  getEventTypeIcon,
  getActionColor,
  type AuditLog,
  type AuditLogFilters,
  type AuditStatistics
} from '@/services/auditLog';

// Breadcrumbs removed for simplicity

// State
const loading = ref(false);
const exporting = ref(false);
const logs = ref<AuditLog[]>([]);
const statistics = ref<AuditStatistics>({
  total_logs: 0,
  logs_by_event_type: {},
  top_actions: {},
  unique_users: 0,
  unique_ips: 0
});

// Filters
const filters = ref<AuditLogFilters>({
  limit: 50,
  offset: 0
});

// Pagination
const currentPage = ref(1);
const pagination = ref({
  limit: 50,
  offset: 0,
  has_more: false
});

// Dialog
const showLogDialog = ref(false);
const selectedLog = ref<AuditLog | null>(null);

// Event types for filter
const eventTypes = [
  { title: 'Authentication', value: 'authentication' },
  { title: 'CRUD Operation', value: 'crud_operation' },
  { title: 'Data Transfer', value: 'data_transfer' },
  { title: 'System Event', value: 'system_event' },
  { title: 'Security Event', value: 'security_event' }
];

// Table headers
const headers = [
  { title: 'ID', key: 'id', sortable: true },
  { title: 'Action', key: 'action', sortable: true },
  { title: 'User', key: 'user', sortable: false },
  { title: 'Details', key: 'details', sortable: false },
  { title: 'IP Address', key: 'ip_address', sortable: true },
  { title: 'Timestamp', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
];

// Methods
async function loadLogs() {
  loading.value = true;
  try {
    const response = await fetchAuditLogs(filters.value);
    if (response.success) {
      logs.value = response.data.logs;
      pagination.value = response.data.pagination;
    }
  } catch (error) {
    console.error('Error loading audit logs:', error);
  } finally {
    loading.value = false;
  }
}

async function loadStatistics() {
  try {
    const response = await fetchAuditStatistics(filters.value);
    if (response.success) {
      statistics.value = response.data;
    }
  } catch (error) {
    console.error('Error loading statistics:', error);
  }
}

function applyFilters() {
  filters.value.offset = 0;
  currentPage.value = 1;
  loadLogs();
  loadStatistics();
}

function clearFilters() {
  filters.value = {
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
    }
  } catch (error) {
    console.error('Error exporting logs:', error);
  } finally {
    exporting.value = false;
  }
}

function onPageChange(page: number) {
  currentPage.value = page;
  filters.value.offset = (page - 1) * pagination.value.limit;
  loadLogs();
}

function viewLog(log: AuditLog) {
  selectedLog.value = log;
  showLogDialog.value = true;
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('id-ID');
}

// Lifecycle
onMounted(() => {
  loadLogs();
  loadStatistics();
});
</script>
