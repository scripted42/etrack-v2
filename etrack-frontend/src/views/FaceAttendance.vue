<template>
  <v-app>
    <v-main>
      <!-- Fullscreen Camera View -->
      <div class="face-attendance-container">
        <!-- Header Status Bar -->
        <div class="status-bar">
          <div class="status-info">
            <v-chip :color="statusColor" class="status-chip">
              <v-icon start :icon="statusIcon"></v-icon>
              {{ statusText }}
            </v-chip>
          </div>
          <div class="time-info">
            <span class="current-time">{{ currentTime }}</span>
            <span class="current-date">{{ currentDate }}</span>
          </div>
        </div>

        <!-- Camera Preview -->
        <div class="camera-container">
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

        <!-- Main Action Button -->
        <div class="action-container">
          <v-btn
            v-if="!isScanning"
            @click="startAttendance"
            :disabled="!cameraReady"
            size="x-large"
            color="primary"
            rounded="pill"
            class="attendance-btn"
          >
            <v-icon start icon="mdi-face-recognition"></v-icon>
            Mulai Absensi
          </v-btn>

          <v-btn
            v-else
            @click="stopAttendance"
            size="x-large"
            color="warning"
            rounded="pill"
            class="attendance-btn"
          >
            <v-icon start icon="mdi-stop"></v-icon>
            Hentikan
          </v-btn>
        </div>

        <!-- Test Capture Button -->
        <div class="test-capture-container">
          <v-btn
            @click="testCapturePhoto"
            :disabled="!cameraReady"
            size="small"
            color="secondary"
            variant="outlined"
            class="test-capture-btn"
          >
            <v-icon start icon="mdi-camera"></v-icon>
            Test Ambil Foto
          </v-btn>
        </div>

        <!-- Result Display -->
        <v-dialog v-model="showResult" max-width="400" persistent>
          <v-card>
            <v-card-title class="text-center">
              <v-icon 
                :color="resultSuccess ? 'success' : 'error'" 
                size="48"
                class="mb-2"
              >
                {{ resultSuccess ? 'mdi-check-circle' : 'mdi-alert-circle' }}
              </v-icon>
              <div>{{ resultTitle }}</div>
            </v-card-title>
            
            <v-card-text class="text-center">
              <p>{{ resultMessage }}</p>
              <div v-if="resultSuccess && employeeData" class="employee-info mt-4">
                <v-avatar size="64" class="mb-2">
                  <v-img :src="employeeData.photo || '/default-avatar.png'"></v-img>
                </v-avatar>
                <h3>{{ employeeData.name }}</h3>
                <p class="text-medium-emphasis">{{ employeeData.nip }}</p>
                <p class="text-medium-emphasis">{{ employeeData.position }}</p>
              </div>
            </v-card-text>

            <v-card-actions class="justify-center">
              <v-btn @click="closeResult" color="primary">Tutup</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Settings Button -->
        <v-btn
          @click="showSettings = true"
          icon="mdi-cog"
          variant="text"
          class="settings-btn"
        ></v-btn>

        <!-- Settings Dialog -->
        <v-dialog v-model="showSettings" max-width="500">
          <v-card>
            <v-card-title>Pengaturan Kamera</v-card-title>
            <v-card-text>
              <v-select
                v-model="selectedCamera"
                :items="availableCameras"
                item-title="label"
                item-value="deviceId"
                label="Pilih Kamera"
                @update:modelValue="switchCamera"
              ></v-select>
              
              <v-slider
                v-model="confidenceThreshold"
                min="0.5"
                max="0.95"
                step="0.05"
                label="Tingkat Kepercayaan"
                thumb-label
              ></v-slider>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn @click="showSettings = false">Tutup</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </div>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import faceAttendanceService from '@/services/faceAttendance'

// Reactive variables
const videoElement = ref<HTMLVideoElement>()
const canvasElement = ref<HTMLCanvasElement>()
const isLoading = ref(true)
const loadingText = ref('Memuat kamera...')
const cameraReady = ref(false)
const isScanning = ref(false)
const showResult = ref(false)
const showSettings = ref(false)

// Time and date
const currentTime = ref('')
const currentDate = ref('')

// Status
const statusText = ref('Menunggu')
const statusColor = ref('grey')
const statusIcon = ref('mdi-clock-outline')

// Result data
const resultSuccess = ref(false)
const resultTitle = ref('')
const resultMessage = ref('')
const employeeData = ref<any>(null)

