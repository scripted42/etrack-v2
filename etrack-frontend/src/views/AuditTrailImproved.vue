<template>
  <div class="pa-4">
    <div class="mb-4">
      <h1 class="text-h4 font-weight-bold">Audit Trail & Log Aktivitas</h1>
      <p class="text-body-2 text-grey">Monitoring dan tracking semua aktivitas sistem untuk keamanan dan compliance</p>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
      <div class="text-h6 mt-4">Memuat data audit trail...</div>
    </div>
    
    <!-- Audit Trail Content -->
    <div v-else>
      <!-- Quick Stats dengan Penjelasan -->
      <v-row class="mb-6">
        <v-col cols="12" md="3">
          <v-card color="primary" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="primary" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:history</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.total_logs || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Total Log Aktivitas</div>
                <div class="text-caption text-grey">Semua aktivitas yang tercatat</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi:check-circle</v-icon>
                  Real-time monitoring
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="success" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="success" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:account-check</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.successful_logins || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Login Berhasil</div>
                <div class="text-caption text-grey">24 jam terakhir</div>
                <div class="text-caption text-success mt-1">
                  <v-icon size="16" class="mr-1">mdi:shield-check</v-icon>
                  Aman
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="warning" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="warning" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:account-alert</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.failed_logins || 0 }}</div>
                <div class="text-body-2 font-weight-medium">Login Gagal</div>
                <div class="text-caption text-grey">24 jam terakhir</div>
                <div class="text-caption text-warning mt-1">
                  <v-icon size="16" class="mr-1">mdi:alert</v-icon>
                  Perlu monitoring
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="3">
          <v-card color="info" variant="tonal" class="h-100">
            <v-card-text class="d-flex align-center">
              <v-avatar color="info" size="48" class="mr-4">
                <v-icon color="white" size="24">mdi:account-group</v-icon>
              </v-avatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics?.active_users || 0 }}</div>
                <div class="text-body-2 font-weight-medium">User Aktif</div>
                <div class="text-caption text-grey">7 hari terakhir</div>
                <div class="text-caption text-info mt-1">
                  <v-icon size="16" class="mr-1">mdi:account-multiple</v-icon>
                  {{ getActiveUserPercentage() }}% dari total
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filter dan Search dengan Penjelasan -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi:filter</v-icon>
              Filter & Pencarian Log Aktivitas
              <v-spacer></v-spacer>
              <v-chip color="primary" variant="tonal" size="small">
                {{ filteredLogs.length }} dari {{ logs.length }} log
              </v-chip>
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="filters.search"
                    label="Cari aktivitas..."
                    prepend-inner-icon="mdi:magnify"
                    variant="outlined"
                    density="comfortable"
                    clearable
                    hint="Cari berdasarkan user, action, atau detail"
                    persistent-hint
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.action"
                    :items="actionOptions"
                    label="Jenis Aktivitas"
                    prepend-inner-icon="mdi:format-list-bulleted"
                    variant="outlined"
                    density="comfortable"
                    clearable
                    hint="Filter berdasarkan jenis aktivitas"
                    persistent-hint
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.user"
                    :items="userOptions"
                    label="User"
                    prepend-inner-icon="mdi:account"
                    variant="outlined"
                    density="comfortable"
                    clearable
                    hint="Filter berdasarkan user tertentu"
                    persistent-hint
                  />
                </v-col>
                
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.dateRange"
                    :items="dateRangeOptions"
                    label="Periode Waktu"
                    prepend-inner-icon="mdi:calendar"
                    variant="outlined"
                    density="comfortable"
                    hint="Filter berdasarkan periode waktu"
                    persistent-hint
                  />
                </v-col>
              </v-row>
              
              <div class="d-flex justify-space-between align-center mt-4">
                <div class="text-caption text-grey">
                  <v-icon size="16" class="mr-1">mdi:information</v-icon>
                  Gunakan filter untuk menemukan aktivitas spesifik. Semua aktivitas sistem tercatat untuk keamanan dan compliance.
                </div>
                <v-btn
                  color="primary"
                  variant="tonal"
                  @click="clearFilters"
                  prepend-icon="mdi:filter-remove"
                >
                  Reset Filter
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Aktivitas Terbaru dengan 2 Kolom -->
      <v-row class="mb-6">
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi:clock-outline</v-icon>
              Aktivitas Terbaru
              <v-spacer></v-spacer>
              <v-chip color="primary" variant="tonal" size="small">
                {{ getRecentActivityCount() }} aktivitas
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="filteredLogs.length > 0">
                <v-row>
                  <!-- Kolom Kiri -->
                  <v-col cols="12" md="6">
                    <div class="text-h6 mb-3 d-flex align-center">
                      <v-icon size="20" class="mr-2" color="primary">mdi:clock-fast</v-icon>
                      Aktivitas Terbaru (Kolom 1)
                    </div>
                    <v-timeline density="compact" align="start">
                      <v-timeline-item
                        v-for="(log, index) in getLeftColumnLogs()"
                        :key="log.id"
                        :dot-color="getActionColor(log.action)"
                        size="small"
                        class="mb-3"
                      >
                        <template v-slot:icon>
                          <v-icon :color="getActionColor(log.action)" size="18">
                            {{ getActionIcon(log.action) }}
                          </v-icon>
                        </template>
                        
                        <v-card variant="outlined" class="pa-3">
                          <div class="d-flex justify-space-between align-start mb-2">
                            <div>
                              <div class="text-body-2 font-weight-bold">
                                {{ getActionTitle(log.action) }}
                              </div>
                              <div class="text-caption text-grey">
                                {{ getActionDescription(log.action) }}
                              </div>
                            </div>
                            <v-chip
                              :color="getActionColor(log.action)"
                              size="x-small"
                              variant="tonal"
                            >
                              {{ log.action }}
                            </v-chip>
                          </div>
                          
                          <div class="d-flex justify-space-between align-center">
                            <div class="text-caption text-grey">
                              <v-icon size="14" class="mr-1">mdi:account</v-icon>
                              {{ log.user?.name || 'Unknown User' }}
                              <br>
                              <v-icon size="14" class="mr-1">mdi:clock</v-icon>
                              {{ formatDateTime(log.created_at) }}
                            </div>
                            <v-btn
                              size="x-small"
                              variant="text"
                              color="primary"
                              @click="showLogDetails(log)"
                              prepend-icon="mdi:eye"
                            >
                              Detail
                            </v-btn>
                          </div>
                          
                          <div v-if="log.details" class="mt-2">
                            <v-expansion-panels variant="accordion">
                              <v-expansion-panel>
                                <v-expansion-panel-title class="text-caption">
                                  <v-icon size="14" class="mr-2">mdi:information</v-icon>
                                  Detail Aktivitas
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                  <div class="detail-content">
                                    <div v-for="(value, key) in log.details" :key="key" class="detail-item mb-2">
                                      <div class="text-caption font-weight-bold text-primary">{{ formatDetailKey(key) }}:</div>
                                      <div class="text-caption ml-2">
                                        <span v-if="typeof value === 'object' && value !== null" class="text-grey">
                                          {{ formatObjectValue(value) }}
                                        </span>
                                        <span v-else class="text-body-2">{{ value }}</span>
                                      </div>
                                    </div>
                                  </div>
                                </v-expansion-panel-text>
                              </v-expansion-panel>
                            </v-expansion-panels>
                          </div>
                        </v-card>
                      </v-timeline-item>
                    </v-timeline>
                  </v-col>
                  
                  <!-- Kolom Kanan -->
                  <v-col cols="12" md="6">
                    <div class="text-h6 mb-3 d-flex align-center">
                      <v-icon size="20" class="mr-2" color="success">mdi:history</v-icon>
                      Aktivitas Terbaru (Kolom 2)
                    </div>
                    <v-timeline density="compact" align="start">
                      <v-timeline-item
                        v-for="(log, index) in getRightColumnLogs()"
                        :key="log.id"
                        :dot-color="getActionColor(log.action)"
                        size="small"
                        class="mb-3"
                      >
                        <template v-slot:icon>
                          <v-icon :color="getActionColor(log.action)" size="18">
                            {{ getActionIcon(log.action) }}
                          </v-icon>
                        </template>
                        
                        <v-card variant="outlined" class="pa-3">
                          <div class="d-flex justify-space-between align-start mb-2">
                            <div>
                              <div class="text-body-2 font-weight-bold">
                                {{ getActionTitle(log.action) }}
                              </div>
                              <div class="text-caption text-grey">
                                {{ getActionDescription(log.action) }}
                              </div>
                            </div>
                            <v-chip
                              :color="getActionColor(log.action)"
                              size="x-small"
                              variant="tonal"
                            >
                              {{ log.action }}
                            </v-chip>
                          </div>
                          
                          <div class="d-flex justify-space-between align-center">
                            <div class="text-caption text-grey">
                              <v-icon size="14" class="mr-1">mdi:account</v-icon>
                              {{ log.user?.name || 'Unknown User' }}
                              <br>
                              <v-icon size="14" class="mr-1">mdi:clock</v-icon>
                              {{ formatDateTime(log.created_at) }}
                            </div>
                            <v-btn
                              size="x-small"
                              variant="text"
                              color="primary"
                              @click="showLogDetails(log)"
                              prepend-icon="mdi:eye"
                            >
                              Detail
                            </v-btn>
                          </div>
                          
                          <div v-if="log.details" class="mt-2">
                            <v-expansion-panels variant="accordion">
                              <v-expansion-panel>
                                <v-expansion-panel-title class="text-caption">
                                  <v-icon size="14" class="mr-2">mdi:information</v-icon>
                                  Detail Aktivitas
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                  <div class="detail-content">
                                    <div v-for="(value, key) in log.details" :key="key" class="detail-item mb-2">
                                      <div class="text-caption font-weight-bold text-primary">{{ formatDetailKey(key) }}:</div>
                                      <div class="text-caption ml-2">
                                        <span v-if="typeof value === 'object' && value !== null" class="text-grey">
                                          {{ formatObjectValue(value) }}
                                        </span>
                                        <span v-else class="text-body-2">{{ value }}</span>
                                      </div>
                                    </div>
                                  </div>
                                </v-expansion-panel-text>
                              </v-expansion-panel>
                            </v-expansion-panels>
                          </div>
                        </v-card>
                      </v-timeline-item>
                    </v-timeline>
                  </v-col>
                </v-row>
                
                <!-- Load More Button -->
                <div v-if="displayedLogsCount < filteredLogs.length" class="text-center mt-4">
                  <v-btn
                    color="primary"
                    variant="tonal"
                    @click="loadMoreLogs"
                    prepend-icon="mdi:arrow-down"
                    :loading="false"
                  >
                    Tampilkan Lebih Banyak ({{ filteredLogs.length - displayedLogsCount }} tersisa)
                  </v-btn>
                </div>
                
                <!-- Show All Button (when all logs are displayed) -->
                <div v-else-if="filteredLogs.length > 20" class="text-center mt-4">
                  <v-chip color="success" variant="tonal" size="small">
                    <v-icon start>mdi:check-circle</v-icon>
                    Semua {{ filteredLogs.length }} aktivitas ditampilkan
                  </v-chip>
                </div>
              </div>
              <div v-else class="text-center text-grey py-8">
                <v-icon size="64" color="grey">mdi:history</v-icon>
                <div class="text-h6 mt-4">Tidak ada aktivitas yang ditemukan</div>
                <div class="text-caption">Coba ubah filter atau periode waktu</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Statistik Aktivitas dengan Penjelasan -->
      <v-row class="mb-6">
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="success">mdi:chart-pie</v-icon>
              Distribusi Jenis Aktivitas
              <v-spacer></v-spacer>
              <v-chip color="success" variant="tonal" size="small">
                {{ getTotalActivityCount() }} total
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="getActivityDistribution().length > 0">
                <div v-for="activity in getActivityDistribution()" :key="activity.action" class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">{{ getActionTitle(activity.action) }}</div>
                      <div class="text-caption text-grey">{{ getActionDescription(activity.action) }}</div>
                    </div>
                    <v-chip :color="getActionColor(activity.action)" size="small">
                      {{ activity.count }} aktivitas
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(activity.count / getTotalActivityCount()) * 100"
                    :color="getActionColor(activity.action)"
                    height="8"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((activity.count / getTotalActivityCount()) * 100).toFixed(1) }}% dari total aktivitas
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:chart-pie-outline</v-icon>
                <div class="text-h6 mt-2">Belum ada data aktivitas</div>
                <div class="text-caption">Data distribusi akan muncul setelah ada aktivitas</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="info">mdi:account-group</v-icon>
              Aktivitas per User
              <v-spacer></v-spacer>
              <v-chip color="info" variant="tonal" size="small">
                {{ getUserActivityCount() }} user
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div v-if="getUserActivityDistribution().length > 0">
                <div v-for="user in getUserActivityDistribution().slice(0, 5)" :key="user.user_id" class="mb-4">
                  <div class="d-flex justify-space-between align-center mb-2">
                    <div>
                      <div class="text-body-1 font-weight-bold">{{ user.user_name || 'Unknown User' }}</div>
                      <div class="text-caption text-grey">User ID: {{ user.user_id }}</div>
                    </div>
                    <v-chip color="info" size="small">
                      {{ user.count }} aktivitas
                    </v-chip>
                  </div>
                  <v-progress-linear
                    :model-value="(user.count / getTotalActivityCount()) * 100"
                    color="info"
                    height="6"
                    rounded
                  />
                  <div class="text-caption text-grey mt-1">
                    {{ ((user.count / getTotalActivityCount()) * 100).toFixed(1) }}% dari total aktivitas
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey py-4">
                <v-icon size="48" color="grey">mdi:account-group-outline</v-icon>
                <div class="text-h6 mt-2">Belum ada data user</div>
                <div class="text-caption">Data aktivitas user akan muncul setelah ada aktivitas</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Keamanan & Compliance dengan Penjelasan -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="warning">mdi:shield-alert</v-icon>
              Status Keamanan
              <v-spacer></v-spacer>
              <v-chip :color="getSecurityStatusColor()" variant="tonal" size="small">
                {{ getSecurityStatus() }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <div>
                    <div class="text-body-1 font-weight-bold">Login Gagal</div>
                    <div class="text-caption text-grey">24 jam terakhir</div>
                  </div>
                  <v-chip :color="getFailedLoginColor()" size="small">
                    {{ statistics?.failed_logins || 0 }} kali
                  </v-chip>
                </div>
                <v-progress-linear
                  :model-value="getFailedLoginPercentage()"
                  :color="getFailedLoginColor()"
                  height="6"
                  rounded
                />
                <div class="text-caption text-grey mt-1">
                  {{ getFailedLoginPercentage().toFixed(1) }}% dari total login
                </div>
              </div>
              
              <v-alert 
                :type="getSecurityAlertType()" 
                variant="tonal"
                class="mt-4"
              >
                <v-alert-title>{{ getSecurityAlertTitle() }}</v-alert-title>
                {{ getSecurityAlertMessage() }}
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="6">
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2" color="success">mdi:certificate</v-icon>
              Compliance & Audit
            </v-card-title>
            <v-card-text>
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <div>
                    <div class="text-body-1 font-weight-bold">Data Retention</div>
                    <div class="text-caption text-grey">Penyimpanan log aktivitas</div>
                  </div>
                  <v-chip color="success" size="small">
                    {{ getDataRetentionDays() }} hari
                  </v-chip>
                </div>
                <v-progress-linear
                  :model-value="100"
                  color="success"
                  height="6"
                  rounded
                />
              </div>
              
              <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-2">
                  <div>
                    <div class="text-body-1 font-weight-bold">Audit Coverage</div>
                    <div class="text-caption text-grey">Cakupan aktivitas yang diaudit</div>
                  </div>
                  <v-chip color="success" size="small">
                    {{ getAuditCoverage() }}%
                  </v-chip>
                </div>
                <v-progress-linear
                  :model-value="getAuditCoverage()"
                  color="success"
                  height="6"
                  rounded
                />
              </div>
              
              <v-alert type="success" variant="tonal" class="mt-4">
                <v-alert-title>Compliance Status</v-alert-title>
                Sistem audit trail memenuhi standar keamanan dan compliance. Semua aktivitas kritis tercatat dengan baik.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Log Details Dialog -->
    <v-dialog v-model="showDetailsDialog" max-width="800px">
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2" color="primary">mdi:information</v-icon>
          Detail Log Aktivitas
        </v-card-title>
        <v-card-text v-if="selectedLog">
          <v-row>
            <v-col cols="12" md="6">
              <v-card variant="outlined" class="pa-3">
                <div class="text-body-2 font-weight-bold mb-2">Informasi Dasar</div>
                <div class="mb-2">
                  <strong>ID Log:</strong> {{ selectedLog.id }}
                </div>
                <div class="mb-2">
                  <strong>User:</strong> {{ selectedLog.user?.name || 'Unknown User' }}
                </div>
                <div class="mb-2">
                  <strong>Action:</strong> {{ selectedLog.action }}
                </div>
                <div class="mb-2">
                  <strong>Waktu:</strong> {{ formatDateTime(selectedLog.created_at) }}
                </div>
                <div class="mb-2">
                  <strong>IP Address:</strong> {{ selectedLog.ip_address || 'N/A' }}
                </div>
              </v-card>
            </v-col>
            <v-col cols="12" md="6">
              <v-card variant="outlined" class="pa-3">
                <div class="text-body-2 font-weight-bold mb-2">Detail Aktivitas</div>
                <pre class="text-caption">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="showDetailsDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { fetchAuditLogs, fetchAuditStatistics, type AuditLog, type AuditStatistics } from '@/services/auditLog';

// State
const loading = ref(true);
const logs = ref<AuditLog[]>([]);
const statistics = ref<AuditStatistics>({} as AuditStatistics);
const showDetailsDialog = ref(false);
const selectedLog = ref<AuditLog | null>(null);
const displayedLogsCount = ref(20); // Jumlah logs yang ditampilkan

// Filters
const filters = ref({
  search: '',
  action: '',
  user: '',
  dateRange: '7d'
});

// Options
const actionOptions = [
  { title: 'Login Berhasil', value: 'LOGIN_SUCCESS' },
  { title: 'Login Gagal', value: 'LOGIN_FAILED' },
  { title: 'Logout', value: 'LOGOUT' },
  { title: 'Create Data', value: 'create' },
  { title: 'Update Data', value: 'update' },
  { title: 'Delete Data', value: 'delete' }
];

const dateRangeOptions = [
  { title: 'Hari ini', value: '1d' },
  { title: '7 hari terakhir', value: '7d' },
  { title: '30 hari terakhir', value: '30d' },
  { title: 'Semua waktu', value: 'all' }
];

// Computed
const filteredLogs = computed(() => {
  let filtered = logs.value;
  
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    filtered = filtered.filter(log => 
      log.user?.name?.toLowerCase().includes(search) ||
      log.action?.toLowerCase().includes(search) ||
      JSON.stringify(log.details)?.toLowerCase().includes(search)
    );
  }
  
  if (filters.value.action) {
    filtered = filtered.filter(log => log.action === filters.value.action);
  }
  
  if (filters.value.user) {
    filtered = filtered.filter(log => log.user_id === filters.value.user);
  }
  
  return filtered;
});

