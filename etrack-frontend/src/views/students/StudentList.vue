<template>
  <div>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs" />

    <v-card class="mb-4">
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Data Siswa</span>
        <div class="d-flex gap-2" style="gap:8px">
          <v-btn variant="outlined" @click="downloadTemplate">Unduh Template</v-btn>
          <v-btn variant="tonal" color="primary" @click="openImport">Import CSV</v-btn>
          <v-btn color="primary" @click="openCreate">Tambah Siswa</v-btn>
        </div>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field v-model="filters.search" label="Cari nama / NIS" density="comfortable" variant="outlined" clearable @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="filters.kelas" :items="kelasOptions" label="Filter Kelas" density="comfortable" variant="outlined" clearable />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="filters.status" :items="statusOptions" label="Filter Status" density="comfortable" variant="outlined" clearable />
          </v-col>
          <v-col cols="12" md="2" class="d-flex align-center" style="gap:8px">
            <v-btn color="primary" @click="applyFilters">Terapkan</v-btn>
            <v-btn variant="outlined" @click="resetFilters">Reset</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
      <v-data-table
        :items="students"
        :headers="headers"
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
        <template #item.nis="{ item }">
          <div class="font-weight-medium text-body-1">{{ item.nis }}</div>
        </template>

        <template #item.nama="{ item }">
          <div class="d-flex align-center">
            <div class="student-avatar">
              <img 
                v-if="avatarSrc(item)" 
                :src="avatarSrc(item)" 
                :alt="item.nama"
                class="avatar-image"
              />
              <div v-else class="avatar-placeholder">
                {{ initials(item.nama) }}
              </div>
            </div>
            <div>
              <div class="font-weight-medium">{{ item.nama }}</div>
            </div>
          </div>
        </template>

        <template #item.kelas="{ item }">
          <div v-if="inlineEdit.id===item.id && inlineEdit.field==='kelas'" class="d-flex align-center">
            <v-select
              :items="kelasOptions"
              v-model="inlineEdit.value"
              density="compact"
              variant="outlined"
              hide-details
              style="max-width:110px"
              @update:model-value="onInlineSave(item, 'kelas')"
              @blur="cancelInline()"
            />
          </div>
          <div v-else>
            <v-chip size="small" variant="tonal" class="cursor-pointer" @click="startInline(item, 'kelas', item.kelas)">{{ item.kelas }}</v-chip>
          </div>
        </template>

        <template #item.status="{ item }">
          <div v-if="inlineEdit.id===item.id && inlineEdit.field==='status'" class="d-flex align-center">
            <v-select
              :items="statusOptions"
              v-model="inlineEdit.value"
              density="compact"
              variant="outlined"
              hide-details
              style="max-width:120px"
              @update:model-value="onInlineSave(item, 'status')"
              @blur="cancelInline()"
            />
          </div>
          <div v-else>
            <v-chip size="small" :color="statusColor(item.status)" variant="tonal" class="cursor-pointer" @click="startInline(item, 'status', item.status)">
              {{ item.status }}
            </v-chip>
          </div>
        </template>


        <template #item.actions="{ item }">
          <div class="d-flex justify-center align-center" style="gap:8px">
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
    </v-card>


    <!-- Import Dialog -->
    <v-dialog v-model="importDialog" max-width="600">
      <v-card>
        <v-card-title>Import Siswa dari CSV</v-card-title>
        <v-card-text>
          <p class="mb-4">Unggah file CSV sesuai template. Kolom: <code>nis,nama,kelas,status,nisn,tempat_lahir,tanggal_lahir,jenis_kelamin,agama,no_hp,wali_nama,wali_hubungan,wali_no_hp</code>.</p>
          <v-file-input
            label="Pilih file CSV"
            accept=".csv,text/csv"
            v-model="importFile"
            prepend-icon="mdi-file-upload"
            show-size
            variant="outlined"
            density="comfortable"
          />
          <v-alert v-if="importResult" :type="importResult.success ? 'success' : 'error'" class="mt-4" variant="tonal">
            <div v-if="importResult.success">
              {{ importResult.message || 'Import selesai' }} | Berhasil: {{ importedCount }} | Gagal: {{ failedCount }}
            </div>
            <div v-else>
              {{ importResult.message || 'Gagal import' }}
            </div>
            <div v-if="normalizedImportErrors.length" class="mt-2" style="max-height:160px;overflow:auto">
              <ul>
                <li v-for="(err, i) in normalizedImportErrors" :key="i">Baris {{ err.line }}: {{ (err.messages || []).join(' | ') }}</li>
              </ul>
            </div>
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="importDialog=false">Tutup</v-btn>
          <v-btn color="primary" :loading="importing" :disabled="!importFile" @click="doImport">Upload</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>


    <v-dialog v-model="dialog" max-width="900">
      <v-card>
        <v-card-title>{{ editId ? 'Edit Siswa' : 'Tambah Siswa' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-row>
              <v-col cols="12" md="6">
                <h4 class="text-subtitle-1 mb-2" style="margin-bottom:12px">Data Utama</h4>
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.nis" label="NIS" required @blur="syncQr" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.nama" label="Nama" required />
                <v-select density="comfortable" variant="outlined" class="mb-4" v-model="form.kelas" :items="kelasOptions" label="Kelas" required />
                <v-select density="comfortable" variant="outlined" class="mb-4" v-model="form.status" :items="statusOptions" label="Status" />
                <v-row>
                  <v-col cols="12" class="d-flex flex-column align-center">
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
                  </v-col>
                </v-row>
                <h4 class="text-subtitle-1 mb-2 mt-4">Akun Siswa</h4>
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.username" label="Username" required />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.password" label="Password" type="password" :required="!editId" />
              </v-col>
              <v-col cols="12" md="6">
                <h4 class="text-subtitle-1 mb-2" style="margin-top:12px;margin-bottom:12px">Identitas</h4>
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.nik" label="NIK (opsional)" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.nisn" label="NISN (opsional)" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.tempat_lahir" label="Tempat Lahir" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.tanggal_lahir" label="Tanggal Lahir" type="date" />
                <v-select density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.jenis_kelamin" :items="jkOptions" label="Jenis Kelamin" />
                <v-select density="comfortable" variant="outlined" class="mb-4" v-model="form.identity.agama" :items="agamaOptions" label="Agama" />
              </v-col>
            </v-row>
            <h4 class="text-subtitle-1 mb-2 mt-4" style="margin-top:16px;margin-bottom:12px">Kontak</h4>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.alamat" label="Alamat" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.kota" label="Kota" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.provinsi" label="Provinsi" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.kode_pos" label="Kode Pos" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.no_hp" label="No. HP" />
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.contact.email" label="Email" />
              </v-col>
            </v-row>
            <h4 class="text-subtitle-1 mb-2 mt-4" style="margin-top:16px;margin-bottom:12px">Wali Murid</h4>
            <v-row v-for="(w, idx) in form.wali" :key="idx">
              <v-col cols="12" md="4"><v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="w.nama" label="Nama Wali" /></v-col>
              <v-col cols="12" md="3">
                <v-select density="comfortable" variant="outlined" class="mb-4" v-model="w.hubungan" :items="hubunganOptions" label="Hubungan" />
              </v-col>
              <v-col cols="12" md="3" v-if="w.hubungan==='Lainnya'">
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="w.hubungan_lainnya" label="Hubungan Lainnya" />
              </v-col>
              <v-col cols="12" md="3"><v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="w.pekerjaan" label="Pekerjaan" /></v-col>
              <v-col cols="12" md="2"><v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="w.no_hp" label="No. HP" /></v-col>
              <v-col cols="12"><v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="w.alamat" label="Alamat Wali" /></v-col>
            </v-row>
            <v-btn variant="text" @click="addWali">+ Tambah Wali</v-btn>
            <v-alert v-if="showError" type="error" variant="tonal" class="mt-2">{{ errorMessage }}</v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog=false">Batal</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
  
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed, nextTick } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import { fetchStudents, createStudent, updateStudent, deleteStudent, importStudentsCsv, type Student } from '@/services/students';

const page = ref({ title: 'Siswa' });
const breadcrumbs = ref([
  { text: 'Dashboard', disabled: false, href: '/dashboard' },
  { text: 'Siswa', disabled: true, href: '#' }
]);

const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');

// Computed property untuk show/hide error alert
const showError = computed(() => !!errorMessage.value);
const students = ref<Student[]>([]);
const filters = reactive<{ search: string; kelas: string | null; status: string | null }>({ search: '', kelas: null, status: null });
const totalStudents = ref(0);
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Computed properties for custom pagination
const totalPages = computed(() => {
  if (itemsPerPage.value === -1) return 1;
  if (itemsPerPage.value <= 0) return 1;
  return Math.ceil(totalStudents.value / itemsPerPage.value);
});

const paginationInfo = computed(() => {
  if (itemsPerPage.value === -1) {
    return `1-${totalStudents.value} of ${totalStudents.value}`;
  }
  const start = (currentPage.value - 1) * itemsPerPage.value + 1;
  const end = Math.min(currentPage.value * itemsPerPage.value, totalStudents.value);
  return `${start}-${end} of ${totalStudents.value}`;
});

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  
  // Jika ALL (itemsPerPage = -1), hanya tampilkan halaman 1
  if (itemsPerPage.value === -1) {
    return [1];
  }
  
  if (total <= 7) {
    // Show all pages if total is 7 or less
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Show first page
    pages.push(1);
    
    if (current > 4) {
      pages.push('...');
    }
    
    // Show pages around current page
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    
    for (let i = start; i <= end; i++) {
      if (i !== 1 && i !== total) {
        pages.push(i);
      }
    }
    
    if (current < total - 3) {
      pages.push('...');
    }
    
    // Show last page
    if (total > 1) {
      pages.push(total);
    }
  }
  
  return pages;
});

