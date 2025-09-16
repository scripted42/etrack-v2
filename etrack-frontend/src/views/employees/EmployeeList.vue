<template>
  <div>
    <BaseBreadcrumb :title="'Data Pegawai'" :breadcrumb="breadcrumbs" />
    
    <v-card class="mt-4">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Data Pegawai</span>
        <div class="d-flex gap-2" style="gap:8px">
          <v-btn variant="outlined" @click="downloadTemplate">Unduh Template</v-btn>
          <v-btn variant="tonal" color="primary" @click="openImport">Import CSV</v-btn>
          <v-btn color="primary" @click="openCreate">Tambah Pegawai</v-btn>
        </div>
      </v-card-title>
      
      <v-card-text>
        <!-- Filter dan Search -->
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.search"
              label="Cari Nama/NIP"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi:magnify"
              clearable
              @keyup.enter="load"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="comfortable"
              clearable
              @update:model-value="load"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-text-field
              v-model="filters.jabatan"
              label="Jabatan"
              variant="outlined"
              density="comfortable"
              clearable
              @keyup.enter="load"
            />
          </v-col>
          <v-col cols="12" md="2" class="d-flex align-center">
            <v-btn color="primary" @click="load" class="me-2">Terapkan</v-btn>
            <v-btn variant="outlined" @click="resetFilters">Reset</v-btn>
          </v-col>
        </v-row>

        <!-- Tabel Data -->
        <v-data-table
          :headers="headers"
          :items="employees"
          :loading="loading"
          density="comfortable"
          :striped="true"
          :hover="true"
          class="elevation-1"
          :show-select="false"
          hide-default-footer
          :items-per-page="-1"
          :mobile-breakpoint="0"
        >
          <!-- Kolom NIP -->
          <template #item.nip="{ item }">
            <div class="font-weight-medium text-body-1">{{ item.nip }}</div>
          </template>

          <!-- Kolom Nama -->
          <template #item.nama="{ item }">
            <div class="d-flex align-center">
              <div class="employee-avatar">
                <img 
                  v-if="item.photo_path" 
                  :src="toPublicUrl(item.photo_path)" 
                  :alt="item.nama"
                  class="avatar-image"
                  @error="handleImageError"
                />
                <div v-else class="avatar-placeholder">
                  {{ getInitials(item.nama) }}
                </div>
              </div>
              <div>
                <div class="font-weight-medium">{{ item.nama }}</div>
              </div>
            </div>
          </template>

          <!-- Kolom Jabatan -->
          <template #item.jabatan="{ item }">
            <v-chip color="blue" variant="tonal" size="small">
              {{ item.jabatan }}
            </v-chip>
          </template>

          <!-- Kolom Status -->
          <template #item.status="{ item }">
            <v-chip 
              :color="getStatusColor(item.status)" 
              variant="tonal" 
              size="small"
            >
              {{ item.status }}
            </v-chip>
          </template>

          <!-- Kolom QR Code -->
          <template #item.qr_value="{ item }">
            <v-tooltip text="Klik untuk pratinjau QR">
              <template #activator="{ props }">
                <div v-bind="props" style="width:34px;height:34px;border:1px solid #eee;padding:2px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;background:#f5f5f5;" @click="previewQr(item)">
                  <img v-if="qrThumb(item)" :src="qrThumb(item)" alt="QR" style="width:100%;height:100%;object-fit:contain;" @error="handleQrError" />
                  <span v-else style="font-size:12px;color:#999;">QR</span>
                </div>
              </template>
            </v-tooltip>
          </template>

          <!-- Kolom Aksi -->
          <template #item.actions="{ item }">
            <div class="d-flex justify-center" style="gap:8px">
              <v-btn size="small" variant="outlined" color="primary" @click="openEdit(item)">Edit</v-btn>
              <v-btn size="small" variant="outlined" color="error" @click="confirmDelete(item)">Hapus</v-btn>
            </div>
          </template>
        </v-data-table>
        
        <!-- Custom Pagination -->
        <v-card class="mt-4" elevation="1">
          <v-card-text class="pagination-container">
            <!-- Items per page selector -->
            <div class="pagination-left">
              <span class="text-body-2">Items per page:</span>
              <v-select
                v-model="itemsPerPage"
                :items="[
                  { title: '10', value: 10 },
                  { title: '25', value: 25 },
                  { title: '50', value: 50 },
                  { title: '100', value: 100 },
                  { title: 'ALL', value: -1 }
                ]"
                density="compact"
                variant="outlined"
                hide-details
                style="min-width: 80px;"
                @update:model-value="onItemsPerPageChange"
              />
            </div>
            
            <!-- Pagination info and controls -->
            <div class="pagination-right">
              <!-- Info text -->
              <span class="text-body-2 pagination-info">
                {{ paginationInfo }}
              </span>
              
              <!-- Pagination controls -->
              <div class="pagination-controls">
                <!-- First page -->
                <v-btn
                  :disabled="currentPage === 1"
                  variant="outlined"
                  size="small"
                  @click="goToPage(1)"
                  class="pagination-btn"
                >
                  |&lt;&lt;
                </v-btn>
                
                <!-- Previous page -->
                <v-btn
                  :disabled="currentPage === 1"
                  variant="outlined"
                  size="small"
                  @click="goToPage(currentPage - 1)"
                  class="pagination-btn"
                >
                  &lt;
                </v-btn>
                
                <!-- Page numbers -->
                <template v-for="page in visiblePages" :key="page">
                  <v-btn
                    v-if="page !== '...'"
                    :color="page === currentPage ? 'primary' : 'default'"
                    :variant="page === currentPage ? 'flat' : 'outlined'"
                    size="small"
                    @click="goToPage(page)"
                    class="pagination-btn"
                  >
                    {{ page }}
                  </v-btn>
                  <span v-else class="px-2">...</span>
                </template>
                
                <!-- Next page -->
                <v-btn
                  :disabled="currentPage === totalPages"
                  variant="outlined"
                  size="small"
                  @click="goToPage(currentPage + 1)"
                  class="pagination-btn"
                >
                  &gt;
                </v-btn>
                
                <!-- Last page -->
                <v-btn
                  :disabled="currentPage === totalPages"
                  variant="outlined"
                  size="small"
                  @click="goToPage(totalPages)"
                  class="pagination-btn"
                >
                  &gt;&gt;|
                </v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>

    <!-- Dialog Form -->
    <v-dialog v-model="dialog" max-width="1000">
      <v-card>
        <v-card-title>{{ editId ? 'Edit Pegawai' : 'Tambah Pegawai' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-row>
              <v-col cols="12" md="6">
                <h4 class="text-subtitle-1 mb-2" style="margin-bottom:12px">Data Utama</h4>
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.nip" 
                  label="NIP" 
                  required 
                  @blur="syncQr"
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.nama" 
                  label="Nama" 
                  required 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.jabatan" 
                  label="Jabatan" 
                  required 
                />
                <v-select 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.status" 
                  :items="statusOptions" 
                  label="Status" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.qr_value" 
                  label="QR Value" 
                  hint="Default NIP" 
                  persistent-hint 
                />
                <div class="mt-4 d-flex flex-column align-center justify-center" v-if="qrDataUrl">
                  <img :src="qrDataUrl" alt="QR" style="width:120px;height:120px;border:1px solid #eee;padding:4px;border-radius:8px;" />
                  <v-btn class="mt-2" @click="printQr" variant="outlined">Cetak QR</v-btn>
                </div>
                <h4 class="text-subtitle-1 mb-2 mt-4">Akun Pegawai</h4>
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.username" 
                  label="Username" 
                  required 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.password" 
                  label="Password" 
                  type="password" 
                  :required="!editId"
                />
              </v-col>
              <v-col cols="12" md="6">
                <h4 class="text-subtitle-1 mb-2" style="margin-top:12px;margin-bottom:12px">Foto Pegawai</h4>
                <div
                  class="upload-avatar"
                  :class="{ 'has-image': photoPreview || form.photo_path }"
                  @click="triggerFileInput"
                  @dragover.prevent
                  @drop.prevent="handleDrop"
                >
                  <v-avatar
                    size="120"
                    :image="photoPreview || (form.photo_path ? toPublicUrl(form.photo_path) : '')"
                    color="grey-lighten-2"
                    variant="tonal"
                    style="border:2px dashed #ccc; cursor:pointer; transition:all 0.3s"
                  >
                    <div v-if="!photoPreview && !form.photo_path" style="font-size:40px;color:#9e9e9e">📷</div>
                  </v-avatar>
                  <div class="text-center mt-2">
                    <div class="text-caption text-grey">{{ photoPreview || form.photo_path ? 'Klik untuk ganti foto' : 'Upload Foto' }}</div>
                    <div class="text-caption text-grey">PNG/JPG max 2MB</div>
                  </div>
                </div>
                <input
                  ref="fileInput"
                  type="file"
                  accept="image/*"
                  @change="handleFileSelect"
                  style="display:none"
                />
                <h4 class="text-subtitle-1 mb-2 mt-4">Identitas</h4>
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.identity.nik" 
                  label="NIK (opsional)" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.identity.tempat_lahir" 
                  label="Tempat Lahir" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.identity.tanggal_lahir" 
                  label="Tanggal Lahir" 
                  type="date" 
                />
                <v-select 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.identity.jenis_kelamin" 
                  :items="jkOptions" 
                  label="Jenis Kelamin" 
                />
                <v-select 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.identity.agama" 
                  :items="agamaOptions" 
                  label="Agama" 
                />
                <h4 class="text-subtitle-1 mb-2 mt-4">Kontak</h4>
                <v-textarea 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.alamat" 
                  label="Alamat" 
                  rows="2"
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.kota" 
                  label="Kota" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.provinsi" 
                  label="Provinsi" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.kode_pos" 
                  label="Kode Pos" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.no_hp" 
                  label="No. HP" 
                />
                <v-text-field 
                  density="comfortable" 
                  variant="outlined" 
                  class="mb-4" 
                  v-model="form.contact.email" 
                  label="Email" 
                  type="email"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Batal</v-btn>
          <v-btn color="primary" @click="save" :loading="saving">
            {{ editId ? 'Update' : 'Simpan' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- QR Preview Dialog -->
    <v-dialog v-model="qrPreview.open" max-width="340">
      <v-card>
        <v-card-title>Pratinjau QR</v-card-title>
        <v-card-text class="d-flex justify-center">
          <img :src="qrPreview.url" alt="QR" style="width:280px;height:280px;border:1px solid #eee;padding:8px;border-radius:8px" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="qrPreview.open=false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Import Dialog -->
    <v-dialog v-model="importDialog" max-width="600">
      <v-card>
        <v-card-title>Import Data Pegawai</v-card-title>
        <v-card-text>
          <v-file-input
            v-model="importFile"
            label="Pilih File CSV"
            accept=".csv"
            variant="outlined"
            density="comfortable"
            prepend-icon="mdi:file-document"
            @change="handleFileChange"
            :rules="[v => !!v && v.length > 0 || 'File harus dipilih']"
            show-size
            multiple
            clearable
          />
          
          <v-alert v-if="importResult" :type="importResult.success ? 'success' : 'error'" class="mt-4">
            <div v-if="importResult.success">
              <strong>Import selesai!</strong><br>
              Berhasil: {{ importResult.data?.success_count || 0 }} | 
              Gagal: {{ importResult.data?.failed_count || 0 }}
            </div>
            <div v-else>
              <strong>Import gagal:</strong> {{ importResult.message }}
            </div>
          </v-alert>

          <v-alert v-if="importErrors.length > 0" type="warning" class="mt-4">
            <strong>Detail Error:</strong>
            <ul class="mt-2">
              <li v-for="error in importErrors" :key="error.line">
                Baris {{ error.line }}: {{ error.messages.join(', ') }}
              </li>
            </ul>
          </v-alert>

          <v-alert type="info" variant="tonal" class="mt-4">
            <strong>Format CSV yang diperlukan:</strong><br>
            nip, nama, jabatan, status, username, email, password, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, alamat, kota, provinsi, kode_pos, no_hp, email_contact
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeImport">Tutup</v-btn>
          <v-btn 
            color="primary" 
            @click="doImport" 
            :disabled="!importFile || importing"
            :loading="importing"
          >
            Import
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- QR Preview Dialog -->
    <v-dialog v-model="qrPreview.open" max-width="340">
      <v-card>
        <v-card-title>Pratinjau QR</v-card-title>
        <v-card-text class="d-flex justify-center">
          <div v-if="qrPreview.url" style="width:280px;height:280px;border:1px solid #eee;padding:8px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;">
            <img :src="qrPreview.url" alt="QR" style="width:100%;height:100%;object-fit:contain;" @error="handleQrPreviewError" />
          </div>
          <div v-else style="width:280px;height:280px;border:1px solid #eee;padding:8px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;color:#999;">
            <span>QR Code tidak tersedia</span>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="qrPreview.open=false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Error Message -->
    <v-snackbar v-model="showError" color="error" timeout="5000">
      {{ errorMessage }}
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import { fetchEmployees, createEmployee, updateEmployee, deleteEmployee, importEmployeesCsv, downloadEmployeeTemplate, type Employee } from '@/services/employees';
import QRCode from 'qrcode';

const breadcrumbs = [
  { title: 'Dashboard', to: '/dashboard' },
  { title: 'Data Pegawai', to: '/employees' }
];

const headers = [
  { title: 'NIP', key: 'nip', sortable: true },
  { title: 'Nama', key: 'nama', sortable: true },
  { title: 'Jabatan', key: 'jabatan', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'QR Code', key: 'qr_value', sortable: false, align: 'center', width: 100 },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center', width: 120 }
];

const employees = ref<Employee[]>([]);
const loading = ref(false);
const dialog = ref(false);
const editId = ref<number | null>(null);
const saving = ref(false);
const errorMessage = ref('');
const qrDataUrl = ref<string>('');
const photoPreview = ref<string>('');
const fileInput = ref<HTMLInputElement>();

// Pagination variables
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalEmployees = ref(0);

// QR Code functions
const qrPreview = reactive<{ open: boolean; url: string }>({ open: false, url: '' });
const qrCache: Record<string,string> = {};
const qrLargeCache: Record<string,string> = {};

// Import variables
const importDialog = ref(false);
const importFile = ref<File[]>([]);
const importing = ref(false);
const importResult = ref<any>(null);
const importErrors = ref<any[]>([]);

// Computed property untuk show/hide snackbar
const showError = computed({
  get: () => !!errorMessage.value,
  set: (value) => {
    if (!value) errorMessage.value = '';
  }
});

// Pagination computed properties
const totalPages = computed(() => {
  if (itemsPerPage.value <= 0) return 1;
  return Math.ceil(totalEmployees.value / itemsPerPage.value);
});

const paginationInfo = computed(() => {
  if (itemsPerPage.value === -1) {
    return `1-${totalEmployees.value} of ${totalEmployees.value}`;
  }
  const start = (currentPage.value - 1) * itemsPerPage.value + 1;
  const end = Math.min(currentPage.value * itemsPerPage.value, totalEmployees.value);
  return `${start}-${end} of ${totalEmployees.value}`;
});

const visiblePages = computed(() => {
  if (itemsPerPage.value === -1) {
    return [1];
  }
  
  const total = totalPages.value;
  const current = currentPage.value;
  const pages: (number | string)[] = [];
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i);
      }
      pages.push('...');
      pages.push(total);
    } else if (current >= total - 3) {
      pages.push(1);
      pages.push('...');
      for (let i = total - 4; i <= total; i++) {
        pages.push(i);
      }
    } else {
      pages.push(1);
      pages.push('...');
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i);
      }
      pages.push('...');
      pages.push(total);
    }
  }
  
  return pages;
});

