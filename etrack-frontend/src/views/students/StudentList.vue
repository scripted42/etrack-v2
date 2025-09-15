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
        fixed-header
        :striped="true"
        :hover="true"
      >
        <template #item.nama="{ item }">
          <div class="d-flex align-center">
            <div 
              class="mr-3 d-flex align-center justify-center"
              style="width:36px; height:36px; border-radius:50%; border:2px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: #f5f5f5; overflow: hidden;"
            >
              <img 
                v-if="avatarSrc(item)" 
                :src="avatarSrc(item)" 
                :alt="item.nama"
                style="width:100%; height:100%; object-fit: cover;"
              />
              <span v-else style="font-size:14px; font-weight:500; color: #666;">{{ initials(item.nama) }}</span>
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

        <template #item.qr_value="{ item }">
          <v-tooltip text="Klik untuk pratinjau QR">
            <template #activator="{ props }">
              <img v-bind="props" :src="qrThumb(item)" alt="QR" style="width:34px;height:34px;border:1px solid #eee;padding:2px;border-radius:4px;cursor:pointer" @click="previewQr(item)" />
            </template>
          </v-tooltip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex justify-center" style="gap:8px">
            <v-btn size="small" variant="outlined" color="primary" @click="openEdit(item)">Edit</v-btn>
            <v-btn size="small" variant="outlined" color="error" @click="confirmDelete(item)">Hapus</v-btn>
          </div>
        </template>
      </v-data-table>
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
                <v-text-field density="comfortable" variant="outlined" class="mb-4" v-model="form.qr_value" label="QR Value" hint="Default NIS" persistent-hint />
                <v-row>
                  <v-col cols="12" md="6" class="d-flex flex-column align-center justify-center">
                    <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR" style="width:120px;height:120px;border:1px solid #eee;padding:4px;border-radius:8px;" />
                    <v-btn class="mt-2" @click="printQr" variant="outlined">Cetak QR</v-btn>
                  </v-col>
                  <v-col cols="12" md="6" class="d-flex flex-column align-center">
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
            <v-alert v-if="errorMessage" type="error" variant="tonal" class="mt-2">{{ errorMessage }}</v-alert>
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
import { ref, reactive, onMounted, watch, computed } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import { fetchStudents, createStudent, updateStudent, deleteStudent, importStudentsCsv, type Student } from '@/services/students';
import QRCode from 'qrcode';

const page = ref({ title: 'Siswa' });
const breadcrumbs = ref([
  { text: 'Dashboard', disabled: false, href: '/dashboard' },
  { text: 'Siswa', disabled: true, href: '#' }
]);

const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const students = ref<Student[]>([]);
const headers = [
  { title: 'NIS', value: 'nis' },
  { title: 'Nama', value: 'nama' },
  { title: 'Kelas', value: 'kelas' },
  { title: 'Status', value: 'status' },
  { title: 'QR', value: 'qr_value' },
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
  qr_value: '',
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

const qrDataUrl = ref<string>('');
const photoPreview = ref<string>('');
const fileInput = ref<HTMLInputElement>();

// Filters
const filters = reactive<{ search: string; kelas: string | null; status: string | null }>({ search: '', kelas: null, status: null });
async function applyFilters() { await load(); }
function resetFilters() { filters.search = ''; filters.kelas = null; filters.status = null; load(); }

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
function qrThumb(item: any) { if (!item?.qr_value) return ''; ensureQrThumb(item.qr_value); return qrDataUrlFrom(item.qr_value); }
const qrPreview = reactive<{ open: boolean; url: string }>({ open: false, url: '' });
async function previewQr(item: any) {
  if (!item?.qr_value) return;
  await ensureQrLarge(item.qr_value);
  qrPreview.url = qrLargeCache[item.qr_value];
  qrPreview.open = true;
}
function qrDataUrlFrom(val: string) { return qrCache[val] || ''; }
const qrCache: Record<string,string> = {};
const qrLargeCache: Record<string,string> = {};

function syncQr() {
  // QR otomatis mengikuti NIS
  form.qr_value = form.nis;
  if (!form.username) form.username = form.nis;
  const tgl = (form.identity?.tanggal_lahir || '').toString();
  const thn = tgl ? String(new Date(tgl).getFullYear() || '') : '';
  if (!editId.value) form.password = `${form.nis}${thn}`;
}

watch(() => form.qr_value, async (val) => {
  if (!val) { qrDataUrl.value = ''; return; }
  qrDataUrl.value = await QRCode.toDataURL(String(val), { width: 160, margin: 1 });
});

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

// Generate small QR thumbnails for table (cache for performance)
async function ensureQrThumb(val: string) {
  if (!val || qrCache[val]) return;
  qrCache[val] = await QRCode.toDataURL(String(val), { width: 64, margin: 0 });
}

async function ensureQrLarge(val: string) {
  if (!val || qrLargeCache[val]) return;
  qrLargeCache[val] = await QRCode.toDataURL(String(val), { width: 280, margin: 1 });
}

watch(() => form.identity.tanggal_lahir, () => {
  // update default password bila NIS sudah terisi
  syncQr();
});

function openCreate() {
  editId.value = null;
  Object.assign(form, { nis: '', nama: '', kelas: '', status: 'aktif', qr_value: '', photo: null, photo_path: '', identity: { nik: '', nisn: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' }, contact: { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' }, wali: [] });
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
    qr_value: (item as any).qr_value,
    photo: null,
    photo_path: (item as any).photo_path || '',
    identity: (item as any).identity ?? { nik: '', nisn: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', agama: '' },
    contact: (item as any).contact ?? { alamat: '', kota: '', provinsi: '', kode_pos: '', no_hp: '', email: '' },
    wali: (item as any).guardians ?? []
  });
  photoPreview.value = '';
  dialog.value = true;
  // refresh QR preview
  qrDataUrl.value = form.qr_value ? await QRCode.toDataURL(String(form.qr_value), { width: 160, margin: 1 }) : '';
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
  await deleteStudent(id);
  await load();
}

async function load() {
  loading.value = true;
  try {
    students.value = await fetchStudents({ search: filters.search || undefined, kelas: filters.kelas || undefined, status: filters.status || undefined });
    // Pre-generate QR thumbs
    for (const s of students.value as any[]) { await ensureQrThumb(s.qr_value); }
  } finally {
    loading.value = false;
  }
}

function addWali() {
  form.wali.push({ nama: '', hubungan: '', pekerjaan: '', no_hp: '', alamat: '' });
}

function printQr() {
  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write(`<img src="${qrDataUrl.value}" style="width:240px;height:240px" />`);
  w.document.close();
  w.focus();
  w.print();
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