// Camera settings
const selectedCamera = ref('')
const availableCameras = ref<any[]>([])
const confidenceThreshold = ref(0.8)

// Stream and detection
let mediaStream: MediaStream | null = null
let detectionInterval: NodeJS.Timeout | null = null

// Computed
const statusComputed = computed(() => {
  if (isScanning.value) {
    return { text: 'Memindai wajah...', color: 'warning', icon: 'mdi-face-recognition' }
  }
  if (cameraReady.value) {
    return { text: 'Siap', color: 'success', icon: 'mdi-check-circle' }
  }
  return { text: 'Menunggu', color: 'grey', icon: 'mdi-clock-outline' }
})

// Methods
const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit', 
    second: '2-digit' 
  })
  currentDate.value = now.toLocaleDateString('id-ID', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const initCamera = async () => {
  try {
    isLoading.value = true
    loadingText.value = 'Memuat kamera...'

    // Get available cameras
    const devices = await navigator.mediaDevices.enumerateDevices()
    availableCameras.value = devices
      .filter(device => device.kind === 'videoinput')
      .map(device => ({
        deviceId: device.deviceId,
        label: device.label || `Kamera ${device.deviceId.slice(0, 8)}`
      }))

    if (availableCameras.value.length === 0) {
      throw new Error('Tidak ada kamera yang tersedia')
    }

    // Use first camera if none selected
    if (!selectedCamera.value) {
      selectedCamera.value = availableCameras.value[0].deviceId
    }

    // Get video stream
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: {
        deviceId: selectedCamera.value,
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
        updateStatus()
      }
      
      videoElement.value.oncanplay = () => {
        console.log('Video can play')
        if (!cameraReady.value) {
          setupCanvas()
          cameraReady.value = true
          isLoading.value = false
          updateStatus()
        }
      }
    }

  } catch (error) {
    console.error('Camera initialization error:', error)
    isLoading.value = false
    showError('Gagal mengakses kamera', 'Pastikan izin kamera diizinkan dan perangkat kamera tersedia.')
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

const switchCamera = async (deviceId: string) => {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
  }
  selectedCamera.value = deviceId
  await initCamera()
}

const startAttendance = () => {
  if (!cameraReady.value) return
  
  isScanning.value = true
  updateStatus()
  
  // Start face detection
  detectionInterval = setInterval(() => {
    detectFace()
  }, 1000) // Check every second
  
  // Auto stop after 30 seconds
  setTimeout(() => {
    if (isScanning.value) {
      stopAttendance()
      showError('Waktu Habis', 'Silakan coba lagi atau pastikan wajah terlihat jelas.')
    }
  }, 30000)
}

const stopAttendance = () => {
  isScanning.value = false
  updateStatus()
  
  if (detectionInterval) {
    clearInterval(detectionInterval)
    detectionInterval = null
  }
}

const detectFace = async () => {
  // Placeholder for face detection logic
  // In real implementation, you would use face-api.js or similar library
  
  try {
    // Simulate face detection
    const isDetected = Math.random() > 0.7 // 30% chance of detection
    
    if (isDetected) {
      // Simulate face recognition
      const confidence = Math.random()
      
      if (confidence > confidenceThreshold.value) {
        // Success - simulate employee data
        const mockEmployee = {
          id: 1,
          name: 'John Doe',
          nip: '123456789',
          position: 'Guru Matematika',
          photo: '/avatar-1.png'
        }
        
        await processAttendance(mockEmployee)
      }
    }
  } catch (error) {
    console.error('Face detection error:', error)
  }
}

const processAttendance = async (employee: any) => {
  try {
    stopAttendance()
    
    // Capture photo first
    let photoData = ''
    try {
      photoData = await capturePhoto()
      console.log('Photo captured, size:', photoData.length)
    } catch (photoError) {
      console.error('Photo capture failed:', photoError)
      showError('Gagal Mengambil Foto', 'Tidak dapat mengambil foto dari kamera')
      return
    }
    
    // Call backend API to record attendance using service
    const result = await faceAttendanceService.processAttendance({
      employee_id: employee.id,
      photo: photoData,
      location: 'Sekolah',
      attendance_type: 'on_time'
    })
    
    if (result.success) {
      showSuccess('Absensi Berhasil!', `Selamat datang, ${employee.name}`, employee)
    } else {
      showError('Absensi Gagal', result.message || 'Terjadi kesalahan sistem')
    }
  } catch (error) {
    console.error('Attendance processing error:', error)
    showError('Kesalahan Jaringan', 'Tidak dapat terhubung ke server')
  }
}

const capturePhoto = (): Promise<string> => {
  return new Promise((resolve, reject) => {
    try {
      if (!videoElement.value) {
        console.error('Video element not found')
        reject(new Error('Video element not found'))
        return
      }
      
      if (!canvasElement.value) {
        console.error('Canvas element not found')
        reject(new Error('Canvas element not found'))
        return
      }
      
      // Check if video has loaded metadata
      if (videoElement.value.videoWidth === 0 || videoElement.value.videoHeight === 0) {
        console.error('Video dimensions not available')
        reject(new Error('Video dimensions not available'))
        return
      }
      
      // Ensure video is ready
      if (videoElement.value.readyState < 2) {
        console.error('Video not ready, readyState:', videoElement.value.readyState)
        reject(new Error('Video not ready'))
        return
      }
      
      const ctx = canvasElement.value.getContext('2d')
      if (!ctx) {
        console.error('Canvas context not found')
        reject(new Error('Canvas context not found'))
        return
      }
      
      // Set canvas size to match video
      const videoWidth = videoElement.value.videoWidth
      const videoHeight = videoElement.value.videoHeight
      
      canvasElement.value.width = videoWidth
      canvasElement.value.height = videoHeight
      
      console.log('Capturing photo with dimensions:', videoWidth, 'x', videoHeight)
      
      // Draw video frame to canvas
      ctx.drawImage(videoElement.value, 0, 0, videoWidth, videoHeight)
      
      // Convert to base64
      const dataURL = canvasElement.value.toDataURL('image/jpeg', 0.8)
      console.log('Photo captured successfully, size:', dataURL.length)
      resolve(dataURL)
    } catch (error) {
      console.error('Error capturing photo:', error)
      reject(error)
    }
  })
}

const showSuccess = (title: string, message: string, employee?: any) => {
  resultSuccess.value = true
  resultTitle.value = title
  resultMessage.value = message
  employeeData.value = employee
  showResult.value = true
}

const showError = (title: string, message: string) => {
  resultSuccess.value = false
  resultTitle.value = title
  resultMessage.value = message
  employeeData.value = null
  showResult.value = true
}

const closeResult = () => {
  showResult.value = false
  setTimeout(() => {
    resultSuccess.value = false
    resultTitle.value = ''
    resultMessage.value = ''
    employeeData.value = null
  }, 300)
}

const updateStatus = () => {
  const status = statusComputed.value
  statusText.value = status.text
  statusColor.value = status.color
  statusIcon.value = status.icon
}

const testCapturePhoto = async () => {
  try {
    console.log('Testing photo capture...')
    const photoData = await capturePhoto()
    console.log('Photo captured successfully!')
    console.log('Photo size:', photoData.length, 'bytes')
    console.log('Photo preview:', photoData.substring(0, 100) + '...')
    
    // Show success message
    showSuccess('Foto Berhasil!', `Foto berhasil diambil (${Math.round(photoData.length / 1024)} KB)`)
  } catch (error) {
    console.error('Test capture failed:', error)
    showError('Gagal Mengambil Foto', error.message || 'Tidak dapat mengambil foto')
  }
}

// Lifecycle
onMounted(() => {
  updateTime()
  setInterval(updateTime, 1000)
  initCamera()
})

onUnmounted(() => {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
  }
  if (detectionInterval) {
    clearInterval(detectionInterval)
  }
})
</script>

<style scoped>
.face-attendance-container {
  height: 100vh;
  width: 100vw;
  position: relative;
  overflow: hidden;
  background: #000;
}

.status-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
}

.status-chip {
  font-size: 16px;
  font-weight: 600;
}

.time-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  color: white;
}

.current-time {
  font-size: 24px;
  font-weight: bold;
  line-height: 1;
}

.current-date {
  font-size: 14px;
  opacity: 0.8;
}

.camera-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.camera-feed {
  width: 100%;
  height: 100%;
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

.action-container {
  position: absolute;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
}

.test-capture-container {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
}

.test-capture-btn {
  font-size: 14px;
  min-width: 150px;
}

.attendance-btn {
  font-size: 18px;
  font-weight: bold;
  min-width: 200px;
  height: 60px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.settings-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
  color: white;
}

.employee-info {
  padding: 16px;
  border-radius: 8px;
  background: rgba(var(--v-theme-surface), 0.1);
}

.employee-info h3 {
  margin: 8px 0 4px 0;
  font-weight: bold;
}
</style>
