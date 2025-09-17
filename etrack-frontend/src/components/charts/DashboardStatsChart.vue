<template>
  <v-card class="pa-4" elevation="2">
    <v-card-title class="d-flex align-center mb-4">
      <v-icon color="primary" class="mr-2">mdi-chart-pie</v-icon>
      <span>Statistik Data</span>
    </v-card-title>
    
    <div v-if="loading" class="d-flex justify-center align-center" style="height: 300px;">
      <v-progress-circular indeterminate color="primary" />
    </div>
    
    <div v-else-if="error" class="d-flex justify-center align-center" style="height: 300px;">
      <v-alert type="error" variant="tonal">
        <div class="text-center">
          <v-icon class="mb-2">mdi-alert-circle</v-icon>
          <div>{{ error }}</div>
        </div>
      </v-alert>
    </div>
    
    <div v-else>
      <!-- Statistics Cards -->
      <v-row class="mb-4">
        <v-col cols="6" md="3" v-for="stat in statistics" :key="stat.label">
          <v-card variant="tonal" :color="stat.color" class="pa-3 text-center">
            <v-icon :color="stat.color" size="large" class="mb-2">{{ stat.icon }}</v-icon>
            <div class="text-h6 font-weight-bold">{{ stat.value }}</div>
            <div class="text-caption">{{ stat.label }}</div>
          </v-card>
        </v-col>
      </v-row>
      
      <!-- Pie Chart -->
      <div style="height: 300px;">
        <canvas ref="chartCanvas"></canvas>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import {
  Chart,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import api from '@/services/api'

// Register Chart.js components
Chart.register(ArcElement, Tooltip, Legend)

// API instance is imported directly
const chartCanvas = ref<HTMLCanvasElement>()
const loading = ref(true)
const error = ref('')
let chartInstance: Chart | null = null

// Statistics data
const statistics = ref([
  {
    label: 'Total Siswa',
    value: 0,
    icon: 'mdi-account-group',
    color: 'primary'
  },
  {
    label: 'Total Pegawai',
    value: 0,
    icon: 'mdi-account-tie',
    color: 'success'
  },
  {
    label: 'Siswa Aktif',
    value: 0,
    icon: 'mdi-account-check',
    color: 'info'
  },
  {
    label: 'Pegawai Aktif',
    value: 0,
    icon: 'mdi-account-star',
    color: 'warning'
  }
])

// Chart data
const chartData = ref({
  labels: ['Siswa', 'Pegawai'],
  datasets: [
    {
      data: [0, 0],
      backgroundColor: [
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 99, 132, 0.8)'
      ],
      borderColor: [
        'rgba(54, 162, 235, 1)',
        'rgba(255, 99, 132, 1)'
      ],
      borderWidth: 2
    }
  ]
})

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
    },
    tooltip: {
      callbacks: {
        label: function(context: any) {
          const label = context.label || ''
          const value = context.parsed
          const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0)
          const percentage = ((value / total) * 100).toFixed(1)
          return `${label}: ${value} (${percentage}%)`
        }
      }
    }
  }
}

// Fetch data
const fetchData = async () => {
  try {
    loading.value = true
    error.value = ''
    
    // Fetch dashboard data
    const response = await api.get('/dashboard')
    
    if (response.data.success) {
      const data = response.data.data
      
      // Update statistics
      statistics.value[0].value = data.total_students || 0
      statistics.value[1].value = data.total_employees || 0
      statistics.value[2].value = data.active_students || 0
      statistics.value[3].value = data.active_employees || 0
      
      // Update chart data
      chartData.value.datasets[0].data = [
        data.total_students || 0,
        data.total_employees || 0
      ]
      
      await nextTick()
      createChart()
    }
    
  } catch (err: any) {
    console.error('Error fetching dashboard data:', err)
    
    // Use sample data if API fails
    statistics.value[0].value = 150
    statistics.value[1].value = 25
    statistics.value[2].value = 145
    statistics.value[3].value = 23
    
    chartData.value.datasets[0].data = [150, 25]
    
    await nextTick()
    createChart()
  } finally {
    loading.value = false
  }
}

// Create chart
const createChart = () => {
  if (!chartCanvas.value) return
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: chartData.value,
    options: chartOptions
  })
}

// Lifecycle
onMounted(() => {
  fetchData()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}

.statistics-card {
  transition: transform 0.2s ease-in-out;
}

.statistics-card:hover {
  transform: translateY(-2px);
}
</style>