// Watch totalStudents untuk debug
watch(totalStudents, (newVal, oldVal) => {
  // totalStudents changed
}, { immediate: true });

// Watch itemsPerPage untuk debug
watch(itemsPerPage, (newVal, oldVal) => {
  // itemsPerPage changed
}, { immediate: true });

// const tableKey = ref(0);


// // Muat ulang saat nomor halaman berubah
// // watch(currentPage, async (val, oldVal) => {
// //   console.log('WATCHER: currentPage changed from', oldVal, 'to', val);
// //   if (val !== oldVal) {
// //     await load();
// //   }
// // });

// // Computed properties untuk pagination
// const itemsPerPageNormalized = computed(() => {
//   return itemsPerPage.value === -1 ? totalStudents.value : itemsPerPage.value;
// });

// const pageCount = computed(() => {
//   if (totalStudents.value === 0) return 1;
//   const count = Math.ceil(totalStudents.value / itemsPerPageNormalized.value);
//   console.log('pageCount computed:', count, 'totalStudents:', totalStudents.value, 'itemsPerPageNormalized:', itemsPerPageNormalized.value);
//   return count;
// });

// (v3) gunakan items-length, tidak perlu computed khusus

const headers = [
  { title: 'NIS', value: 'nis' },
  { title: 'Nama', value: 'nama' },
  { title: 'Kelas', value: 'kelas' },
  { title: 'Status', value: 'status' },
  { title: 'Aksi', value: 'actions', sortable: false, align: 'center', width: 120 }
];

