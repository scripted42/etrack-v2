<template>
  <div class="pa-6">
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="mr-3">mdi-file-export</v-icon>
        <span>Export Data Excel/CSV</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-1 mb-4">
          Export data siswa, pegawai, dan laporan audit trail ke file Excel (.xlsx) atau CSV. 
          Gunakan filter untuk menyesuaikan data yang akan diekspor.
        </p>
      </v-card-text>
    </v-card>

    <!-- Action Bar -->
    <v-card class="mb-6">
      <v-card-text class="pa-4">
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon color="primary" class="mr-2">mdi-download-multiple</v-icon>
            <span class="text-h6">Quick Actions</span>
          </div>
          <div class="d-flex align-center" style="gap: 12px;">
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-refresh"
              @click="refreshAllData"
              :loading="loadingHistory"
              size="default"
            >
              Refresh Data
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Export Students -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="success" class="mr-2">mdi-account-multiple</v-icon>
        <span>Export Data Siswa</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-select
              v-model="studentFilters.kelas"
              label="Filter Kelas"
              :items="kelasOptions"
              clearable
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="studentFilters.status"
              label="Filter Status"
              :items="statusOptions"
              clearable
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="studentFilters.format"
              label="Format File"
              :items="formatOptions"
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3" class="d-flex align-center justify-center">
            <v-btn
              color="success"
              @click="exportStudents"
              :loading="exportingStudents"
              prepend-icon="mdi-download"
              block
              size="default"
              variant="elevated"
            >
              Export Siswa
            </v-btn>
          </v-col>
        </v-row>
        
        <v-alert
          v-if="studentExportResult"
          :type="studentExportResult.success ? 'success' : 'error'"
          variant="tonal"
          class="mt-4"
        >
          <div class="d-flex align-center">
            <v-icon class="mr-2">{{ studentExportResult.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <div>
              <div class="font-weight-bold">{{ studentExportResult.message }}</div>
              <div v-if="studentExportResult.data" class="text-caption mt-1">
                Total data: {{ studentExportResult.data.total_count }}
              </div>
            </div>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Export Employees -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="info" class="mr-2">mdi-account-tie</v-icon>
        <span>Export Data Pegawai</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-select
              v-model="employeeFilters.jabatan"
              label="Filter Jabatan"
              :items="jabatanOptions"
              clearable
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="employeeFilters.status"
              label="Filter Status"
              :items="statusOptions"
              clearable
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="employeeFilters.format"
              label="Format File"
              :items="formatOptions"
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3" class="d-flex align-center justify-center">
            <v-btn
              color="info"
              @click="exportEmployees"
              :loading="exportingEmployees"
              prepend-icon="mdi-download"
              block
              size="default"
              variant="elevated"
            >
              Export Pegawai
            </v-btn>
          </v-col>
        </v-row>
        
        <v-alert
          v-if="employeeExportResult"
          :type="employeeExportResult.success ? 'success' : 'error'"
          variant="tonal"
          class="mt-4"
        >
          <div class="d-flex align-center">
            <v-icon class="mr-2">{{ employeeExportResult.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <div>
              <div class="font-weight-bold">{{ employeeExportResult.message }}</div>
              <div v-if="employeeExportResult.data" class="text-caption mt-1">
                Total data: {{ employeeExportResult.data.total_count }}
              </div>
            </div>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Export Audit Trail -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="warning" class="mr-2">mdi-shield-account</v-icon>
        <span>Export Audit Trail</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="auditFilters.dateFrom"
              label="Tanggal Mulai"
              type="date"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="auditFilters.dateTo"
              label="Tanggal Selesai"
              type="date"
              variant="outlined"
              density="comfortable"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="auditFilters.eventType"
              label="Filter Event"
              :items="eventTypeOptions"
              clearable
              variant="outlined"
              density="comfortable"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3" class="d-flex align-center justify-center">
            <v-btn
              color="warning"
              @click="exportAuditTrail"
              :loading="exportingAudit"
              prepend-icon="mdi-download"
              block
              size="default"
              variant="elevated"
            >
              Export Audit
            </v-btn>
          </v-col>
        </v-row>
        
        <v-alert
          v-if="auditExportResult"
          :type="auditExportResult.success ? 'success' : 'error'"
          variant="tonal"
          class="mt-4"
        >
          <div class="d-flex align-center">
            <v-icon class="mr-2">{{ auditExportResult.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <div>
              <div class="font-weight-bold">{{ auditExportResult.message }}</div>
              <div v-if="auditExportResult.data" class="text-caption mt-1">
                Total data: {{ auditExportResult.data.total_count }}
              </div>
            </div>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Export History -->
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="mr-2">mdi-history</v-icon>
        <span>Riwayat Export</span>
      </v-card-title>
      <v-card-text>
        <v-data-table
          :headers="historyHeaders"
          :items="exportHistory"
          :loading="loadingHistory"
          class="elevation-1"
        >
          <template v-slot:item.type="{ item }">
            <v-chip
              :color="item.type === 'students' ? 'success' : 'info'"
              size="small"
            >
              {{ item.type === 'students' ? 'Siswa' : 'Pegawai' }}
            </v-chip>
          </template>
          
          <template v-slot:item.status="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              size="small"
            >
              {{ getStatusText(item.status) }}
            </v-chip>
          </template>
          
          <template v-slot:item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Error Details Dialog -->
    <v-dialog v-model="showErrorDialog" max-width="600">
      <v-card>
        <v-card-title>
          <v-icon class="mr-2" color="error">mdi-alert-circle</v-icon>
          Detail Error Import
        </v-card-title>
        <v-card-text>
          <v-list>
            <v-list-item
              v-for="(error, index) in currentErrors"
              :key="index"
              class="text-caption"
            >
              <v-list-item-title>{{ error }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="showErrorDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

// Reactive data
const exportingStudents = ref(false)
const exportingEmployees = ref(false)
const exportingAudit = ref(false)
const studentExportResult = ref<any>(null)
const employeeExportResult = ref<any>(null)
const auditExportResult = ref<any>(null)
const exportHistory = ref([])
const loadingHistory = ref(false)

// Filters
const studentFilters = ref({
  kelas: null,
  status: null,
  format: 'xlsx'
})

const employeeFilters = ref({
  jabatan: null,
  status: null,
  format: 'xlsx'
})

const auditFilters = ref({
  dateFrom: null,
  dateTo: null,
  eventType: null,
  format: 'xlsx'
})

// Options
const kelasOptions = ref(['7A', '7B', '7C', '8A', '8B', '8C', '9A', '9B', '9C'])
const jabatanOptions = ref(['Guru', 'Kepala Sekolah', 'Wakil Kepala Sekolah', 'Staff', 'TU'])
const statusOptions = ref(['aktif', 'tidak aktif'])
const formatOptions = ref([
  { title: 'Excel (.xlsx)', value: 'xlsx' },
  { title: 'CSV (.csv)', value: 'csv' }
])
const eventTypeOptions = ref([
  'LOGIN', 'LOGOUT', 'CREATE_STUDENT', 'UPDATE_STUDENT', 'DELETE_STUDENT',
  'CREATE_EMPLOYEE', 'UPDATE_EMPLOYEE', 'DELETE_EMPLOYEE', 'EXPORT_DATA'
])

// Table headers
const historyHeaders = [
  { title: 'Tipe', key: 'type', sortable: true },
  { title: 'File', key: 'file_name', sortable: true },
  { title: 'Total Data', key: 'total_count', sortable: true },
  { title: 'Format', key: 'format', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Tanggal', key: 'created_at', sortable: true },
]

// Methods
const exportStudents = async () => {
  try {
    exportingStudents.value = true
    const params = new URLSearchParams()
    
    if (studentFilters.value.kelas) params.append('kelas', studentFilters.value.kelas)
    if (studentFilters.value.status) params.append('status', studentFilters.value.status)
    params.append('format', studentFilters.value.format)
    
    const response = await api.get(`/export/students?${params.toString()}`, {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { 
      type: studentFilters.value.format === 'csv' 
        ? 'text/csv' 
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `data_siswa_${new Date().toISOString().split('T')[0]}.${studentFilters.value.format}`
    link.click()
    window.URL.revokeObjectURL(url)
    
    studentExportResult.value = {
      success: true,
      message: 'Export data siswa berhasil',
      data: { total_count: 'Data berhasil diekspor' }
    }
    
    // Reload history
    loadExportHistory()
    
  } catch (error: any) {
    studentExportResult.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal mengexport data siswa'
    }
  } finally {
    exportingStudents.value = false
  }
}

const exportEmployees = async () => {
  try {
    exportingEmployees.value = true
    const params = new URLSearchParams()
    
    if (employeeFilters.value.jabatan) params.append('jabatan', employeeFilters.value.jabatan)
    if (employeeFilters.value.status) params.append('status', employeeFilters.value.status)
    params.append('format', employeeFilters.value.format)
    
    const response = await api.get(`/export/employees?${params.toString()}`, {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { 
      type: employeeFilters.value.format === 'csv' 
        ? 'text/csv' 
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `data_pegawai_${new Date().toISOString().split('T')[0]}.${employeeFilters.value.format}`
    link.click()
    window.URL.revokeObjectURL(url)
    
    employeeExportResult.value = {
      success: true,
      message: 'Export data pegawai berhasil',
      data: { total_count: 'Data berhasil diekspor' }
    }
    
    // Reload history
    loadExportHistory()
    
  } catch (error: any) {
    employeeExportResult.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal mengexport data pegawai'
    }
  } finally {
    exportingEmployees.value = false
  }
}

const exportAuditTrail = async () => {
  try {
    exportingAudit.value = true
    const params = new URLSearchParams()
    
    if (auditFilters.value.dateFrom) params.append('date_from', auditFilters.value.dateFrom)
    if (auditFilters.value.dateTo) params.append('date_to', auditFilters.value.dateTo)
    if (auditFilters.value.eventType) params.append('event_type', auditFilters.value.eventType)
    params.append('format', auditFilters.value.format)
    
    const response = await api.get(`/export/audit-trail?${params.toString()}`, {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { 
      type: auditFilters.value.format === 'csv' 
        ? 'text/csv' 
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `audit_trail_${new Date().toISOString().split('T')[0]}.${auditFilters.value.format}`
    link.click()
    window.URL.revokeObjectURL(url)
    
    auditExportResult.value = {
      success: true,
      message: 'Export audit trail berhasil',
      data: { total_count: 'Data berhasil diekspor' }
    }
    
    // Reload history
    loadExportHistory()
    
  } catch (error: any) {
    auditExportResult.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal mengexport audit trail'
    }
  } finally {
    exportingAudit.value = false
  }
}

const loadExportHistory = async () => {
  try {
    loadingHistory.value = true
    const response = await api.get('/export/history')
    exportHistory.value = response.data.data || []
  } catch (error) {
    console.error('Error loading export history:', error)
  } finally {
    loadingHistory.value = false
  }
}

const refreshAllData = async () => {
  await loadExportHistory()
  // Add other refresh functions if needed
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'success': return 'success'
    case 'completed': return 'success'
    case 'failed': return 'error'
    default: return 'grey'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'success': return 'Berhasil'
    case 'completed': return 'Selesai'
    case 'failed': return 'Gagal'
    default: return 'Unknown'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('id-ID')
}

// Lifecycle
onMounted(() => {
  loadExportHistory()
})
</script>

<style scoped>
.v-card {
  transition: all 0.3s ease;
}

.v-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.v-btn {
  transition: all 0.3s ease;
  font-weight: 500;
  height: 40px !important;
  min-height: 40px !important;
}

.v-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.v-select, .v-text-field {
  transition: all 0.3s ease;
  height: 40px;
}

.v-select:focus-within, .v-text-field:focus-within {
  transform: translateY(-1px);
}

/* Perfect alignment for buttons and inputs */
.d-flex.align-center {
  align-items: center;
  height: 40px;
}

.d-flex.justify-center {
  justify-content: center;
}

/* Consistent spacing */
.v-row {
  margin-bottom: 0;
  align-items: center;
}

.v-col {
  padding-bottom: 8px;
  display: flex;
  align-items: center;
}

/* Button centering */
.v-btn {
  margin: 0 auto;
}

/* Ensure all form elements have same height */
.v-field {
  height: 40px;
}

.v-field__input {
  min-height: 40px;
}
</style>
