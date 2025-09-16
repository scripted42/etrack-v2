<template>
  <div>
    <BaseBreadcrumb :title="'Dashboard'" :breadcrumb="breadcrumbs" />
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat Dashboard...</div>
    </div>
    
    <!-- Dashboard Content -->
    <div v-else>
      <!-- KPI Cards untuk Kepala Sekolah -->
      <v-row class="mb-6">
        <!-- Total Siswa dengan Utilisasi -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" color="primary" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:school</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.kpi?.total_students || 0 }}</div>
                <div class="text-body-2 text-white">Total Siswa</div>
                <div class="text-caption text-white">Utilisasi: {{ stats.kpi?.utilization_rate || 0 }}%</div>
              </div>
            </div>
          </v-card>
        </v-col>
      
        <!-- Rasio Guru-Siswa -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getRatioColor(stats.kpi?.teacher_student_ratio?.status || 'excellent')" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:account-supervisor</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.kpi?.teacher_student_ratio?.ratio || 0 }}:1</div>
                <div class="text-body-2 text-white">Rasio Guru-Siswa</div>
                <div class="text-caption text-white">{{ getRatioStatus(stats.kpi?.teacher_student_ratio?.status || 'excellent') }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
        
        <!-- Kualitas Data -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getQualityColor(stats.quality?.data_quality?.status || 'good')" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:chart-line-variant</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.quality?.data_quality?.completeness_rate || 0 }}%</div>
                <div class="text-body-2 text-white">Kelengkapan Data</div>
                <div class="text-caption text-white">{{ getQualityStatus(stats.quality?.data_quality?.status || 'good') }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
        
        <!-- Keamanan Sistem -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getSecurityColor(stats.system?.activity?.security_score || 0)" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:shield-check</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.system?.activity?.security_score || 0 }}</div>
                <div class="text-body-2 text-white">Security Score</div>
                <div class="text-caption text-white">{{ getSecurityStatus(stats.system?.activity?.security_score || 0) }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
    </v-row>

      <!-- Alerts Section -->
      <v-row class="mb-6" v-if="stats.system?.alerts && stats.system.alerts.length > 0">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:alert-circle</v-icon>
              Notifikasi Penting
            </v-card-title>
            <v-card-text>
              <v-alert
                v-for="alert in stats.system.alerts"
                :key="alert.title"
                :type="alert.type"
                :color="getAlertColor(alert.type)"
                variant="tonal"
                class="mb-3"
              >
                <div class="d-flex align-center">
                  <v-icon class="mr-2">{{ getAlertIcon(alert.type) }}</v-icon>
                  <div>
                    <div class="font-weight-bold">{{ alert.title }}</div>
                    <div class="text-body-2">{{ alert.message }}</div>
                  </div>
                  <v-spacer></v-spacer>
                  <v-chip :color="getPriorityColor(alert.priority)" size="small">
                    {{ getPriorityText(alert.priority) }}
                  </v-chip>
                </div>
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Charts Row - Commented out for now -->
      <!-- <v-row class="mb-6">
        <v-col cols="12" lg="8">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:chart-line</v-icon>
              Pertumbuhan Data (12 Bulan Terakhir)
            </v-card-title>
            <v-card-text>
              <div style="height: 400px;">
                <LineChart 
                  :data="monthlyChartData" 
                  :options="monthlyChartOptions"
                  v-if="!loading"
                />
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:chart-pie</v-icon>
              Distribusi Status Siswa
            </v-card-title>
            <v-card-text>
              <div style="height: 300px;">
                <DoughnutChart 
                  :data="studentsStatusChartData" 
                  :options="doughnutChartOptions"
                  v-if="!loading"
                />
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row> -->

    <!-- Growth Statistics -->
    <v-row class="mb-6">
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi:trending-up</v-icon>
            Pertumbuhan Siswa
          </v-card-title>
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-4">
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.growth_stats.students.current_year }}</div>
                <div class="text-body-2 text-grey">Tahun Ini</div>
              </div>
              <div class="text-right">
                <div class="text-h4 font-weight-bold" :class="growthColor(stats.growth_stats.students.growth_percentage)">
                  {{ stats.growth_stats.students.growth_percentage }}%
                </div>
                <div class="text-body-2 text-grey">vs Tahun Lalu</div>
              </div>
            </div>
            <v-progress-linear 
              :model-value="Math.abs(stats.growth_stats.students.growth_percentage)" 
              :color="growthColor(stats.growth_stats.students.growth_percentage)"
              height="8"
              rounded
            />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi:trending-up</v-icon>
            Pertumbuhan Pegawai
          </v-card-title>
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-4">
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.growth_stats.employees.current_year }}</div>
                <div class="text-body-2 text-grey">Tahun Ini</div>
              </div>
              <div class="text-right">
                <div class="text-h4 font-weight-bold" :class="growthColor(stats.growth_stats.employees.growth_percentage)">
                  {{ stats.growth_stats.employees.growth_percentage }}%
                </div>
                <div class="text-body-2 text-grey">vs Tahun Lalu</div>
              </div>
            </div>
            <v-progress-linear 
              :model-value="Math.abs(stats.growth_stats.employees.growth_percentage)" 
              :color="growthColor(stats.growth_stats.employees.growth_percentage)"
              height="8"
              rounded
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- System Health & Recent Activities -->
    <v-row>
      <!-- System Health -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi:heart-pulse</v-icon>
            Status Sistem
          </v-card-title>
          <v-card-text>
            <div class="mb-4">
              <div class="d-flex justify-space-between align-center mb-2">
                <span>Database</span>
                <v-chip :color="health.database === 'healthy' ? 'success' : 'error'" size="small">
                  {{ health.database === 'healthy' ? 'Sehat' : 'Error' }}
                </v-chip>
              </div>
              
              <div class="d-flex justify-space-between align-center mb-2">
                <span>Storage Usage</span>
                <span>{{ health.storage.usage_percentage }}%</span>
              </div>
              <v-progress-linear 
                :model-value="health.storage.usage_percentage" 
                :color="health.storage.usage_percentage > 80 ? 'error' : health.storage.usage_percentage > 60 ? 'warning' : 'success'"
                height="8"
                rounded
              />
              
              <div class="d-flex justify-space-between align-center mb-2">
                <span>Error Logs</span>
                <v-chip :color="health.errors > 10 ? 'error' : health.errors > 5 ? 'warning' : 'success'" size="small">
                  {{ health.errors }}
                </v-chip>
              </div>
              
              <div class="text-caption text-grey">
                Terakhir diperbarui: {{ formatDate(health.last_updated) }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Recent Activities -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi:history</v-icon>
            Aktivitas Terbaru
          </v-card-title>
          <v-card-text>
            <div v-if="stats.recent_activities.length === 0" class="text-center text-grey py-4">
              Tidak ada aktivitas terbaru
            </div>
            <div v-else>
              <div 
                v-for="activity in stats.recent_activities.slice(0, 5)" 
                :key="activity.id"
                class="d-flex align-center mb-3"
              >
                <v-avatar size="32" color="primary" variant="tonal" class="mr-3">
                  <v-icon size="16">mdi:account</v-icon>
                </v-avatar>
                <div class="flex-grow-1">
                  <div class="text-body-2 font-weight-medium">{{ activity.user }}</div>
                  <div class="text-caption text-grey">{{ activity.action }}</div>
                  <div class="text-caption text-grey">{{ activity.time_ago }}</div>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div> <!-- End Dashboard Content -->
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import { fetchDashboardStats, fetchSystemHealth, type DashboardStats, type SystemHealth } from '@/services/dashboard';
// Chart.js imports - commented out for now
// import { Line, Doughnut } from 'vue-chartjs';
// import {
//   Chart as ChartJS,
//   CategoryScale,
//   LinearScale,
//   PointElement,
//   LineElement,
//   Title,
//   Tooltip,
//   Legend,
//   ArcElement
// } from 'chart.js';

// Register Chart.js components
// ChartJS.register(
//   CategoryScale,
//   LinearScale,
//   PointElement,
//   LineElement,
//   Title,
//   Tooltip,
//   Legend,
//   ArcElement
// );

const breadcrumbs = [
  { title: 'Dashboard', to: '/dashboard' }
];

const loading = ref(true);
const stats = ref<any>({
  kpi: {
    total_students: 0,
    total_employees: 0,
    utilization_rate: 0,
    max_capacity: 500,
    teacher_student_ratio: { ratio: 0, status: 'excellent' }
  },
  distribution: {
    students_by_class: {},
    students_by_level: {},
    employee_analysis: {}
  },
  quality: {
    data_quality: { completeness_rate: 0, status: 'good' },
    performance_indicators: {}
  },
  trends: {
    growth_trend: { months: [], student_counts: [], trend_direction: 'stable' },
    monthly_stats: { months: [], students: [], employees: [] }
  },
  system: {
    activity: { security_score: 0 },
    alerts: []
  },
  // Legacy data
  overview: { total_students: 0, total_employees: 0, total_users: 0, active_users: 0 }
});

const health = ref<SystemHealth>({
  database: 'healthy',
  storage: { total_space: '0 B', used_space: '0 B', free_space: '0 B', usage_percentage: 0 },
  errors: 0,
  last_updated: ''
});

// Chart components - commented out for now
// const LineChart = Line;
// const DoughnutChart = Doughnut;

// Monthly chart data
const monthlyChartData = computed(() => ({
  labels: stats.value.monthly_stats.months,
  datasets: [
    {
      label: 'Siswa',
      data: stats.value.monthly_stats.students,
      borderColor: 'rgb(75, 192, 192)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.1
    },
    {
      label: 'Pegawai',
      data: stats.value.monthly_stats.employees,
      borderColor: 'rgb(255, 99, 132)',
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      tension: 0.1
    }
  ]
}));

const monthlyChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
    title: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
    },
  },
};