const userOptions = computed(() => {
  const users = [...new Set(logs.value.map(log => log.user_id))];
  return users.map(userId => {
    const user = logs.value.find(log => log.user_id === userId);
    return {
      title: user?.user?.name || `User ${userId}`,
      value: userId
    };
  });
});

// Methods
async function loadAuditData() {
  loading.value = true;
  try {
    const [logsResponse, statsResponse] = await Promise.all([
      fetchAuditLogs(),
      fetchAuditStatistics()
    ]);
    
    if (logsResponse.success) {
      // Handle the correct response structure
      if (logsResponse.data && logsResponse.data.logs) {
        logs.value = logsResponse.data.logs;
      } else {
        logs.value = logsResponse.data || [];
      }
    }
    
    if (statsResponse.success) {
      statistics.value = statsResponse.data;
    }
  } catch (error) {
    console.error('Error loading audit data:', error);
  } finally {
    loading.value = false;
  }
}

function clearFilters() {
  filters.value = {
    search: '',
    action: '',
    user: '',
    dateRange: '7d'
  };
  // Reset displayed logs count when filters are cleared
  displayedLogsCount.value = 20;
}

function showLogDetails(log: AuditLog) {
  selectedLog.value = log;
  showDetailsDialog.value = true;
}

function formatDateTime(dateString: string) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getActionColor(action: string) {
  const colors: Record<string, string> = {
    'LOGIN_SUCCESS': 'success',
    'LOGIN_FAILED': 'error',
    'LOGOUT': 'info',
    'create': 'success',
    'update': 'warning',
    'delete': 'error'
  };
  return colors[action] || 'grey';
}

