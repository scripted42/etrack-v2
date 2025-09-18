<template>
  <div>
    <BaseBreadcrumb :title="'Registrasi Wajah Pegawai'" :breadcrumb="breadcrumbs" />
    
    <v-card class="mt-4">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Registrasi Wajah Pegawai</span>
        <v-btn color="primary" @click="openRegistration">
          <v-icon start>mdi-camera-plus</v-icon>
          Registrasi Wajah Baru
        </v-btn>
      </v-card-title>
      
      <v-card-text>
        <!-- Employee List with Face Registration Status -->
        <v-data-table
          :headers="headers"
          :items="employees"
          :loading="loading"
          :search="search"
          class="elevation-1"
        >
          <template v-slot:top>
            <v-toolbar flat>
              <v-toolbar-title>Daftar Pegawai</v-toolbar-title>
              <v-divider class="mx-4" inset vertical></v-divider>
              <v-spacer></v-spacer>
              <v-text-field
                v-model="search"
                append-icon="mdi-magnify"
                label="Cari Pegawai"
                single-line
                hide-details
              ></v-text-field>
            </v-toolbar>
          </template>

          <template v-slot:item.photo="{ item }">
            <v-avatar size="40">
              <v-img :src="item.face_photo_url || '/default-avatar.png'"></v-img>
            </v-avatar>
          </template>

          <template v-slot:item.face_status="{ item }">
            <v-chip 
              :color="item.has_registered_face ? 'success' : 'warning'"
              size="small"
            >
              <v-icon start>
                {{ item.has_registered_face ? 'mdi-check-circle' : 'mdi-alert-circle' }}
              </v-icon>
              {{ item.has_registered_face ? 'Terdaftar' : 'Belum Terdaftar' }}
            </v-chip>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-btn
              v-if="!item.has_registered_face"
              @click="registerEmployeeFace(item)"
              color="primary"
              size="small"
              variant="outlined"
            >
              <v-icon start>mdi-camera-plus</v-icon>
              Daftarkan
            </v-btn>
            <v-btn
              v-else
              @click="reRegisterEmployeeFace(item)"
              color="warning"
              size="small"
              variant="outlined"
            >
              <v-icon start>mdi-camera-refresh</v-icon>
              Update
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Face Registration Dialog -->
    <v-dialog v-model="showRegistrationDialog" max-width="800" persistent>
      <v-card>
        <v-card-title>
          <span class="text-h5">Registrasi Wajah - {{ selectedEmployee?.nama }}</span>
        </v-card-title>

        <v-card-text>
          <div class="registration-container">
            <!-- Camera Preview -->
            <div class="camera-preview">
              <video 
                ref="videoElement" 
                autoplay 
                muted 
                playsinline
                class="camera-feed"
              ></video>
              
              <!-- Face Detection Overlay -->
              <canvas 
                ref="canvasElement"
                class="face-overlay"
              ></canvas>

              <!-- Loading Indicator -->
              <div v-if="isLoading" class="loading-overlay">
                <v-progress-circular
                  indeterminate
                  color="primary"
                  size="64"
                ></v-progress-circular>
                <p class="loading-text">{{ loadingText }}</p>
              </div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
              <h3>Petunjuk Registrasi:</h3>
              <ol>
                <li>Pastikan wajah terlihat jelas di kamera</li>
                <li>Posisikan wajah di tengah frame</li>
                <li>Pastikan pencahayaan cukup</li>
                <li>Jangan menggunakan topi atau kacamata gelap</li>
                <li>Klik "Ambil Foto" ketika siap</li>
              </ol>
            </div>
          </div>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeRegistration" :disabled="isProcessing">
            Batal
          </v-btn>
          <v-btn 
            @click="capturePhoto" 
            color="primary"
            :loading="isProcessing"
            :disabled="!cameraReady"
          >
            <v-icon start>mdi-camera</v-icon>
            Ambil Foto
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success Dialog -->
    <v-dialog v-model="showSuccessDialog" max-width="400">
      <v-card>
        <v-card-title class="text-center">
          <v-icon color="success" size="48" class="mb-2">
            mdi-check-circle
          </v-icon>
          <div>Registrasi Berhasil!</div>
        </v-card-title>
        
        <v-card-text class="text-center">
          <p>Wajah pegawai berhasil didaftarkan untuk sistem absensi face recognition.</p>
        </v-card-text>

        <v-card-actions class="justify-center">
          <v-btn @click="showSuccessDialog = false" color="primary">
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Reactive variables
const loading = ref(false)
const search = ref('')
const employees = ref([])
const showRegistrationDialog = ref(false)
const showSuccessDialog = ref(false)
const selectedEmployee = ref(null)
const isProcessing = ref(false)

// Camera variables
const videoElement = ref<HTMLVideoElement>()
const canvasElement = ref<HTMLCanvasElement>()
const isLoading = ref(false)
const loadingText = ref('Memuat kamera...')
const cameraReady = ref(false)

// Stream
let mediaStream: MediaStream | null = null

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', to: '/dashboard' },
  { title: 'Registrasi Wajah Pegawai' }
]

