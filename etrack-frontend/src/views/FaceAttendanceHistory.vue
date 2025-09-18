<template>
  <div>
    <BaseBreadcrumb :title="'Riwayat Absensi Wajah'" :breadcrumb="breadcrumbs" />
    
    <v-card class="mt-4">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Riwayat Absensi Wajah</span>
        <div class="d-flex gap-2">
          <v-btn variant="outlined" @click="exportData">
            <v-icon start>mdi-download</v-icon>
            Export Data
          </v-btn>
          <v-btn color="primary" @click="refreshData">
            <v-icon start>mdi-refresh</v-icon>
            Refresh
          </v-btn>
        </div>
      </v-card-title>
      
      <v-card-text>
        <!-- Filters -->
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.search"
              label="Cari Nama/NIP"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi:magnify"
              clearable
              @keyup.enter="loadData"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.attendance_type"
              :items="attendanceTypes"
              label="Tipe Absensi"
              variant="outlined"
              density="comfortable"
              clearable
              @update:modelValue="loadData"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.employee_id"
              :items="employees"
              item-title="nama"
              item-value="id"
              label="Pegawai"
              variant="outlined"
              density="comfortable"
              clearable
              @update:modelValue="loadData"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-text-field
              v-model="filters.start_date"
              label="Tanggal Mulai"
              type="date"
              variant="outlined"
              density="comfortable"
              @change="loadData"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-text-field
              v-model="filters.end_date"
              label="Tanggal Akhir"
              type="date"
              variant="outlined"
              density="comfortable"
              @change="loadData"
            />
          </v-col>
          <v-col cols="12" md="1">
            <v-btn @click="loadData" color="primary" block>
              <v-icon>mdi-filter</v-icon>
            </v-btn>
          </v-col>
        </v-row>

        <!-- Statistics Cards -->
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-card color="primary" variant="tonal">
              <v-card-text class="text-center">
                <div class="text-h4 font-weight-bold">{{ statistics.total_attendances }}</div>
                <div class="text-subtitle-1">Total Absensi</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card color="success" variant="tonal">
              <v-card-text class="text-center">
                <div class="text-h4 font-weight-bold">{{ statistics.present_count }}</div>
                <div class="text-subtitle-1">Hadir</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card color="warning" variant="tonal">
              <v-card-text class="text-center">
                <div class="text-h4 font-weight-bold">{{ statistics.late_count }}</div>
                <div class="text-subtitle-1">Terlambat</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card color="info" variant="tonal">
              <v-card-text class="text-center">
                <div class="text-h4 font-weight-bold">{{ statistics.average_confidence }}%</div>
                <div class="text-subtitle-1">Rata-rata Akurasi</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Attendance Table -->
        <v-data-table
          :headers="headers"
          :items="attendances"
          :loading="loading"
          :items-per-page="15"
          class="elevation-1"
        >
          <template v-slot:item.employee_photo="{ item }">
            <v-avatar size="40">
              <v-img :src="item.employee?.face_photo_url || '/default-avatar.png'"></v-img>
            </v-avatar>
          </template>

          <template v-slot:item.attendance_type="{ item }">
            <v-chip 
              :color="getAttendanceTypeColor(item.attendance_type)"
              size="small"
            >
              {{ getAttendanceTypeLabel(item.attendance_type) }}
            </v-chip>
          </template>

          <template v-slot:item.confidence_score="{ item }">
            <div class="d-flex align-center">
              <v-progress-circular
                :model-value="item.confidence_score * 100"
                :color="getConfidenceColor(item.confidence_score)"
                size="24"
                width="3"
                class="mr-2"
              ></v-progress-circular>
              <span>{{ Math.round(item.confidence_score * 100) }}%</span>
            </div>
          </template>

          <template v-slot:item.photo_path="{ item }">
            <v-btn
              @click="viewPhoto(item)"
              icon="mdi-eye"
              size="small"
              variant="text"
              :disabled="!item.photo_path"
            ></v-btn>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-btn
              @click="viewDetails(item)"
              icon="mdi-information"
              size="small"
              variant="text"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Photo Viewer Dialog -->
    <v-dialog v-model="showPhotoDialog" max-width="600">
      <v-card>
        <v-card-title>
          <span class="text-h5">Foto Absensi</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" @click="showPhotoDialog = false"></v-btn>
        </v-card-title>
        
        <v-card-text class="text-center">
          <v-img 
            :src="selectedPhoto" 
            max-height="400"
            contain
          ></v-img>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Details Dialog -->
    <v-dialog v-model="showDetailsDialog" max-width="500">
      <v-card>
        <v-card-title>
          <span class="text-h5">Detail Absensi</span>
        </v-card-title>
        
        <v-card-text v-if="selectedAttendance">
          <v-list>
            <v-list-item>
              <v-list-item-title>Pegawai</v-list-item-title>
              <v-list-item-subtitle>{{ selectedAttendance.employee?.nama }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>NIP</v-list-item-title>
              <v-list-item-subtitle>{{ selectedAttendance.employee?.nip }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Tanggal</v-list-item-title>
              <v-list-item-subtitle>{{ formatDate(selectedAttendance.attendance_date) }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Waktu</v-list-item-title>
              <v-list-item-subtitle>{{ selectedAttendance.attendance_time }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Tipe Absensi</v-list-item-title>
              <v-list-item-subtitle>{{ getAttendanceTypeLabel(selectedAttendance.attendance_type) }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Akurasi</v-list-item-title>
              <v-list-item-subtitle>{{ Math.round(selectedAttendance.confidence_score * 100) }}%</v-list-item-subtitle>
            </v-list-item>
            <v-list-item v-if="selectedAttendance.location">
              <v-list-item-title>Lokasi</v-list-item-title>
              <v-list-item-subtitle>{{ selectedAttendance.location }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showDetailsDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Reactive variables
const loading = ref(false)
const attendances = ref([])
const employees = ref([])
const statistics = ref({
  total_attendances: 0,
  present_count: 0,
  late_count: 0,
  average_confidence: 0
})

const showPhotoDialog = ref(false)
const showDetailsDialog = ref(false)
const selectedPhoto = ref('')
const selectedAttendance = ref(null)

// Filters
const filters = ref({
  search: '',
  attendance_type: '',
  employee_id: '',
  start_date: '',
  end_date: ''
})

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', to: '/dashboard' },
  { title: 'Riwayat Absensi Wajah' }
]

// Table headers
const headers = [
  { title: 'Foto', key: 'employee_photo', sortable: false },
  { title: 'NIP', key: 'employee.nip' },
  { title: 'Nama', key: 'employee.nama' },
  { title: 'Tanggal', key: 'attendance_date' },
  { title: 'Waktu', key: 'attendance_time' },
  { title: 'Tipe', key: 'attendance_type' },
  { title: 'Akurasi', key: 'confidence_score' },
  { title: 'Foto', key: 'photo_path', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false }
]

// Attendance types
const attendanceTypes = [
  { title: 'Datang Awal', value: 'early' },
  { title: 'Tepat Waktu', value: 'on_time' },
  { title: 'Terlambat', value: 'late' },
  { title: 'Lembur', value: 'overtime' }
]

// Methods
const loadData = async () => {
  try {
    loading.value = true
    
    const params = new URLSearchParams()
    if (filters.value.search) params.append('search', filters.value.search)
    if (filters.value.attendance_type) params.append('attendance_type', filters.value.attendance_type)
    if (filters.value.employee_id) params.append('employee_id', filters.value.employee_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`http://localhost:8000/api/attendance/face-recognition/history?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      attendances.value = data.data.data.map(attendance => ({
        ...attendance,
        employee: {
          ...attendance.employee,
          face_photo_url: attendance.employee?.face_photo_path ? `/storage/${attendance.employee.face_photo_path}` : null
        }
      }))
    }
  } catch (error) {
    console.error('Error loading attendance data:', error)
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/attendance/face-recognition/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      statistics.value = {
        total_attendances: data.data.total_attendances,
        present_count: data.data.present_count,
        late_count: data.data.late_count,
        average_confidence: Math.round(data.data.average_confidence * 100)
      }
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const loadEmployees = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/employees', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      employees.value = data.data
    }
  } catch (error) {
    console.error('Error loading employees:', error)
  }
}

const refreshData = () => {
  loadData()
  loadStatistics()
}

const exportData = () => {
  // Implement export functionality
  alert('Fitur export akan segera tersedia')
}

const viewPhoto = (attendance: any) => {
  if (attendance.photo_path) {
    selectedPhoto.value = `/storage/${attendance.photo_path}`
    showPhotoDialog.value = true
  }
}

const viewDetails = (attendance: any) => {
  selectedAttendance.value = attendance
  showDetailsDialog.value = true
}

const getAttendanceTypeColor = (type: string) => {
  const colors = {
    'early': 'success',
    'on_time': 'primary',
    'late': 'warning',
    'overtime': 'info'
  }
  return colors[type] || 'grey'
}

const getAttendanceTypeLabel = (type: string) => {
  const labels = {
    'early': 'Datang Awal',
    'on_time': 'Tepat Waktu',
    'late': 'Terlambat',
    'overtime': 'Lembur'
  }
  return labels[type] || type
}

const getConfidenceColor = (score: number) => {
  if (score >= 0.9) return 'success'
  if (score >= 0.8) return 'warning'
  return 'error'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID')
}

// Lifecycle
onMounted(() => {
  loadData()
  loadStatistics()
  loadEmployees()
})
</script>

<style scoped>
.v-card {
  margin-bottom: 16px;
}
</style>