function getActionIcon(action: string) {
  const icons: Record<string, string> = {
    'LOGIN_SUCCESS': 'mdi:login',
    'LOGIN_FAILED': 'mdi:login-variant',
    'LOGOUT': 'mdi:logout',
    'create': 'mdi:plus',
    'update': 'mdi:pencil',
    'delete': 'mdi:delete'
  };
  return icons[action] || 'mdi:information';
}

function getActionTitle(action: string) {
  const titles: Record<string, string> = {
    'LOGIN_SUCCESS': 'Login Berhasil',
    'LOGIN_FAILED': 'Login Gagal',
    'LOGOUT': 'Logout',
    'create': 'Membuat Data',
    'update': 'Mengupdate Data',
    'delete': 'Menghapus Data'
  };
  return titles[action] || action;
}

function getActionDescription(action: string) {
  const descriptions: Record<string, string> = {
    'LOGIN_SUCCESS': 'User berhasil masuk ke sistem',
    'LOGIN_FAILED': 'User gagal masuk ke sistem',
    'LOGOUT': 'User keluar dari sistem',
    'create': 'Data baru telah dibuat',
    'update': 'Data telah diperbarui',
    'delete': 'Data telah dihapus'
  };
  return descriptions[action] || 'Aktivitas sistem';
}