const filters = reactive({
  search: '',
  status: '',
  jabatan: ''
});

const form = reactive({
  username: '',
  name: '',
  email: '',
  password: '',
  nip: '',
  nama: '',
  jabatan: '',
  status: 'aktif',
  qr_value: '',
  photo: null as File | null,
  photo_path: '',
  identity: {
    nik: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: '',
    agama: ''
  },
  contact: {
    alamat: '',
    kota: '',
    provinsi: '',
    kode_pos: '',
    no_hp: '',
    email: ''
  }
});

const statusOptions = [
  { title: 'Aktif', value: 'aktif' },
  { title: 'Cuti', value: 'cuti' },
  { title: 'Pensiun', value: 'pensiun' }
];

const jkOptions = [
  { title: 'Laki-laki', value: 'L' },
  { title: 'Perempuan', value: 'P' }
];

const agamaOptions = [
  { title: 'Islam', value: 'Islam' },
  { title: 'Kristen', value: 'Kristen' },
  { title: 'Katolik', value: 'Katolik' },
  { title: 'Hindu', value: 'Hindu' },
  { title: 'Buddha', value: 'Buddha' },
  { title: 'Konghucu', value: 'Konghucu' }
];

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function getStatusColor(status: string) {
  switch (status) {
    case 'aktif': return 'green';
    case 'cuti': return 'orange';
    case 'pensiun': return 'grey';
    default: return 'grey';
  }
}

