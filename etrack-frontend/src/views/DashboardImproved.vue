<template>
  <div class="pa-4">
    <div class="mb-4">
      <h1 class="text-h4 font-weight-bold">Dashboard Sekolah</h1>
      <p class="text-body-2 text-grey">Overview sistem manajemen sekolah SMPN</p>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat data dashboard...</div>
    </div>
    
    <!-- Dashboard Content -->
    <div v-else>
      <!-- Quick Stats dengan Penjelasan -->
      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="primary" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:school</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.kpi?.total_students || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Total Siswa</div>
                <div class="text-caption text-grey">Siswa aktif di sekolah</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi:check-circle</v-icon>
                  Data terbaru
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="success" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:account-group</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.kpi?.total_employees || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Total Pegawai</div>
                <div class="text-caption text-grey">Guru dan staff sekolah</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi:check-circle</v-icon>
                  Semua aktif
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="info" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:chart-line</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.kpi?.utilization_rate || 0 }}%</div>
                <div class="text-body-2 font-weight-medium">Kapasitas Sekolah</div>
                <div class="text-caption text-grey">Dari {{ stats.kpi?.max_capacity || 500 }} siswa</div>
                <div class="text-caption text-info mt-1">
                  <v-icon size="16" class="mr-1">mdi:information</v-icon>
                  Masih banyak ruang
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="warning" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:account-supervisor</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.kpi?.teacher_student_ratio?.ratio || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Rasio Guru-Siswa</div>
                <div class="text-caption text-grey">Standar ISO: ≤20:1</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi:star</v-icon>
                  Sangat baik
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Informasi Detail dengan Penjelasan -->
      <v-row class="mb-6">
        <!-- Distribusi Siswa dengan Penjelasan -->
        <v-col cols="12" md="6">
          <v-card class="h-100">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi:chart-pie</v-icon>
              Distribusi Siswa per Tingkat
              <v-spacer></v-spacer>
              <v-chip color="primary" variant="tonal" size="small">
                {{ stats.kpi?.total_students || 0 }} total
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="stats.distribution?.students_by_level">
                <div v-for="(count, level) in stats.distribution.students_by_level" :key="level" class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">{{ getLevelName(level) }}</div>
                      <div class="text-caption text-grey">{{ getLevelDescription(level) }}</div>
                    </div>
                    <v-chip :color="getLevelColor(level)" size="small">
                      {{ count }} siswa
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(count / stats.kpi?.total_students) * 100"
                    :color="getLevelColor(level)"
                    height="8"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((count / stats.kpi?.total_students) * 100).toFixed(1) }}% dari total siswa
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:chart-pie-outline</v-icon>
                <div class="text-h6 mt-2">Belum ada data siswa</div>
                <div class="text-caption">Data distribusi akan muncul setelah ada siswa</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <!-- Analisis Kepegawaian dengan Penjelasan -->
        <v-col cols="12" md="6">
          <v-card class="h-100">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="success">mdi:account-group</v-icon>
              Analisis Kepegawaian
              <v-spacer></v-spacer>
              <v-chip color="success" variant="tonal" size="small">
                {{ stats.distribution?.employee_analysis?.active_percentage || 0 }}% aktif
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="stats.distribution?.employee_analysis">
                <!-- Total Pegawai -->
                <div class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">Total Pegawai</div>
                      <div class="text-caption text-grey">Semua karyawan sekolah</div>
                    </div>
                    <v-chip color="primary" size="small">
                      {{ stats.distribution.employee_analysis.total }} orang
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="100"
                    color="primary"
                    height="6"
                    rounded
                  />
                </div>
                
                <!-- Guru -->
                <div class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">Guru</div>
                      <div class="text-caption text-grey">Tenaga pendidik</div>
                    </div>
                    <v-chip color="success" size="small">
                      {{ stats.distribution.employee_analysis.teachers }} orang
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(stats.distribution.employee_analysis.teachers / stats.distribution.employee_analysis.total) * 100"
                    color="success"
                    height="6"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((stats.distribution.employee_analysis.teachers / stats.distribution.employee_analysis.total) * 100).toFixed(1) }}% dari total pegawai
                  </div>
                </div>
                
                <!-- Staff -->
                <div class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">Staff</div>
                      <div class="text-caption text-grey">Tenaga kependidikan</div>
                    </div>
                    <v-chip color="info" size="small">
                      {{ stats.distribution.employee_analysis.staff }} orang
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(stats.distribution.employee_analysis.staff / stats.distribution.employee_analysis.total) * 100"
                    color="info"
                    height="6"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((stats.distribution.employee_analysis.staff / stats.distribution.employee_analysis.total) * 100).toFixed(1) }}% dari total pegawai
                  </div>
                </div>
                
                <!-- Status Keaktifan -->
                <v-alert type="success" variant="tonal" class="mt-4">
                  <v-alert-title>Status Kepegawaian</v-alert-title>
                  Semua pegawai dalam status aktif ({{ stats.distribution.employee_analysis.active_percentage }}%)
                </v-alert>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:account-group-outline</v-icon>
                <div class="text-h6 mt-2">Belum ada data pegawai</div>
                <div class="text-caption">Data kepegawaian akan muncul setelah ada pegawai</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Indikator Kinerja dengan Penjelasan -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi:chart-bar</v-icon>
              Indikator Kinerja Utama (KPI)
              <v-spacer></v-spacer>
              <v-chip color="primary" variant="tonal" size="small">
                ISO 9001 Compliant
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="stats.quality?.performance_indicators">
                <v-row>
                  <v-col cols="12" md="3">
                    <v-card variant="outlined" class="text-center pa-4">
                      <v-icon size="48" color="success" class="mb-2">mdi:database-check</v-icon>
                      <div class="text-h4 font-weight-bold text-success">
                        {{ stats.quality.performance_indicators.data_completeness }}%
                      </div>
                      <div class="text-body-2 font-weight-medium">Kelengkapan Data</div>
                      <div class="text-caption text-grey mt-2">
                        Persentase data yang lengkap dan valid
                      </div>
                      <v-chip 
                        :color="getKpiColor(stats.quality.performance_indicators.data_completeness, 90)"
                        size="small" 
                        class="mt-2"
                      >
                        {{ getKpiStatus(stats.quality.performance_indicators.data_completeness, 90) }}
                      </v-chip>
                    </v-card>
                  </v-col>
                  
                  <v-col cols="12" md="3">
                    <v-card variant="outlined" class="text-center pa-4">
                      <v-icon size="48" color="info" class="mb-2">mdi:account-clock</v-icon>
                      <div class="text-h4 font-weight-bold text-info">
                        {{ stats.quality.performance_indicators.activity_rate }}%
                      </div>
                      <div class="text-body-2 font-weight-medium">Tingkat Aktivitas</div>
                      <div class="text-caption text-grey mt-2">
                        Persentase user yang aktif dalam 7 hari
                      </div>
                      <v-chip 
                        :color="getKpiColor(stats.quality.performance_indicators.activity_rate, 70)"
                        size="small" 
                        class="mt-2"
                      >
                        {{ getKpiStatus(stats.quality.performance_indicators.activity_rate, 70) }}
                      </v-chip>
                    </v-card>
                  </v-col>
                  
                  <v-col cols="12" md="3">
                    <v-card variant="outlined" class="text-center pa-4">
                      <v-icon size="48" color="primary" class="mb-2">mdi:school</v-icon>
                      <div class="text-h4 font-weight-bold text-primary">
                        {{ stats.quality.performance_indicators.utilization_rate }}%
                      </div>
                      <div class="text-body-2 font-weight-medium">Utilisasi Kapasitas</div>
                      <div class="text-caption text-grey mt-2">
                        Persentase kapasitas sekolah yang terpakai
                      </div>
                      <v-chip 
                        :color="getKpiColor(stats.quality.performance_indicators.utilization_rate, 80)"
                        size="small" 
                        class="mt-2"
                      >
                        {{ getKpiStatus(stats.quality.performance_indicators.utilization_rate, 80) }}
                      </v-chip>
                    </v-card>
                  </v-col>
                  
                  <v-col cols="12" md="3">
                    <v-card variant="outlined" class="text-center pa-4">
                      <v-icon size="48" color="success" class="mb-2">mdi:heart-pulse</v-icon>
                      <div class="text-h4 font-weight-bold text-success">
                        {{ stats.quality.performance_indicators.system_health }}%
                      </div>
                      <div class="text-body-2 font-weight-medium">Kesehatan Sistem</div>
                      <div class="text-caption text-grey mt-2">
                        Overall health score sistem
                      </div>
                      <v-chip 
                        :color="getKpiColor(stats.quality.performance_indicators.system_health, 85)"
                        size="small" 
                        class="mt-2"
                      >
                        {{ getKpiStatus(stats.quality.performance_indicators.system_health, 85) }}
                      </v-chip>
                    </v-card>
                  </v-col>
                </v-row>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:chart-bar</v-icon>
                <div class="text-h6 mt-2">Belum ada data KPI</div>
                <div class="text-caption">Indikator kinerja akan muncul setelah ada data</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Trend dan Rekomendasi -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="info">mdi:trending-up</v-icon>
              Trend Pertumbuhan
              <v-spacer></v-spacer>
              <v-chip color="info" variant="tonal" size="small">
                {{ getTrendDirection() }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-center mb-4">
                <v-icon 
                  :color="getTrendColor()" 
                  size="64"
                  class="mb-2"
                >
                  {{ getTrendIcon() }}
                </v-icon>
                <div class="text-h5 font-weight-bold" :class="`text-${getTrendColor()}`">
                  {{ getTrendText() }}
                </div>
                <div class="text-body-2 text-grey mt-2">
                  {{ getTrendDescription() }}
                </div>
              </div>
              
              <v-alert 
                :type="getTrendAlertType()" 
                variant="tonal"
                class="mt-4"
              >
                <v-alert-title>{{ getTrendTitle() }}</v-alert-title>
                {{ getTrendMessage() }}
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="warning">mdi:lightbulb-on</v-icon>
              Rekomendasi & Insight
            </v-card-title>
            <v-card-text>
              <div v-for="(insight, index) in getInsights()" :key="index" class="mb-3">
                <v-alert 
                  :type="insight.type" 
                  variant="tonal"
                  class="mb-2"
                >
                  <v-alert-title>{{ insight.title }}</v-alert-title>
                  {{ insight.message }}
                </v-alert>
              </div>
              
              <div v-if="getInsights().length === 0" class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:lightbulb-outline</v-icon>
                <div class="text-h6 mt-2">Sistem berjalan dengan baik</div>
                <div class="text-caption">Tidak ada rekomendasi khusus saat ini</div>
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

// State
const loading = ref(true);
const stats = ref({
  kpi: {
    total_students: 0,
    total_employees: 0,
    utilization_rate: 0,
    max_capacity: 500,
    teacher_student_ratio: {
      ratio: 0,
      students: 0,
      teachers: 0,
      status: 'unknown'
    }
  },
  distribution: {
    students_by_level: {},
    employee_analysis: {
      total: 0,
      active: 0,
      teachers: 0,
      staff: 0,
      active_percentage: 0
    }
  },
  quality: {
    performance_indicators: {
      data_completeness: 0,
      activity_rate: 0,
      utilization_rate: 0,
      system_health: 0
    }
  }
});

// Methods
async function loadDashboardData() {
  loading.value = true;
  try {
    const response = await fetchDashboardStats();
    if (response.success) {
      stats.value = response.data;
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
  } finally {
    loading.value = false;
  }
}

// Helper functions
function getLevelName(level: string): string {
  const levelNames: Record<string, string> = {
    'VII': 'Kelas VII (7)',
    'VIII': 'Kelas VIII (8)', 
    'IX': 'Kelas IX (9)'
  };
  return levelNames[level] || `Kelas ${level}`;
}

function getLevelDescription(level: string): string {
  const descriptions: Record<string, string> = {
    'VII': 'Siswa baru (kelas 7)',
    'VIII': 'Siswa kelas 8',
    'IX': 'Siswa kelas 9 (kelas terakhir)'
  };
  return descriptions[level] || 'Siswa kelas ' + level;
}

function getLevelColor(level: string): string {
  const colors: Record<string, string> = {
    'VII': 'primary',
    'VIII': 'success',
    'IX': 'warning'
  };
  return colors[level] || 'grey';
}

function getKpiColor(value: number, threshold: number): string {
  if (value >= threshold) return 'success';
  if (value >= threshold * 0.8) return 'warning';
  return 'error';
}

function getKpiStatus(value: number, threshold: number): string {
  if (value >= threshold) return 'Excellent';
  if (value >= threshold * 0.8) return 'Good';
  if (value >= threshold * 0.6) return 'Fair';
  return 'Poor';
}

function getTrendDirection(): string {
  return stats.value.trends?.growth_trend?.trend_direction || 'stable';
}

function getTrendColor(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'success';
    case 'decreasing': return 'error';
    default: return 'info';
  }
}

function getTrendIcon(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'mdi:trending-up';
    case 'decreasing': return 'mdi:trending-down';
    default: return 'mdi:trending-neutral';
  }
}