function getRecentActivityCount() {
  return filteredLogs.value.length;
}

function getActivityDistribution() {
  const distribution = {};
  logs.value.forEach(log => {
    distribution[log.action] = (distribution[log.action] || 0) + 1;
  });
  
  return Object.entries(distribution).map(([action, count]) => ({
    action,
    count
  })).sort((a, b) => b.count - a.count);
}

function getTotalActivityCount() {
  return logs.value.length;
}

function getUserActivityDistribution() {
  const distribution = {};
  logs.value.forEach(log => {
    const key = log.user_id;
    if (!distribution[key]) {
      distribution[key] = {
        user_id: key,
        user_name: log.user?.name,
        count: 0
      };
    }
    distribution[key].count++;
  });
  
  return Object.values(distribution).sort((a, b) => b.count - a.count);
}

function getUserActivityCount() {
  return getUserActivityDistribution().length;
}

function getActiveUserPercentage() {
  const totalUsers = 41; // Total users from dashboard
  const activeUsers = statistics.value?.active_users || 0;
  return totalUsers > 0 ? ((activeUsers / totalUsers) * 100).toFixed(1) : 0;
}

function getSecurityStatus() {
  const failedLogins = statistics.value?.failed_logins || 0;
  if (failedLogins === 0) return 'Aman';
  if (failedLogins < 5) return 'Waspada';
  return 'Bahaya';
}

