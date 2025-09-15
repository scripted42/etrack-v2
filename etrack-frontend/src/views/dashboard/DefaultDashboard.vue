<template>
  <div>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>
    <v-row>
      <!-- Welcome Card -->
      <v-col cols="12" class="mb-6">
        <v-card class="gradient-card" elevation="4">
          <v-card-text class="pa-6">
            <v-row align="center">
              <v-col cols="12" md="8">
                <h1 class="text-h4 font-weight-bold text-white mb-2">
                  Selamat Datang, {{ user?.name }}!
                </h1>
                <p class="text-h6 text-white opacity-90 mb-4">
                  Dashboard E-Track - SMP Negeri 14 Surabaya
                </p>
                <p class="text-body-1 text-white opacity-80">
                  Sistem manajemen data siswa dan pegawai yang terintegrasi
                </p>
              </v-col>
              <v-col cols="12" md="4" class="text-center">
                <v-avatar size="120" class="elevation-8">
                  <v-img src="/logo-smpn14.png" alt="Logo SMPN 14" />
                </v-avatar>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Statistics Cards -->
      <v-col cols="12" sm="6" md="3" v-for="stat in statistics" :key="stat.title">
        <v-card class="stat-card" elevation="2" hover>
          <v-card-text class="pa-4">
            <div class="d-flex align-center">
              <v-avatar :color="stat.color" size="48" class="me-4">
                <v-icon :icon="stat.icon" color="white" size="24" />
              </v-avatar>
              <div>
                <h3 class="text-h4 font-weight-bold text-primary">{{ stat.value }}</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ stat.title }}</p>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Charts Row -->
      <v-col cols="12" lg="8">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon icon="mdi-chart-line" class="me-2" />
            Grafik Pertumbuhan Siswa
          </v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-8">
              <v-progress-circular indeterminate color="primary" size="64" />
              <p class="text-body-1 mt-4">Memuat data...</p>
            </div>
            <div v-else-if="chartData.students_growth?.length" class="chart-container">
              <apexchart
                type="line"
                height="300"
                :options="chartOptions"
                :series="chartSeries"
              />
            </div>
            <div v-else class="text-center pa-8">
              <v-icon icon="mdi-chart-line" size="64" color="grey-lighten-1" />
              <p class="text-body-1 mt-4 text-medium-emphasis">Belum ada data untuk ditampilkan</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="4">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon icon="mdi-account-group" class="me-2" />
            Distribusi Pegawai
          </v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-8">
              <v-progress-circular indeterminate color="primary" size="48" />
            </div>
            <div v-else-if="chartData.employees_by_status?.length" class="chart-container">
              <apexchart
                type="donut"
                height="300"
                :options="donutOptions"
                :series="donutSeries"
              />
            </div>
            <div v-else class="text-center pa-8">
              <v-icon icon="mdi-account-group" size="48" color="grey-lighten-1" />
              <p class="text-body-2 mt-4 text-medium-emphasis">Belum ada data</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Recent Activities -->
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon icon="mdi-history" class="me-2" />
            Aktivitas Terbaru
          </v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-4">
              <v-progress-circular indeterminate color="primary" size="32" />
            </div>
            <v-list v-else-if="recentActivities?.length">
              <v-list-item
                v-for="activity in recentActivities"
                :key="activity.id"
                class="px-0"
              >
                <template v-slot:prepend>
                  <v-avatar :color="getActivityColor(activity.action)" size="40">
                    <v-icon :icon="getActivityIcon(activity.action)" color="white" size="20" />
                  </v-avatar>
                </template>
                <v-list-item-title>{{ getActivityDescription(activity) }}</v-list-item-title>
                <v-list-item-subtitle>
                  {{ formatDateTime(activity.created_at) }}
                  <span v-if="activity.user"> • {{ activity.user.name }}</span>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
            <div v-else class="text-center pa-8">
              <v-icon icon="mdi-history" size="48" color="grey-lighten-1" />
              <p class="text-body-1 mt-4 text-medium-emphasis">Belum ada aktivitas</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import apiService from '@/services/api';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import type { User, AuditLog } from '@/types/user';

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const page = ref({ title: 'Dashboard' });
const breadcrumbs = ref([
  {
    title: 'Dashboard',
    disabled: false,
    href: '/dashboard'
  }
]);

