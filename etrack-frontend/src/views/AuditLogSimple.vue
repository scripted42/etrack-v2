<template>
  <div class="pa-4">
    <h1 class="text-h4 mb-6">Audit Trail Dashboard</h1>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat Audit Logs...</div>
    </div>
    
    <!-- Error State -->
    <v-alert v-if="error" type="error" class="mb-4">
      {{ error }}
    </v-alert>
    
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

      <!-- Top Actions -->
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title>Top Actions</v-card-title>
            <v-card-text>
              <div v-if="Object.keys(statistics.top_actions || {}).length > 0">
                <div v-for="(count, action) in statistics.top_actions" :key="action" class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">{{ action }}</span>
                  <v-chip color="primary" size="small">{{ count }}</v-chip>
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
            <v-card-title>Recent Logs</v-card-title>
            <v-card-text>
              <div v-if="logs.length > 0">
                <div v-for="log in logs.slice(0, 5)" :key="log.id" class="mb-3">
                  <div class="d-flex justify-space-between align-center">
                    <div>
                      <div class="font-weight-bold">{{ log.action }}</div>
                      <div class="text-caption">{{ log.user?.name || 'System' }}</div>
                    </div>
                    <div class="text-right">
                      <v-chip :color="getActionColor(log.action)" size="x-small">{{ log.action }}</v-chip>
                      <div class="text-caption">{{ formatDate(log.created_at) }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                No logs available
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Test API Button -->
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title>API Test</v-card-title>
            <v-card-text>
              <v-btn @click="loadData" :loading="loading" color="primary" class="mr-2">
                <v-icon class="mr-2">mdi:refresh</v-icon>
                Refresh Data
              </v-btn>
              
              <v-btn @click="testAPI" :loading="testing" color="secondary">
                <v-icon class="mr-2">mdi:api</v-icon>
                Test API
              </v-btn>
              
              <div v-if="apiResult" class="mt-4">
                <h4>API Test Result:</h4>
                <pre class="text-caption">{{ JSON.stringify(apiResult, null, 2) }}</pre>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { fetchAuditLogs, fetchAuditStatistics } from '@/services/auditLog';

// State
const loading = ref(false);
const testing = ref(false);
const error = ref('');
const logs = ref([]);
const statistics = ref({
  total_logs: 0,
  logs_by_event_type: {},
  top_actions: {},
  unique_users: 0,
  unique_ips: 0
});
const apiResult = ref(null);

// Methods
async function loadData() {
  loading.value = true;
  error.value = '';
  
  try {
    // Load logs
    const logsResponse = await fetchAuditLogs({ limit: 10 });
    if (logsResponse.success) {
      logs.value = logsResponse.data.logs;
    }
    
    // Load statistics
    const statsResponse = await fetchAuditStatistics();
    if (statsResponse.success) {
      statistics.value = statsResponse.data;
    }
  } catch (err) {
    error.value = err.message || 'Error loading data';
    console.error('Error loading audit data:', err);
  } finally {
    loading.value = false;
  }
}

async function testAPI() {
  testing.value = true;
  apiResult.value = null;
  
  try {
    const response = await fetchAuditLogs({ limit: 3 });
    apiResult.value = response;
  } catch (err) {
    apiResult.value = { error: err.message };
  } finally {
    testing.value = false;
  }
}

function getActionColor(action: string): string {
  if (action.includes('CREATE') || action.includes('Create')) {
    return 'success';
  } else if (action.includes('UPDATE') || action.includes('Update')) {
    return 'info';
  } else if (action.includes('DELETE') || action.includes('Delete')) {
    return 'error';
  } else if (action.includes('LOGIN') || action.includes('Login')) {
    return 'primary';
  } else if (action.includes('FAILED') || action.includes('Failed')) {
    return 'error';
  } else {
    return 'grey';
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('id-ID');
}

// Lifecycle
onMounted(() => {
  loadData();
});
</script>