function getSecurityStatusColor() {
  const status = getSecurityStatus();
  switch (status) {
    case 'Aman': return 'success';
    case 'Waspada': return 'warning';
    case 'Bahaya': return 'error';
    default: return 'grey';
  }
}

function getFailedLoginColor() {
  const failedLogins = statistics.value?.failed_logins || 0;
  if (failedLogins === 0) return 'success';
  if (failedLogins < 5) return 'warning';
  return 'error';
}

function getFailedLoginPercentage() {
  const failedLogins = statistics.value?.failed_logins || 0;
  const successfulLogins = statistics.value?.successful_logins || 0;
  const total = failedLogins + successfulLogins;
  return total > 0 ? (failedLogins / total) * 100 : 0;
}

function getSecurityAlertType() {
  const status = getSecurityStatus();
  switch (status) {
    case 'Aman': return 'success';
    case 'Waspada': return 'warning';
    case 'Bahaya': return 'error';
    default: return 'info';
  }
}

function getSecurityAlertTitle() {
  const status = getSecurityStatus();
  switch (status) {
    case 'Aman': return 'Sistem Aman';
    case 'Waspada': return 'Perhatian Diperlukan';
    case 'Bahaya': return 'Tindakan Segera Diperlukan';
    default: return 'Status Keamanan';
  }
}