const statusOptions = [
  { title: 'Aktif', value: 'aktif' },
  { title: 'Lulus', value: 'lulus' },
  { title: 'Pindah', value: 'pindah' }
];

const kelasOptions = Array.from({ length: 3 }).flatMap((_, i) => {
  const grade = 7 + i;
  return ['A','B','C','D','E','F'].map(sec => ({ title: `${grade}${sec}`, value: `${grade}${sec}` }));
});

const dialog = ref(false);
const importDialog = ref(false);
const importFile = ref<File | null>(null);
const importing = ref(false);
const importResult = ref<any>(null);
// Backend import returns { success, message, data: { imported, failed, errors } }
const importedCount = computed(() => Number(importResult.value?.data?.imported ?? importResult.value?.imported_count ?? 0));
const failedCount = computed(() => Number(importResult.value?.data?.failed ?? importResult.value?.failed_count ?? 0));
const importErrors = computed(() => (importResult.value?.data?.errors ?? importResult.value?.errors ?? []));
const normalizedImportErrors = computed(() => {
  const errs = importErrors.value;
  if (!Array.isArray(errs)) return [];
  return errs.map((e: any) => {
    if (typeof e === 'string') return { line: '-', messages: [e] };
    const line = e?.line ?? '-';
    const messages = Array.isArray(e?.messages)
      ? e.messages
      : (typeof e?.messages === 'string' ? [e.messages] : (typeof e === 'string' ? [e] : []));
    return { line, messages };
  });
});
const formRef = ref();
const editId = ref<number | null>(null);
const form = reactive<any>({
  nis: '',
  nama: '',
  kelas: '',
  status: 'aktif',
  photo: null as any,
  username: '',
  password: '',
  identity: { nik: '', nisn: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' },
  contact: { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' },
  wali: [] as Array<any>
});

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

const hubunganOptions = [
  { title: 'Ayah', value: 'Ayah' },
  { title: 'Ibu', value: 'Ibu' },
  { title: 'Lainnya', value: 'Lainnya' }
];

const photoPreview = ref<string>('');
const fileInput = ref<HTMLInputElement>();

// Filters
async function applyFilters() { 
  currentPage.value = 1; // Reset to first page
  await load(); 
}

async function resetFilters() { 
  filters.search = ''; 
  filters.kelas = null; 
  filters.status = null; 
  currentPage.value = 1; // Reset to first page
  await load(); 
}

// Inline edit state
const inlineEdit = reactive<{ id: number | null; field: string | null; value: any }>({ id: null, field: null, value: null });
function startInline(item: any, field: 'kelas'|'status', initial: any) { inlineEdit.id = (item as any).id; inlineEdit.field = field; inlineEdit.value = initial; }
function cancelInline() { inlineEdit.id = null; inlineEdit.field = null; inlineEdit.value = null; }
async function onInlineSave(item: any, field: 'kelas'|'status') {
  try {
    const payload: any = {}; payload[field] = inlineEdit.value;
    await updateStudent((item as any).id, payload);
    (item as any)[field] = inlineEdit.value;
  } finally {
    cancelInline();
  }
}

function statusColor(s: string) { return s==='aktif' ? 'success' : s==='lulus' ? 'primary' : 'warning'; }
function initials(name: string) { if (!name) return '?'; const parts = name.trim().split(/\s+/); return (parts[0][0] + (parts[1]?.[0]||'')).toUpperCase(); }
function avatarSrc(item: any) { 
  if (!item?.photo_path) return '';
  const url = toPublicUrl(item.photo_path);
  return url;
}
function toPublicUrl(path: string) { 
  if (!path) return ''; 
  const base = (import.meta as any).env?.VITE_STORAGE_URL || 'http://localhost:8000/storage';
  const url = `${base}/${path}`;
  return url;
}
function syncQr() {
  if (!form.username) form.username = form.nis;
  const tgl = (form.identity?.tanggal_lahir || '').toString();
  const thn = tgl ? String(new Date(tgl).getFullYear() || '') : '';
  if (!editId.value) form.password = `${form.nis}${thn}`;
}

watch(() => form.photo, async (file: File | null) => {
  if (!file) { photoPreview.value = ''; return; }
  photoPreview.value = URL.createObjectURL(file);
});

// Upload photo handlers
function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.photo = file;
  }
}

