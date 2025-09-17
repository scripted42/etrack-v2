<template>
  <v-card class="pa-4" elevation="2">
    <v-card-title class="d-flex align-center mb-4">
      <v-icon color="primary" class="mr-2">mdi-chart-line</v-icon>
      <span>Pertumbuhan Siswa per Tahun</span>
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
    
    <div v-else style="height: 300px;">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import api from '@/services/api'

// Register Chart.js components
Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

// API instance is imported directly
const chartCanvas = ref<HTMLCanvasElement>()
const loading = ref(true)
const error = ref('')
let chartInstance: Chart | null = null

// Chart data
const chartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Total Siswa',
      data: [] as number[],
      borderColor: 'rgb(75, 192, 192)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.4,
      fill: true
    },
    {
      label: 'Siswa Baru',
      data: [] as number[],
      borderColor: 'rgb(255, 99, 132)',
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      tension: 0.4,
      fill: true
    }
  ]
})

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
    title: {
      display: false
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false,
    }
  },
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Tahun'
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: 'Jumlah Siswa'
      },
      beginAtZero: true
    }
  },
  interaction: {
    mode: 'nearest' as const,
    axis: 'x' as const,
    intersect: false
  }
}

// Fetch data
const fetchData = async () => {
  try {
    loading.value = true
    error.value = ''
    
    // Generate sample data for demonstration
    const currentYear = new Date().getFullYear()
    const years = []
    const totalStudents = []
    const newStudents = []
    
    for (let i = 5; i >= 0; i--) {
      const year = currentYear - i
      years.push(year.toString())
      
      // Simulate data growth
      const baseTotal = 200 + (5 - i) * 50
      const baseNew = 30 + (5 - i) * 10
      
      totalStudents.push(baseTotal + Math.random() * 20)
      newStudents.push(baseNew + Math.random() * 10)
    }
    
    chartData.value = {
      labels: years,
      datasets: [
        {
          label: 'Total Siswa',
          data: totalStudents,
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Siswa Baru',
          data: newStudents,
          borderColor: 'rgb(255, 99, 132)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          tension: 0.4,
          fill: true
        }
      ]
    }
    
    // Wait for DOM update
    await nextTick()
    createChart()
    
  } catch (err: any) {
    console.error('Error fetching chart data:', err)
    error.value = err.message || 'Gagal memuat data chart'
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
    type: 'line',
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
</style>
