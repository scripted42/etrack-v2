<template>
  <div>
    <div class="mb-4">
      <h1>Dashboard</h1>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat Dashboard...</div>
    </div>
    
    <!-- Dashboard Content -->
    <div v-else>
      <!-- KPI Cards ISO 9001 -->
      <v-row class="mb-6">
        <!-- Kapasitas Sekolah -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getCapacityColor(stats.kpi?.utilization_rate || 0)" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:school</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.kpi?.total_students || 0 }}</div>
                <div class="text-body-2 text-white">Total Siswa</div>
                <div class="text-caption text-white">Kapasitas: {{ stats.kpi?.max_capacity || 500 }} ({{ stats.kpi?.utilization_rate || 0 }}%)</div>
                <div class="text-caption text-white">{{ getCapacityStatus(stats.kpi?.utilization_rate || 0) }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
        
        <!-- Rasio Guru-Siswa (ISO 9001) -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getRatioColor(stats.kpi?.teacher_student_ratio?.status || 'excellent')" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:account-supervisor</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.kpi?.teacher_student_ratio?.ratio || 0 }}:1</div>
                <div class="text-body-2 text-white">Rasio Guru-Siswa</div>
                <div class="text-caption text-white">Standar ISO: ≤20:1</div>
                <div class="text-caption text-white">{{ getRatioStatus(stats.kpi?.teacher_student_ratio?.status || 'excellent') }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
        
        <!-- Kualitas Data (ISO 9001) -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getQualityColor(stats.quality?.data_quality?.status || 'good')" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:chart-line-variant</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.quality?.data_quality?.completeness_rate || 0 }}%</div>
                <div class="text-body-2 text-white">Kelengkapan Data</div>
                <div class="text-caption text-white">Standar ISO: ≥90%</div>
                <div class="text-caption text-white">{{ getQualityStatus(stats.quality?.data_quality?.status || 'good') }}</div>
              </div>
            </div>
          </v-card>
        </v-col>
        
        <!-- Sistem Keamanan (ISO 9001) -->
        <v-col cols="12" sm="6" md="3">
          <v-card class="pa-4" :color="getSecurityColor(stats.system?.activity?.security_score || 0)" variant="flat">
            <div class="d-flex align-center">
              <v-icon size="40" color="white" class="mr-4">mdi:shield-check</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-white">{{ stats.system?.activity?.security_score || 0 }}</div>
                <div class="text-body-2 text-white">Security Score</div>
                <div class="text-caption text-white">Standar ISO: ≥80</div>
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

      <!-- ISO 9001 Compliance Status -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:certificate</v-icon>
              Status Kepatuhan ISO 9001
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <div class="text-center">
                    <v-progress-circular
                      :model-value="getISOComplianceScore()"
                      :color="getISOComplianceColor()"
                      size="80"
                      width="8"
                    >
                      {{ getISOComplianceScore() }}%
                    </v-progress-circular>
                    <div class="text-h6 mt-2">{{ getISOComplianceStatus() }}</div>
                    <div class="text-caption text-grey">Overall Compliance</div>
                  </div>
                </v-col>
                <v-col cols="12" md="9">
                  <div class="d-flex flex-wrap gap-2">
                    <v-chip :color="getCapacityColor(stats.kpi?.utilization_rate || 0)" size="large">
                      <v-icon start>mdi:school</v-icon>
                      Kapasitas: {{ getCapacityStatus(stats.kpi?.utilization_rate || 0) }}
                    </v-chip>
                    <v-chip :color="getRatioColor(stats.kpi?.teacher_student_ratio?.status || 'excellent')" size="large">
                      <v-icon start>mdi:account-supervisor</v-icon>
                      Rasio: {{ getRatioStatus(stats.kpi?.teacher_student_ratio?.status || 'excellent') }}
                    </v-chip>
                    <v-chip :color="getQualityColor(stats.quality?.data_quality?.status || 'good')" size="large">
                      <v-icon start>mdi:chart-line-variant</v-icon>
                      Data: {{ getQualityStatus(stats.quality?.data_quality?.status || 'good') }}
                    </v-chip>
                    <v-chip :color="getSecurityColor(stats.system?.activity?.security_score || 0)" size="large">
                      <v-icon start>mdi:shield-check</v-icon>
                      Security: {{ getSecurityStatus(stats.system?.activity?.security_score || 0) }}
                    </v-chip>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Performance Indicators -->
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:chart-line</v-icon>
              Indikator Kinerja Utama
            </v-card-title>
            <v-card-text>
              <div v-if="stats.quality?.performance_indicators">
                <div class="d-flex justify-space-between align-center mb-3">
                  <span class="text-body-1">Kelengkapan Data</span>
                  <div class="d-flex align-center">
                    <v-progress-linear
                      :model-value="stats.quality.performance_indicators.data_completeness"
                      color="success"
                      height="8"
                      rounded
                      class="mr-2"
                      style="width: 100px;"
                    />
                    <span class="text-body-2 font-weight-bold">{{ stats.quality.performance_indicators.data_completeness }}%</span>
                  </div>
                </div>
                <div class="d-flex justify-space-between align-center mb-3">
                  <span class="text-body-1">Tingkat Aktivitas</span>
                  <div class="d-flex align-center">
                    <v-progress-linear
                      :model-value="stats.quality.performance_indicators.activity_rate"
                      color="info"
                      height="8"
                      rounded
                      class="mr-2"
                      style="width: 100px;"
                    />
                    <span class="text-body-2 font-weight-bold">{{ stats.quality.performance_indicators.activity_rate }}%</span>
                  </div>
                </div>
                <div class="d-flex justify-space-between align-center mb-3">
                  <span class="text-body-1">Utilisasi Kapasitas</span>
                  <div class="d-flex align-center">
                    <v-progress-linear
                      :model-value="stats.quality.performance_indicators.utilization_rate"
                      color="primary"
                      height="8"
                      rounded
                      class="mr-2"
                      style="width: 100px;"
                    />
                    <span class="text-body-2 font-weight-bold">{{ stats.quality.performance_indicators.utilization_rate }}%</span>
                  </div>
                </div>
                <div class="d-flex justify-space-between align-center mb-3">
                  <span class="text-body-1">Kesehatan Sistem</span>
                  <div class="d-flex align-center">
                    <v-progress-linear
                      :model-value="stats.quality.performance_indicators.system_health"
                      color="success"
                      height="8"
                      rounded
                      class="mr-2"
                      style="width: 100px;"
                    />
                    <span class="text-body-2 font-weight-bold">{{ stats.quality.performance_indicators.system_health }}%</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                Data tidak tersedia
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:account-group</v-icon>
              Distribusi Siswa per Tingkat
            </v-card-title>
            <v-card-text>
              <div v-if="stats.distribution?.students_by_level">
                <div v-for="(count, level) in stats.distribution.students_by_level" :key="level" class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">{{ getLevelName(level) }}</span>
                  <div class="d-flex align-center">
                    <v-progress-linear
                      :model-value="(count / stats.kpi?.total_students) * 100"
                      color="primary"
                      height="6"
                      rounded
                      class="mr-2"
                      style="width: 80px;"
                    />
                    <v-chip color="primary" size="small">{{ count }} siswa</v-chip>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                Data tidak tersedia
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Employee Analysis -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:chart-pie</v-icon>
              Analisis Kepegawaian
            </v-card-title>
            <v-card-text>
              <div v-if="stats.distribution?.employee_analysis">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Total Pegawai</span>
                  <v-chip color="info" size="small">{{ stats.distribution.employee_analysis.total }}</v-chip>
                </div>
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Aktif</span>
                  <v-chip color="success" size="small">{{ stats.distribution.employee_analysis.active }}</v-chip>
                </div>
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Guru</span>
                  <v-chip color="primary" size="small">{{ stats.distribution.employee_analysis.teachers }}</v-chip>
                </div>
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Staff</span>
                  <v-chip color="secondary" size="small">{{ stats.distribution.employee_analysis.staff }}</v-chip>
                </div>
                
                <!-- Breakdown Staff -->
                <div v-if="stats.distribution.employee_analysis.staff_breakdown" class="mt-3">
                  <div class="text-caption text-grey mb-2">Detail Staff:</div>
                  <div class="d-flex justify-space-between align-center mb-1" v-if="stats.distribution.employee_analysis.staff_breakdown.tata_usaha > 0">
                    <span class="text-body-2">Tata Usaha</span>
                    <v-chip color="info" size="x-small">{{ stats.distribution.employee_analysis.staff_breakdown.tata_usaha }}</v-chip>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-1" v-if="stats.distribution.employee_analysis.staff_breakdown.humas > 0">
                    <span class="text-body-2">Humas</span>
                    <v-chip color="warning" size="x-small">{{ stats.distribution.employee_analysis.staff_breakdown.humas }}</v-chip>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-1" v-if="stats.distribution.employee_analysis.staff_breakdown.security > 0">
                    <span class="text-body-2">Security</span>
                    <v-chip color="error" size="x-small">{{ stats.distribution.employee_analysis.staff_breakdown.security }}</v-chip>
                  </div>
                  <div class="d-flex justify-space-between align-center mb-1" v-if="stats.distribution.employee_analysis.staff_breakdown.lainnya > 0">
                    <span class="text-body-2">Lainnya</span>
                    <v-chip color="grey" size="x-small">{{ stats.distribution.employee_analysis.staff_breakdown.lainnya }}</v-chip>
                  </div>
                </div>
                
                <v-divider class="my-3"></v-divider>
                <div class="text-center">
                  <div class="text-h6">{{ stats.distribution.employee_analysis.active_percentage }}%</div>
                  <div class="text-caption text-grey">Tingkat Keaktifan Pegawai</div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                Data tidak tersedia
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi:trending-up</v-icon>
              Trend Pertumbuhan
            </v-card-title>
            <v-card-text>
              <div v-if="stats.trends?.growth_trend">
                <div class="text-center mb-3">
                  <v-icon :color="getTrendColor(stats.trends.growth_trend.trend_direction)" size="40">
                    {{ getTrendIcon(stats.trends.growth_trend.trend_direction) }}
                  </v-icon>
                  <div class="text-h6 mt-2">{{ getTrendText(stats.trends.growth_trend.trend_direction) }}</div>
                </div>
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-body-1">Bulan Terakhir</span>
                  <v-chip color="primary" size="small">{{ stats.trends.growth_trend.student_counts[stats.trends.growth_trend.student_counts.length - 1] }} siswa baru</v-chip>
                </div>
                <div class="text-caption text-grey text-center">
                  Data 6 bulan terakhir menunjukkan {{ stats.trends.growth_trend.trend_direction }} trend
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                Data tidak tersedia
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
import { fetchDashboardStats } from '@/services/dashboard';

// Breadcrumbs removed for simplicity

const loading = ref(true);
const stats = ref<any>({
  kpi: {
    total_students: 0,
    total_employees: 0,
    utilization_rate: 0
  },
  distribution: {
    students_by_level: {},
    employee_analysis: {}
  },
  quality: {
    data_quality: { completeness_rate: 0, status: 'good' }
  },
  system: {
    activity: { security_score: 0 },
    alerts: []
  }
});


// Helper functions
function getLevelName(level: string): string {
  const levelNames: Record<string, string> = {
    'VII': 'Kelas VII (7)',
    'VIII': 'Kelas VIII (8)', 
    'IX': 'Kelas IX (9)'
  };
  return levelNames[level] || `Kelas ${level}`;
}

function getQualityStatus(status: string): string {
  switch (status) {
    case 'excellent': return 'Sangat Lengkap';
    case 'good': return 'Cukup Lengkap';
    case 'needs_improvement': return 'Perlu Dilengkapi';
    default: return 'Tidak Diketahui';
  }
}

function getSecurityStatus(score: number): string {
  if (score >= 80) return 'Sangat Aman';
  if (score >= 60) return 'Aman';
  if (score >= 40) return 'Perhatian';
  return 'Risiko Tinggi';
}

function getCapacityColor(utilization: number): string {
  if (utilization >= 90) return 'error';
  if (utilization >= 70) return 'warning';
  if (utilization >= 30) return 'success';
  return 'info';
}

function getCapacityStatus(utilization: number): string {
  if (utilization >= 90) return 'Hampir Penuh';
  if (utilization >= 70) return 'Cukup Penuh';
  if (utilization >= 30) return 'Optimal';
  return 'Sangat Rendah';
}

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

function getSecurityColor(score: number): string {
  if (score >= 80) return 'success';
  if (score >= 60) return 'info';
  if (score >= 40) return 'warning';
  return 'error';
}

// ISO 9001 Compliance functions
function getISOComplianceScore(): number {
  let score = 0;
  let total = 0;
  
  // Kapasitas (20% weight)
  const utilization = stats.value.kpi?.utilization_rate || 0;
  if (utilization >= 30 && utilization <= 90) score += 20;
  else if (utilization >= 20 && utilization <= 95) score += 15;
  else score += 5;
  total += 20;
  
  // Rasio Guru-Siswa (25% weight)
  const ratioStatus = stats.value.kpi?.teacher_student_ratio?.status || 'excellent';
  if (ratioStatus === 'excellent') score += 25;
  else if (ratioStatus === 'good') score += 20;
  else score += 10;
  total += 25;
  
  // Kualitas Data (25% weight)
  const dataQuality = stats.value.quality?.data_quality?.completeness_rate || 0;
  if (dataQuality >= 90) score += 25;
  else if (dataQuality >= 70) score += 20;
  else score += 10;
  total += 25;
  
  // Security (30% weight)
  const securityScore = stats.value.system?.activity?.security_score || 0;
  if (securityScore >= 80) score += 30;
  else if (securityScore >= 60) score += 20;
  else score += 10;
  total += 30;
  
  return Math.round((score / total) * 100);
}

function getISOComplianceColor(): string {
  const score = getISOComplianceScore();
  if (score >= 80) return 'success';
  if (score >= 60) return 'info';
  if (score >= 40) return 'warning';
  return 'error';
}

function getISOComplianceStatus(): string {
  const score = getISOComplianceScore();
  if (score >= 80) return 'Sangat Baik';
  if (score >= 60) return 'Baik';
  if (score >= 40) return 'Perlu Perbaikan';
  return 'Kritis';
}

// Trend functions
function getTrendColor(direction: string): string {
  switch (direction) {
    case 'increasing': return 'success';
    case 'decreasing': return 'error';
    default: return 'info';
  }
}

function getTrendIcon(direction: string): string {
  switch (direction) {
    case 'increasing': return 'mdi:trending-up';
    case 'decreasing': return 'mdi:trending-down';
    default: return 'mdi:trending-neutral';
  }
}

function getTrendText(direction: string): string {
  switch (direction) {
    case 'increasing': return 'Meningkat';
    case 'decreasing': return 'Menurun';
    default: return 'Stabil';
  }
}

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
    console.log('Loading dashboard data...');
    
    const statsResponse = await fetchDashboardStats();
    
    console.log('Dashboard stats response:', statsResponse);
    
    if (statsResponse.success) {
      stats.value = statsResponse.data;
      console.log('Stats loaded successfully:', stats.value);
    } else {
      console.error('Failed to load stats:', statsResponse);
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
    // Set default values if API fails
    stats.value = {
      kpi: {
        total_students: 0,
        total_employees: 0,
        utilization_rate: 0,
        max_capacity: 500,
        teacher_student_ratio: { ratio: 0, students: 0, teachers: 0, status: 'excellent' }
      },
      distribution: {
        students_by_class: {},
        students_by_level: {},
        employee_analysis: { 
          total: 0, 
          active: 0, 
          teachers: 0, 
          staff: 0, 
          staff_breakdown: { tata_usaha: 0, humas: 0, security: 0, lainnya: 0 },
          active_percentage: 0 
        }
      },
      quality: {
        data_quality: { completeness_rate: 0, complete_records: 0, incomplete_records: 0, status: 'good' },
        performance_indicators: { data_completeness: 0, activity_rate: 0, utilization_rate: 0, system_health: 0 }
      },
      trends: {
        growth_trend: { months: [], student_counts: [], trend_direction: 'stable' },
        monthly_stats: { months: [], students: [], employees: [] }
      },
      system: {
        activity: { activity_24h: 0, activity_7d: 0, successful_logins: 0, failed_logins: 0, security_score: 0 },
        alerts: []
      },
      overview: { total_students: 0, total_employees: 0, total_users: 0, active_users: 0 }
    };
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