function handleDrop(event: DragEvent) {
  const file = event.dataTransfer?.files[0];
  if (file && file.type.startsWith('image/')) {
    form.photo = file;
  }
}


watch(() => form.identity.tanggal_lahir, () => {
  // update default password bila NIS sudah terisi
  syncQr();
});

function openCreate() {
  editId.value = null;
  Object.assign(form, { nis: '', nama: '', kelas: '', status: 'aktif', photo: null, photo_path: '', identity: { nik: '', nisn: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' }, contact: { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' }, wali: [] });
  photoPreview.value = '';
  dialog.value = true;
}

async function openEdit(item: Student) {
  editId.value = item.id;
  Object.assign(form, {
    nis: (item as any).nis,
    nama: (item as any).nama,
    kelas: (item as any).kelas,
    status: (item as any).status,
    photo: null,
    photo_path: (item as any).photo_path || '',
    identity: (item as any).identity ?? { nik: '', nisn: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' },
    contact: (item as any).contact ?? { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' },
    wali: (item as any).guardians ?? []
  });
  photoPreview.value = '';
  dialog.value = true;
}

async function save() {
  saving.value = true;
  try {
    errorMessage.value = '';
    if (editId.value) {
      // For update, send form directly (services handle multipart)
      await updateStudent(editId.value, form);
    } else {
      // map hubungan lainnya
      const payload = JSON.parse(JSON.stringify(form));
      payload.wali = (payload.wali || []).map((w: any) => ({
        ...w,
        hubungan: w.hubungan === 'Lainnya' ? (w.hubungan_lainnya || 'Lainnya') : w.hubungan
      }));
      // auto defaults
      payload.username = payload.username || payload.nis;
      if (!payload.password) {
        const thn = payload.identity?.tanggal_lahir ? new Date(payload.identity.tanggal_lahir).getFullYear() : '';
        let pwd = `${payload.nis || ''}${thn || ''}`;
        if (pwd.length < 8) {
          const yearFallback = thn || '2000';
          pwd = `${payload.nis || ''}${yearFallback}`;
        }
        if (pwd.length < 8) {
          pwd = (payload.nis || '').padEnd(8, '0');
        }
        payload.password = pwd;
      }
      // email tidak digunakan
      delete payload.email;
      // backend expects name (untuk user.name)
      payload.name = payload.nama;
      await createStudent(payload);
    }
    dialog.value = false;
    await load();
  } catch (e: any) {
    console.error('Save error:', e?.response?.data);
    const apiMsg = e?.response?.data?.message;
    const apiErrors = e?.response?.data?.errors;
    const debug = e?.response?.data?.debug;
    if (apiErrors && typeof apiErrors === 'object') {
      const list = Object.entries(apiErrors).flatMap(([k, v]: any) => Array.isArray(v) ? v : [String(v)]);
      errorMessage.value = (apiMsg ? apiMsg + ': ' : '') + list.join(' | ');
    } else if (debug) {
      errorMessage.value = `Debug: ${debug.error} (${debug.file}:${debug.line})`;
    } else {
      errorMessage.value = apiMsg || 'Gagal menyimpan data. Periksa isian Anda.';
    }
  } finally {
    saving.value = false;
  }
}

function confirmDelete(item: Student) {
  if (confirm(`Hapus siswa ${item.nama}?`)) {
    doDelete(item.id);
  }
}

async function doDelete(id: number) {
  try {
    await deleteStudent(id);
    await load();
  } catch (error: any) {
    console.error('Error deleting student:', error);
    alert('Gagal menghapus data: ' + (error?.response?.data?.message || error.message));
  }
}

async function load() {
  loading.value = true;
  try {
    const response = await fetchStudents({ 
      page: currentPage.value,
      perPage: itemsPerPage.value === -1 ? -1 : itemsPerPage.value,
      search: filters.search || undefined, 
      kelas: filters.kelas || undefined, 
      status: filters.status || undefined 
    });

    if (response.success && response.data) {
      // data utama
      students.value = response.data.data || [];

      // ambil total asli dari API
      if (response.data.total) {
        totalStudents.value = response.data.total;
      } else {
        // fallback manual biar pagination bisa next
        if (itemsPerPage.value === -1) {
          // Jika ALL, totalStudents = jumlah data yang diterima
          totalStudents.value = students.value.length;
        } else {
          totalStudents.value =
            (currentPage.value - 1) * itemsPerPage.value + students.value.length;
        }
      }
      
      // Data loaded successfully
    } else {
      students.value = [];
      totalStudents.value = 0;
    }

    // Pagination debug info

    
    // Force DOM update
    await nextTick();
    
    // Force update pagination info
    await nextTick();
    
  } finally {
    loading.value = false;
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) {
    return;
  }
  
  if (page === currentPage.value) {
    return;
  }
  
  currentPage.value = page;
  load();
}

function onItemsPerPageChange(newItemsPerPage: number) {
  itemsPerPage.value = newItemsPerPage;
  
  // Jika ALL, reset ke halaman 1 dan load semua data
  if (newItemsPerPage === -1) {
    currentPage.value = 1;
  } else {
    currentPage.value = 1; // Reset to first page
  }
  
  load();
}



function addWali() {
  form.wali.push({ nama: '', hubungan: '', pekerjaan: '', no_hp: '', alamat: '' });
}


onMounted(load);

// Import CSV
function openImport() {
  importResult.value = null;
  importFile.value = null;
  importDialog.value = true;
}

async function doImport() {
  if (!importFile.value) return;
  importing.value = true;
  try {
    const res = await importStudentsCsv(importFile.value);
    importResult.value = res;
    await load();
  } catch (e: any) {
    importResult.value = { success: false, message: e?.response?.data?.message || 'Gagal import', errors: e?.response?.data?.errors };
  } finally {
    importing.value = false;
  }
}

function downloadTemplate() {
  // Kolom sesuai dukungan importer (wajib + opsional diminta)
  const header = [
    'nis','nama','kelas','status',
    'nisn','tempat_lahir','tanggal_lahir','jenis_kelamin','agama','no_hp',
    'wali_nama','wali_hubungan','wali_no_hp'
  ].join(',');
  const row1 = [
    '91001','Contoh Satu','7A','aktif',
    '0012345678','Surabaya','2012-01-02','L','Islam','0811111111',
    'Bapak Satu','Ayah','0811111110'
  ].join(',');
  const row2 = [
    '91002','Contoh Dua','7B','aktif',
    '0012345679','Sidoarjo','2012-03-04','P','Kristen','0822222222',
    'Ibu Dua','Ibu','0822222220'
  ].join(',');
  const csv = header + '\n' + row1 + '\n' + row2 + '\n';
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'template_import_siswa.csv';
  a.click();
  URL.revokeObjectURL(url);
}
</script>

<style scoped>
/* Ensure table shows all data without height restrictions */
.v-data-table {
  max-height: none !important;
}

.v-data-table :deep(.v-table) {
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

/* Student Avatar Styles */
.student-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
  font-size: 12px;
  font-weight: 500;
  color: #666;
  background: #f5f5f5;
  border-radius: 50%;
}

/* Responsive table */
.v-data-table {
  overflow-x: auto;
}

.v-data-table :deep(.v-table) {
  min-width: 800px;
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