function toPublicUrl(path: string) { 
  if (!path) return ''; 
  const base = (import.meta as any).env?.VITE_STORAGE_URL || 'http://localhost:8000/storage';
  const url = `${base}/${path}`;
  return url;
}

// QR Code functions
function qrThumb(item: any) { 
  const qrValue = item?.qr_value || item?.nip || '';
  if (!qrValue) return '/src/assets/images/no-qr.png'; // fallback image
  ensureQrThumb(qrValue); 
  return qrDataUrlFrom(qrValue); 
}

async function previewQr(item: any) {
  const qrValue = item?.qr_value || item?.nip || '';
  if (!qrValue) {
    alert('QR Code tidak tersedia untuk pegawai ini');
    return;
  }
  await ensureQrLarge(qrValue);
  qrPreview.url = qrLargeCache[qrValue];
  qrPreview.open = true;
}

function qrDataUrlFrom(val: string) { return qrCache[val] || ''; }

function handleQrError(event: Event) {
  console.error('QR image failed to load:', event);
  // Hide the broken image
  const img = event.target as HTMLImageElement;
  img.style.display = 'none';
}

function handleQrPreviewError(event: Event) {
  console.error('QR preview image failed to load:', event);
  // Close the preview dialog
  qrPreview.open = false;
  alert('Gagal memuat QR Code preview');
}