// Students status chart data
const studentsStatusChartData = computed(() => {
  const statusData = stats.value.students_by_status;
  const labels = Object.keys(statusData);
  const data = Object.values(statusData);
  
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: [
          '#4CAF50', // Green for aktif
          '#FF9800', // Orange for cuti
          '#F44336', // Red for nonaktif
          '#9C27B0', // Purple for lulus
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }
    ]
  };
});

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
    },
  },
};

// Helper functions
function growthColor(percentage: number): string {
  if (percentage > 0) return 'text-success';
  if (percentage < 0) return 'text-error';
  return 'text-grey';
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('id-ID');
}

// Helper functions untuk KPI
function getRatioColor(status: string): string {
  switch (status) {
    case 'excellent': return 'success';
    case 'good': return 'info';
    case 'needs_improvement': return 'warning';
    default: return 'grey';
  }
}

function getRatioStatus(status: string): string {
  switch (status) {
    case 'excellent': return 'Sangat Baik';
    case 'good': return 'Baik';
    case 'needs_improvement': return 'Perlu Perbaikan';
    default: return 'Tidak Diketahui';
  }
}

function getQualityColor(status: string): string {
  switch (status) {
    case 'excellent': return 'success';
    case 'good': return 'info';
    case 'needs_improvement': return 'warning';
    default: return 'grey';
  }
}