const loading = ref(false);
const statistics = ref([
  { title: 'Total Pengguna', value: 0, icon: 'mdi-account-group', color: 'primary' },
  { title: 'Siswa Aktif', value: 0, icon: 'mdi-school', color: 'success' },
  { title: 'Pegawai Aktif', value: 0, icon: 'mdi-briefcase', color: 'info' },
  { title: 'Login Hari Ini', value: 0, icon: 'mdi-login', color: 'warning' }
]);

const chartData = reactive({
  students_growth: [] as any[],
  employees_by_status: [] as any[],
  students_by_status: [] as any[]
});

const recentActivities = ref<AuditLog[]>([]);

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: { show: false }
  },
  colors: ['#1976d2'],
  xaxis: {
    categories: chartData.students_growth.map(item => `${item.year}-${item.month}`)
  },
  yaxis: {
    title: { text: 'Jumlah Siswa' }
  },
  stroke: {
    curve: 'smooth',
    width: 3
  }
}));

const chartSeries = computed(() => [{
  name: 'Siswa Baru',
  data: chartData.students_growth.map(item => item.total)
}]);

const donutOptions = computed(() => ({
  chart: {
    type: 'donut'
  },
  colors: ['#4caf50', '#ff9800', '#f44336'],
  labels: chartData.employees_by_status.map(item => item.status),
  legend: {
    position: 'bottom'
  }
}));

const donutSeries = computed(() => 
  chartData.employees_by_status.map(item => item.total)
);

const loadDashboardData = async () => {
  loading.value = true;
  try {
    const [statsResponse, chartResponse] = await Promise.all([
      apiService.getDashboardStatistics(),
      apiService.getChartData()
    ]);

    if (statsResponse.success) {
      const data = statsResponse.data;
      statistics.value[0].value = data.total_users;
      statistics.value[1].value = data.total_students;
      statistics.value[2].value = data.total_employees;
      statistics.value[3].value = data.login_stats.today;
      recentActivities.value = data.recent_activities;
    }

    if (chartResponse.success) {
      Object.assign(chartData, chartResponse.data);
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error);
  } finally {
    loading.value = false;
  }
};

const getActivityColor = (action: string) => {
  const colors: Record<string, string> = {
    'LOGIN_SUCCESS': 'success',
    'LOGOUT': 'info',
    'CREATE_STUDENT': 'primary',
    'UPDATE_STUDENT': 'warning',
    'DELETE_STUDENT': 'error',
    'CREATE_EMPLOYEE': 'primary',
    'UPDATE_EMPLOYEE': 'warning',
    'DELETE_EMPLOYEE': 'error'
  };
  return colors[action] || 'grey';
};

const getActivityIcon = (action: string) => {
  const icons: Record<string, string> = {
    'LOGIN_SUCCESS': 'mdi-login',
    'LOGOUT': 'mdi-logout',
    'CREATE_STUDENT': 'mdi-account-plus',
    'UPDATE_STUDENT': 'mdi-account-edit',
    'DELETE_STUDENT': 'mdi-account-remove',
    'CREATE_EMPLOYEE': 'mdi-briefcase-plus',
    'UPDATE_EMPLOYEE': 'mdi-briefcase-edit',
    'DELETE_EMPLOYEE': 'mdi-briefcase-remove'
  };
  return icons[action] || 'mdi-information';
};

const getActivityDescription = (activity: AuditLog) => {
  const descriptions: Record<string, string> = {
    'LOGIN_SUCCESS': 'Berhasil login',
    'LOGOUT': 'Logout',
    'CREATE_STUDENT': `Menambah data siswa: ${activity.details?.nama || 'N/A'}`,
    'UPDATE_STUDENT': `Memperbarui data siswa: ${activity.details?.nama || 'N/A'}`,
    'DELETE_STUDENT': `Menghapus data siswa: ${activity.details?.nama || 'N/A'}`,
    'CREATE_EMPLOYEE': `Menambah data pegawai: ${activity.details?.nama || 'N/A'}`,
    'UPDATE_EMPLOYEE': `Memperbarui data pegawai: ${activity.details?.nama || 'N/A'}`,
    'DELETE_EMPLOYEE': `Menghapus data pegawai: ${activity.details?.nama || 'N/A'}`
  };
  return descriptions[activity.action] || activity.action;
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  loadDashboardData();
});
</script>

<style scoped>
.gradient-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card {
  transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.chart-container {
  min-height: 300px;
}
</style>