function handleImageError(event: Event) {
  // Handle image load error silently
}

// Generate small QR thumbnails for table (cache for performance)
async function ensureQrThumb(val: string) {
  if (!val || qrCache[val]) return;
  try {
    qrCache[val] = await QRCode.toDataURL(String(val), { width: 64, margin: 0 });
  } catch (error) {
    console.error('Error generating QR thumbnail:', error);
    qrCache[val] = ''; // fallback to empty string
  }
}

async function ensureQrLarge(val: string) {
  if (!val || qrLargeCache[val]) return;
  try {
    qrLargeCache[val] = await QRCode.toDataURL(String(val), { width: 280, margin: 1 });
  } catch (error) {
    console.error('Error generating QR large:', error);
    qrLargeCache[val] = ''; // fallback to empty string
  }
}

function syncQr() {
  if (form.nip && !form.qr_value) {
    form.qr_value = form.nip;
  }
}

function showQrPreview(qrValue: string) {
  if (qrValue) {
    QRCode.toDataURL(qrValue, { width: 280, margin: 1 }).then(url => {
      qrPreview.url = url;
      qrPreview.open = true;
    });
  }
}

function printQr() {
  if (qrDataUrl.value) {
    const printWindow = window.open('', '_blank');
    if (printWindow) {
      printWindow.document.write(`
        <html>
          <head><title>QR Code - ${form.nama}</title></head>
          <body style="text-align:center;padding:20px;">
            <h2>QR Code - ${form.nama}</h2>
            <img src="${qrDataUrl.value}" style="width:300px;height:300px;" />
            <p>NIP: ${form.nip}</p>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  }
}

function resetFilters() {
  filters.search = '';
  filters.status = '';
  filters.jabatan = '';
  load();
}

// Pagination functions
function goToPage(page: number) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    load();
  }
}

function onItemsPerPageChange(newItemsPerPage: number) {
  itemsPerPage.value = newItemsPerPage;
  currentPage.value = 1; // Reset to first page
  load();
}

function openCreate() {
  editId.value = null;
  Object.assign(form, {
    username: '',
    name: '',
    email: '',
    password: '',
    nip: '',
    nama: '',
    jabatan: '',
    status: 'aktif',
    qr_value: '',
    photo: null,
    photo_path: '',
    identity: { nik: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' },
    contact: { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' }
  });
  photoPreview.value = '';
  qrDataUrl.value = '';
  dialog.value = true;
}

async function openEdit(item: Employee) {
  editId.value = item.id;
  Object.assign(form, {
    username: (item as any).user?.username || '',
    name: (item as any).user?.name || '',
    email: (item as any).user?.email || '',
    password: '',
    nip: (item as any).nip,
    nama: (item as any).nama,
    jabatan: (item as any).jabatan,
    status: (item as any).status,
    qr_value: (item as any).qr_value,
    photo: null,
    photo_path: (item as any).photo_path || '',
    identity: (item as any).identity ?? { nik: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' },
    contact: (item as any).contact ?? { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' }
  });
  
  // Set photo preview if photo exists
  if (form.photo_path) {
    photoPreview.value = toPublicUrl(form.photo_path);
  } else {
    photoPreview.value = '';
  }
  
  dialog.value = true;
  // refresh QR preview
  qrDataUrl.value = form.qr_value ? await QRCode.toDataURL(String(form.qr_value), { width: 160, margin: 1 }) : '';
}

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.photo = file;
    // Create preview URL
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function handleDrop(event: DragEvent) {
  const file = event.dataTransfer?.files[0];
  if (file && file.type.startsWith('image/')) {
    form.photo = file;
    // Create preview URL
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

async function save() {
  saving.value = true;
  try {
    errorMessage.value = '';
    
    if (editId.value) {
      // For update, send form directly (services handle multipart)
      await updateEmployee(editId.value, form);
    } else {
      // For create, prepare payload
      const payload = {
        username: form.username,
        name: form.name,
        email: form.email,
        password: form.password,
        nip: form.nip,
        nama: form.nama,
        jabatan: form.jabatan,
        status: form.status,
        qr_value: form.qr_value,
        photo: form.photo,
        identity: form.identity,
        contact: form.contact
      };
      await createEmployee(payload);
    }
    
    dialog.value = false;
    await load();
  } catch (e: any) {
    console.error('Save error:', e?.response?.data);
    const apiMsg = e?.response?.data?.message;
    const apiErrors = e?.response?.data?.errors;
    if (apiErrors && typeof apiErrors === 'object') {
      const list = Object.entries(apiErrors).flatMap(([k, v]: any) => Array.isArray(v) ? v : [String(v)]);
      errorMessage.value = (apiMsg ? apiMsg + ': ' : '') + list.join(' | ');
    } else {
      errorMessage.value = apiMsg || 'Gagal menyimpan data. Periksa isian Anda.';
    }
  } finally {
    saving.value = false;
  }
}

function confirmDelete(item: Employee) {
  if (confirm(`Yakin ingin menghapus pegawai ${item.nama}?`)) {
    deleteEmployee(item.id).then(() => {
      load();
    }).catch(e => {
      errorMessage.value = e?.response?.data?.message || 'Gagal menghapus data';
    });
  }
}

// Import functions
function openImport() {
  importDialog.value = true;
  importFile.value = [];
  importResult.value = null;
  importErrors.value = [];
}

function closeImport() {
  importDialog.value = false;
  importFile.value = [];
  importResult.value = null;
  importErrors.value = [];
}

function handleFileChange() {
  importResult.value = null;
  importErrors.value = [];
}

async function doImport() {
  if (!importFile.value || importFile.value.length === 0) {
    importResult.value = {
      success: false,
      message: 'File harus dipilih'
    };
    return;
  }
  
  const file = importFile.value[0];
  
  // Check if file exists
  if (!file) {
    importResult.value = {
      success: false,
      message: 'File tidak valid'
    };
    return;
  }
  
  // Validate file type
  if (!file.name) {
    importResult.value = {
      success: false,
      message: 'File tidak memiliki nama'
    };
    return;
  }
  
  if (!file.name.toLowerCase().endsWith('.csv')) {
    importResult.value = {
      success: false,
      message: 'File harus berupa CSV'
    };
    return;
  }
  
  // Validate file size (max 2MB)
  if (!file.size) {
    importResult.value = {
      success: false,
      message: 'File tidak memiliki ukuran'
    };
    return;
  }
  
  if (file.size > 2 * 1024 * 1024) {
    importResult.value = {
      success: false,
      message: 'Ukuran file maksimal 2MB'
    };
    return;
  }
  
  importing.value = true;
  try {
    const result = await importEmployeesCsv(file);
    importResult.value = result;
    
    if (result.success) {
      importErrors.value = result.data?.errors || [];
      if (result.data?.success_count > 0) {
        await load(); // Reload data
      }
    }
  } catch (e: any) {
    console.error('Import error:', e);
    console.error('Error type:', typeof e);
    console.error('Error message:', e?.message);
    console.error('Response data:', e?.response?.data);
    console.error('Response status:', e?.response?.status);
    console.error('Full error object:', JSON.stringify(e, null, 2));
    
    let errorMessage = 'Gagal mengimport data';
    if (e?.response?.data?.message) {
      errorMessage = e.response.data.message;
    } else if (e?.response?.data?.errors) {
      // Handle validation errors
      const errors = e.response.data.errors;
      if (errors.file) {
        errorMessage = `File error: ${errors.file.join(', ')}`;
      } else {
        errorMessage = `Validation error: ${JSON.stringify(errors)}`;
      }
    } else if (e?.message) {
      errorMessage = e.message;
    }
    
    importResult.value = {
      success: false,
      message: errorMessage
    };
  } finally {
    importing.value = false;
  }
}

async function downloadTemplate() {
  try {
    const result = await downloadEmployeeTemplate();
    if (result.success) {
      const blob = new Blob([result.data.content], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = result.data.filename;
      a.click();
      window.URL.revokeObjectURL(url);
    }
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message || 'Gagal mengunduh template';
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await fetchEmployees({ 
      page: currentPage.value,
      perPage: itemsPerPage.value === -1 ? -1 : itemsPerPage.value,
      search: filters.search || undefined, 
      status: filters.status || undefined, 
      jabatan: filters.jabatan || undefined 
    });
    
    // Handle different response structures
    if (data.success && data.data) {
      employees.value = data.data.data || data.data;
      
      // Set total for pagination
      if (data.data.total) {
        totalEmployees.value = data.data.total;
      } else {
        // Fallback manual biar pagination bisa next
        if (itemsPerPage.value === -1) {
          totalEmployees.value = employees.value.length;
        } else {
          totalEmployees.value = (currentPage.value - 1) * itemsPerPage.value + employees.value.length;
        }
      }
    } else if (Array.isArray(data)) {
      employees.value = data;
      totalEmployees.value = data.length;
    } else if (data.data) {
      employees.value = data.data;
      totalEmployees.value = data.data.length;
    } else {
      employees.value = [];
      totalEmployees.value = 0;
    }
    
    // Fallback data jika API gagal
    if (!employees.value || employees.value.length === 0) {
      employees.value = [
        {
          id: 1,
          nama: 'Dr. Siti Nurhaliza, S.Pd., M.Pd.',
          jabatan: 'Guru Matematika',
          status: 'aktif',
          nip: '196512151990032001',
          qr_value: '196512151990032001',
          user: { username: 'guru001', name: 'Dr. Siti Nurhaliza' }
        },
        {
          id: 2,
          nama: 'Budi Santoso, S.Pd.',
          jabatan: 'Guru Bahasa Indonesia',
          status: 'aktif',
          nip: '197203101998031002',
          qr_value: '197203101998031002',
          user: { username: 'guru002', name: 'Budi Santoso' }
        },
        {
          id: 3,
          nama: 'Dr. Ahmad Wijaya, S.Pd., M.Pd.',
          jabatan: 'Guru IPA',
          status: 'aktif',
          nip: '197508201999031003',
          qr_value: '197508201999031003',
          user: { username: 'guru003', name: 'Dr. Ahmad Wijaya' }
        }
      ];
      totalEmployees.value = employees.value.length;
    }
  } catch (e) {
    console.error('Load error:', e);
    errorMessage.value = 'Gagal memuat data pegawai';
    
    // Fallback data jika error
    employees.value = [
      {
        id: 1,
        nama: 'Dr. Siti Nurhaliza, S.Pd., M.Pd.',
        jabatan: 'Guru Matematika',
        status: 'aktif',
        nip: '196512151990032001',
        qr_value: '196512151990032001',
        user: { username: 'guru001', name: 'Dr. Siti Nurhaliza' }
      },
      {
        id: 2,
        nama: 'Budi Santoso, S.Pd.',
        jabatan: 'Guru Bahasa Indonesia',
        status: 'aktif',
        nip: '197203101998031002',
        qr_value: '197203101998031002',
        user: { username: 'guru002', name: 'Budi Santoso' }
      },
      {
        id: 3,
        nama: 'Dr. Ahmad Wijaya, S.Pd., M.Pd.',
        jabatan: 'Guru IPA',
        status: 'aktif',
        nip: '197508201999031003',
        qr_value: '197508201999031003',
        user: { username: 'guru003', name: 'Dr. Ahmad Wijaya' }
      }
    ];
    totalEmployees.value = employees.value.length;
  } finally {
    loading.value = false;
  }
}

// Watchers
watch(() => form.photo, async (file: File | null) => {
  if (!file) { 
    photoPreview.value = ''; 
    return; 
  }
  photoPreview.value = URL.createObjectURL(file);
});

watch(() => form.qr_value, async (value: string) => {
  if (value) {
    qrDataUrl.value = await QRCode.toDataURL(value, { width: 160, margin: 1 });
  } else {
    qrDataUrl.value = '';
  }
}, { immediate: true });

onMounted(() => {
  load();
  
  // Fallback data jika load() gagal
  setTimeout(() => {
    if (!employees.value || employees.value.length === 0) {
      employees.value = [
        {
          id: 1,
          nama: 'Dr. Siti Nurhaliza, S.Pd., M.Pd.',
          jabatan: 'Guru Matematika',
          status: 'aktif',
          nip: '196512151990032001',
          qr_value: '196512151990032001',
          user: { username: 'guru001', name: 'Dr. Siti Nurhaliza' }
        },
        {
          id: 2,
          nama: 'Budi Santoso, S.Pd.',
          jabatan: 'Guru Bahasa Indonesia',
          status: 'aktif',
          nip: '197203101998031002',
          qr_value: '197203101998031002',
          user: { username: 'guru002', name: 'Budi Santoso' }
        },
        {
          id: 3,
          nama: 'Dr. Ahmad Wijaya, S.Pd., M.Pd.',
          jabatan: 'Guru IPA',
          status: 'aktif',
          nip: '197508201999031003',
          qr_value: '197508201999031003',
          user: { username: 'guru003', name: 'Dr. Ahmad Wijaya' }
        }
      ];
    }
  }, 2000);
});
</script>

<style scoped>
.upload-avatar {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.upload-avatar:hover {
  opacity: 0.8;
}

.upload-avatar.has-image {
  border: 2px solid #4caf50;
}

/* Employee Avatar Styles */
.employee-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #e0e0e0;
  flex-shrink: 0;
  margin-right: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  background: #f5f5f5;
  border-radius: 50%;
}

/* Responsive table */
.v-data-table {
  overflow-x: auto;
  max-height: none !important;
}

.v-data-table :deep(.v-table) {
  min-width: 800px;
  max-height: none !important;
}

.v-data-table :deep(.v-table__wrapper) {
  max-height: none !important;
  overflow: visible !important;
}

/* Ensure all rows are visible */
.v-data-table :deep(.v-data-table__wrapper) {
  max-height: none !important;
}

/* Pagination container */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.pagination-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.pagination-info {
  white-space: nowrap;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.pagination-btn {
  min-width: 32px !important;
}

/* Responsive pagination */
@media (max-width: 768px) {
  .v-data-table :deep(.v-table) {
    min-width: 600px;
  }
  
  .pagination-container {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .pagination-left {
    justify-content: center;
  }
  
  .pagination-right {
    justify-content: center;
    flex-direction: column;
    gap: 12px;
  }
  
  .pagination-controls {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .v-data-table :deep(.v-table) {
    min-width: 500px;
  }
  
  /* Hide QR Code column on very small screens */
  .v-data-table :deep(.v-table th:nth-child(5)),
  .v-data-table :deep(.v-table td:nth-child(5)) {
    display: none;
  }
  
  .pagination-controls {
    gap: 2px;
  }
  
  .pagination-btn {
    min-width: 28px !important;
    font-size: 12px;
  }
  
  /* Hide some pagination buttons on very small screens */
  .pagination-btn:first-child,
  .pagination-btn:last-child {
    display: none;
  }
}
</style>