function getTrendText(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'Meningkat';
    case 'decreasing': return 'Menurun';
    default: return 'Stabil';
  }
}

function getTrendDescription(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'Jumlah siswa menunjukkan tren peningkatan';
    case 'decreasing': return 'Jumlah siswa menunjukkan tren penurunan';
    default: return 'Jumlah siswa relatif stabil';
  }
}

function getTrendAlertType(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'success';
    case 'decreasing': return 'warning';
    default: return 'info';
  }
}

function getTrendTitle(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'Tren Positif';
    case 'decreasing': return 'Perhatian Diperlukan';
    default: return 'Tren Stabil';
  }
}

function getTrendMessage(): string {
  const direction = getTrendDirection();
  switch (direction) {
    case 'increasing': return 'Sekolah menunjukkan pertumbuhan yang baik. Pertimbangkan untuk menambah kapasitas atau fasilitas.';
    case 'decreasing': return 'Perlu evaluasi strategi penerimaan siswa baru dan peningkatan kualitas pendidikan.';
    default: return 'Sekolah dalam kondisi stabil. Monitor terus untuk mempertahankan kualitas.';
  }
}

function getInsights(): Array<{type: string, title: string, message: string}> {
  const insights = [];
  
  // Kapasitas insight
  if (stats.value.kpi?.utilization_rate < 50) {
    insights.push({
      type: 'info',
      title: 'Kapasitas Sekolah',
      message: 'Sekolah masih memiliki kapasitas yang cukup besar. Pertimbangkan strategi untuk menarik lebih banyak siswa.'
    });
  }
  
  // Rasio guru-siswa insight
  if (stats.value.kpi?.teacher_student_ratio?.ratio < 15) {
    insights.push({
      type: 'success',
      title: 'Rasio Guru-Siswa',
      message: 'Rasio guru-siswa sangat baik. Ini memberikan kesempatan pembelajaran yang optimal untuk setiap siswa.'
    });
  }
  
  // Data completeness insight
  if (stats.value.quality?.performance_indicators?.data_completeness < 90) {
    insights.push({
      type: 'warning',
      title: 'Kelengkapan Data',
      message: 'Beberapa data masih belum lengkap. Lengkapi data untuk meningkatkan kualitas sistem.'
    });
  }
  
  return insights;
}

// Lifecycle
onMounted(() => {
  loadDashboardData();
});
</script>

<style scoped>
.h-100 {
  height: 100%;
}
</style>