function getQualityStatus(status: string): string {
  switch (status) {
    case 'excellent': return 'Sangat Lengkap';
    case 'good': return 'Cukup Lengkap';
    case 'needs_improvement': return 'Perlu Dilengkapi';
    default: return 'Tidak Diketahui';
  }
}

function getSecurityColor(score: number): string {
  if (score >= 80) return 'success';
  if (score >= 60) return 'info';
  if (score >= 40) return 'warning';
  return 'error';
}

function getSecurityStatus(score: number): string {
  if (score >= 80) return 'Sangat Aman';
  if (score >= 60) return 'Aman';
  if (score >= 40) return 'Perhatian';
  return 'Risiko Tinggi';
}

// Helper functions untuk alerts
function getAlertColor(type: string): string {
  switch (type) {
    case 'error': return 'error';
    case 'warning': return 'warning';
    case 'info': return 'info';
    default: return 'grey';
  }
}

function getAlertIcon(type: string): string {
  switch (type) {
    case 'error': return 'mdi:alert-circle';
    case 'warning': return 'mdi:alert';
    case 'info': return 'mdi:information';
    default: return 'mdi:help-circle';
  }
}

function getPriorityColor(priority: string): string {
  switch (priority) {
    case 'high': return 'error';
    case 'medium': return 'warning';
    case 'low': return 'info';
    default: return 'grey';
  }
}

function getPriorityText(priority: string): string {
  switch (priority) {
    case 'high': return 'Tinggi';
    case 'medium': return 'Sedang';
    case 'low': return 'Rendah';
    default: return 'Tidak Diketahui';
  }
}

// Load data
async function loadDashboardData() {
  loading.value = true;
  try {
    const [statsResponse, healthResponse] = await Promise.all([
      fetchDashboardStats(),
      fetchSystemHealth()
    ]);
    
    if (statsResponse.success) {
      stats.value = statsResponse.data;
    }
    
    if (healthResponse.success) {
      health.value = healthResponse.data;
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadDashboardData();
});
</script>

<style scoped>
.v-card {
  transition: all 0.3s ease;
}

.v-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>