// Table headers
const headers = [
  { title: 'Foto', key: 'photo', sortable: false },
  { title: 'NIP', key: 'nip' },
  { title: 'Nama', key: 'nama' },
  { title: 'Jabatan', key: 'jabatan' },
  { title: 'Status Wajah', key: 'face_status', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false }
]

// Methods
const loadEmployees = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/employees')
    const data = await response.json()
    
    if (data.success) {
      employees.value = data.data.map(employee => ({
        ...employee,
        has_registered_face: employee.face_photo_path && employee.face_registered_at,
        face_photo_url: employee.face_photo_path ? `/storage/${employee.face_photo_path}` : null
      }))
    }
  } catch (error) {
    console.error('Error loading employees:', error)
  } finally {
    loading.value = false
  }
}

const openRegistration = () => {
  showRegistrationDialog.value = true
  initCamera()
}

const closeRegistration = () => {
  showRegistrationDialog.value = false
  stopCamera()
}

const registerEmployeeFace = (employee: any) => {
  selectedEmployee.value = employee
  openRegistration()
}

const reRegisterEmployeeFace = (employee: any) => {
  if (confirm('Apakah Anda yakin ingin memperbarui foto wajah pegawai ini?')) {
    selectedEmployee.value = employee
    openRegistration()
  }
}

const initCamera = async () => {
  try {
    isLoading.value = true
    loadingText.value = 'Memuat kamera...'

    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user'
      }
    })

    if (videoElement.value) {
      videoElement.value.srcObject = mediaStream
      videoElement.value.onloadedmetadata = () => {
        console.log('Video metadata loaded')
        setupCanvas()
        cameraReady.value = true
        isLoading.value = false
      }
      
      videoElement.value.oncanplay = () => {
        console.log('Video can play')
        if (!cameraReady.value) {
          setupCanvas()
          cameraReady.value = true
          isLoading.value = false
        }
      }
    }

  } catch (error) {
    console.error('Camera initialization error:', error)
    isLoading.value = false
    alert('Gagal mengakses kamera. Pastikan izin kamera diizinkan.')
  }
}

const setupCanvas = () => {
  if (!videoElement.value || !canvasElement.value) {
    console.error('Video or canvas element not found')
    return
  }
  
  // Wait for video metadata to load
  if (videoElement.value.videoWidth === 0 || videoElement.value.videoHeight === 0) {
    console.log('Video dimensions not ready, waiting...')
    setTimeout(setupCanvas, 100)
    return
  }
  
  canvasElement.value.width = videoElement.value.videoWidth
  canvasElement.value.height = videoElement.value.videoHeight
  
  console.log('Canvas setup complete:', {
    width: canvasElement.value.width,
    height: canvasElement.value.height
  })
}

const stopCamera = () => {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
    mediaStream = null
  }
  cameraReady.value = false
}

const capturePhoto = async () => {
  if (!videoElement.value || !canvasElement.value || !selectedEmployee.value) {
    alert('Kamera atau pegawai tidak tersedia')
    return
  }

  try {
    isProcessing.value = true

    // Check if video is ready
    if (videoElement.value.readyState < 2) {
      alert('Video belum siap, tunggu sebentar')
      return
    }

    if (videoElement.value.videoWidth === 0 || videoElement.value.videoHeight === 0) {
      alert('Dimensi video tidak tersedia')
      return
    }

    // Setup canvas
    canvasElement.value.width = videoElement.value.videoWidth
    canvasElement.value.height = videoElement.value.videoHeight

    // Capture photo
    const ctx = canvasElement.value.getContext('2d')
    if (!ctx) {
      alert('Canvas context tidak tersedia')
      return
    }

    ctx.drawImage(videoElement.value, 0, 0, canvasElement.value.width, canvasElement.value.height)
    const photoData = canvasElement.value.toDataURL('image/jpeg', 0.8)

    console.log('Photo captured, size:', photoData.length)

    // Send to backend
    const response = await fetch('/api/attendance/face-recognition/register-face', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      },
      body: JSON.stringify({
        employee_id: selectedEmployee.value.id,
        face_photo: photoData
      })
    })

    const result = await response.json()

    if (result.success) {
      showSuccessDialog.value = true
      closeRegistration()
      loadEmployees() // Reload data
    } else {
      alert('Gagal mendaftarkan wajah: ' + result.message)
    }

  } catch (error) {
    console.error('Registration error:', error)
    alert('Terjadi kesalahan saat mendaftarkan wajah: ' + error.message)
  } finally {
    isProcessing.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadEmployees()
})

onUnmounted(() => {
  stopCamera()
})
</script>

<style scoped>
.registration-container {
  display: flex;
  gap: 24px;
}

.camera-preview {
  flex: 1;
  position: relative;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
}

.camera-feed {
  width: 100%;
  height: 400px;
  object-fit: cover;
}

.face-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: rgba(0, 0, 0, 0.8);
  color: white;
}

.loading-text {
  margin-top: 16px;
  font-size: 18px;
}

.instructions {
  flex: 1;
  padding: 16px;
}

.instructions h3 {
  margin-bottom: 16px;
  color: #1976d2;
}

.instructions ol {
  padding-left: 20px;
}

.instructions li {
  margin-bottom: 8px;
  line-height: 1.5;
}
</style>
