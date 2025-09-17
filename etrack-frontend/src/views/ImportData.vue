<template>
  <div class="pa-6">
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="mr-3">mdi-file-import</v-icon>
        <span>Import Data Excel/CSV</span>
      </v-card-title>
      <v-card-text>
        <p class="text-body-1 mb-4">
          Import data siswa dan pegawai dari file Excel (.xlsx, .xls) atau CSV. 
          Pastikan format file sesuai dengan template yang disediakan.
        </p>
      </v-card-text>
    </v-card>

    <!-- Import Students -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="success" class="mr-2">mdi-account-multiple</v-icon>
        <span>Import Data Siswa</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-file-input
              v-model="studentFile"
              label="Pilih File Excel/CSV"
              accept=".xlsx,.xls,.csv"
              prepend-icon="mdi-file-document"
              variant="outlined"
              :disabled="importingStudents"
              @change="onStudentFileChange"
            ></v-file-input>
          </v-col>
          <v-col cols="12" md="6" class="d-flex align-center">
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-download"
              @click="downloadStudentTemplate"
              class="mr-3"
            >
              Download Template
            </v-btn>
            <v-btn
              color="success"
              @click="importStudents"
              :loading="importingStudents"
              :disabled="!studentFile"
              prepend-icon="mdi-upload"
            >
              Import Siswa
            </v-btn>
          </v-col>
        </v-row>
        
        <v-alert
          v-if="studentImportResult"
          :type="studentImportResult.success ? 'success' : 'error'"
          variant="tonal"
          class="mt-4"
        >
          <div class="d-flex align-center">
            <v-icon class="mr-2">{{ studentImportResult.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <div>
              <div class="font-weight-bold">{{ studentImportResult.message }}</div>
              <div v-if="studentImportResult.data" class="text-caption mt-1">
                Berhasil: {{ studentImportResult.data.imported_count }} | 
                Gagal: {{ studentImportResult.data.failed_count }}
              </div>
            </div>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Import Employees -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="info" class="mr-2">mdi-account-tie</v-icon>
        <span>Import Data Pegawai</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-file-input
              v-model="employeeFile"
              label="Pilih File Excel/CSV"
              accept=".xlsx,.xls,.csv"
              prepend-icon="mdi-file-document"
              variant="outlined"
              :disabled="importingEmployees"
              @change="onEmployeeFileChange"
            ></v-file-input>
          </v-col>
          <v-col cols="12" md="6" class="d-flex align-center">
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-download"
              @click="downloadEmployeeTemplate"
              class="mr-3"
            >
              Download Template
            </v-btn>
            <v-btn
              color="success"
              @click="importEmployees"
              :loading="importingEmployees"
              :disabled="!employeeFile"
              prepend-icon="mdi-upload"
            >
              Import Pegawai
            </v-btn>
          </v-col>
        </v-row>
        
        <v-alert
          v-if="employeeImportResult"
          :type="employeeImportResult.success ? 'success' : 'error'"
          variant="tonal"
          class="mt-4"
        >
          <div class="d-flex align-center">
            <v-icon class="mr-2">{{ employeeImportResult.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            <div>
              <div class="font-weight-bold">{{ employeeImportResult.message }}</div>
              <div v-if="employeeImportResult.data" class="text-caption mt-1">
                Berhasil: {{ employeeImportResult.data.imported_count }} | 
                Gagal: {{ employeeImportResult.data.failed_count }}
              </div>
            </div>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Import History -->
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon color="warning" class="mr-2">mdi-history</v-icon>
        <span>Riwayat Import</span>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          variant="outlined"
          prepend-icon="mdi-refresh"
          @click="loadImportHistory"
          :loading="loadingHistory"
        >
          Refresh
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-data-table
          :headers="historyHeaders"
          :items="importHistory"
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
const studentFile = ref<File[]>([])
const employeeFile = ref<File[]>([])
const importingStudents = ref(false)
const importingEmployees = ref(false)
const studentImportResult = ref<any>(null)
const employeeImportResult = ref<any>(null)
const importHistory = ref([])
const loadingHistory = ref(false)
const showErrorDialog = ref(false)
const currentErrors = ref<string[]>([])

// Table headers
const historyHeaders = [
  { title: 'Tipe', key: 'type', sortable: true },
  { title: 'File', key: 'file_name', sortable: true },
  { title: 'Total Baris', key: 'total_rows', sortable: true },
  { title: 'Berhasil', key: 'imported_count', sortable: true },
  { title: 'Gagal', key: 'failed_count', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Tanggal', key: 'created_at', sortable: true },
]

// Methods
const onStudentFileChange = (files: File[]) => {
  if (files && files.length > 0) {
    studentFile.value = files
    studentImportResult.value = null
  }
}

const onEmployeeFileChange = (files: File[]) => {
  if (files && files.length > 0) {
    employeeFile.value = files
    employeeImportResult.value = null
  }
}

const downloadStudentTemplate = async () => {
  try {
    const response = await api.get('/import/students/template', {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'template_import_siswa.xlsx'
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading template:', error)
  }
}

const downloadEmployeeTemplate = async () => {
  try {
    const response = await api.get('/import/employees/template', {
      responseType: 'blob'
    })
    
    const blob = new Blob([response.data], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'template_import_pegawai.xlsx'
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading template:', error)
  }
}

const importStudents = async () => {
  if (!studentFile.value || studentFile.value.length === 0) return
  
  try {
    importingStudents.value = true
    const formData = new FormData()
    formData.append('file', studentFile.value[0])
    
    const response = await api.post('/import/students', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    studentImportResult.value = response.data
    
    // Show errors if any
    if (response.data.data?.errors?.length > 0) {
      currentErrors.value = response.data.data.errors
      showErrorDialog.value = true
    }
    
    // Reload history
    loadImportHistory()
    
  } catch (error: any) {
    studentImportResult.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal mengimport data siswa'
    }
  } finally {
    importingStudents.value = false
  }
}

const importEmployees = async () => {
  if (!employeeFile.value || employeeFile.value.length === 0) return
  
  try {
    importingEmployees.value = true
    const formData = new FormData()
    formData.append('file', employeeFile.value[0])
    
    const response = await api.post('/import/employees', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    employeeImportResult.value = response.data
    
    // Show errors if any
    if (response.data.data?.errors?.length > 0) {
      currentErrors.value = response.data.data.errors
      showErrorDialog.value = true
    }
    
    // Reload history
    loadImportHistory()
    
  } catch (error: any) {
    employeeImportResult.value = {
      success: false,
      message: error.response?.data?.message || 'Gagal mengimport data pegawai'
    }
  } finally {
    importingEmployees.value = false
  }
}

const loadImportHistory = async () => {
  try {
    loadingHistory.value = true
    const response = await api.get('/import/history')
    importHistory.value = response.data.data || []
  } catch (error) {
    console.error('Error loading import history:', error)
  } finally {
    loadingHistory.value = false
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'success': return 'success'
    case 'partial': return 'warning'
    case 'failed': return 'error'
    default: return 'grey'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'success': return 'Berhasil'
    case 'partial': return 'Sebagian'
    case 'failed': return 'Gagal'
    default: return 'Unknown'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('id-ID')
}

// Lifecycle
onMounted(() => {
  loadImportHistory()
})
</script>