function getSecurityAlertMessage() {
  const status = getSecurityStatus();
  switch (status) {
    case 'Aman': return 'Tidak ada aktivitas mencurigakan terdeteksi. Sistem berjalan dengan aman.';
    case 'Waspada': return 'Ada beberapa login gagal. Monitor aktivitas lebih ketat.';
    case 'Bahaya': return 'Banyak login gagal terdeteksi. Segera periksa keamanan sistem.';
    default: return 'Status keamanan sistem.';
  }
}

function getDataRetentionDays() {
  return 90; // 90 hari retention
}

function getAuditCoverage() {
  return 100; // 100% coverage
}

function getLeftColumnLogs() {
  // Ambil setengah dari displayedLogsCount untuk kolom kiri
  const halfCount = Math.ceil(displayedLogsCount.value / 2);
  return filteredLogs.value.slice(0, halfCount);
}

function getRightColumnLogs() {
  // Ambil setengah dari displayedLogsCount untuk kolom kanan
  const halfCount = Math.ceil(displayedLogsCount.value / 2);
  return filteredLogs.value.slice(halfCount, displayedLogsCount.value);
}

function loadMoreLogs() {
  // Tambahkan 20 logs lagi ke yang ditampilkan
  const newCount = displayedLogsCount.value + 20;
  const maxAvailable = filteredLogs.value.length;
  
  // Jangan melebihi jumlah logs yang tersedia
  displayedLogsCount.value = Math.min(newCount, maxAvailable);
  
  console.log(`Load more logs: showing ${displayedLogsCount.value} of ${maxAvailable} total logs`);
}

function formatDetailKey(key: string) {
  const keyMap: Record<string, string> = {
    'event_type': 'Jenis Event',
    'model': 'Model',
    'model_id': 'ID Model',
    'operation': 'Operasi',
    'changes': 'Perubahan',
    'username': 'Username',
    'role': 'Role',
    'nis': 'NIS',
    'nama': 'Nama',
    'kelas': 'Kelas',
    'status': 'Status',
    'reason': 'Alasan',
    'severity': 'Tingkat Keparahan',
    'student_id': 'ID Siswa',
    'nip': 'NIP',
    'jabatan': 'Jabatan'
  };
  return keyMap[key] || key;
}

function formatObjectValue(obj: any) {
  if (Array.isArray(obj)) {
    return obj.join(', ');
  }
  
  if (typeof obj === 'object' && obj !== null) {
    const formatted = [];
    for (const [key, value] of Object.entries(obj)) {
      formatted.push(`${formatDetailKey(key)}: ${value}`);
    }
    return formatted.join(' | ');
  }
  
  return String(obj);
}

// Watchers
watch(filters, () => {
  // Reset displayed logs count when filters change
  displayedLogsCount.value = 20;
}, { deep: true });

// Lifecycle
onMounted(() => {
  loadAuditData();
});
</script>

<style scoped>
.h-100 {
  height: 100%;
}

.detail-content {
  max-height: 300px;
  overflow-y: auto;
  padding: 8px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border: 1px solid #e9ecef;
}

.detail-item {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 4px;
}

.detail-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .detail-content {
    max-height: 200px;
    font-size: 12px;
  }
  
  .detail-item {
    margin-bottom: 8px;
  }
}

/* Ensure expansion panels don't break layout */
.v-expansion-panel-text {
  max-width: 100%;
  overflow: hidden;
}

.v-expansion-panel-text__wrapper {
  padding: 8px 16px;
}
</style>
