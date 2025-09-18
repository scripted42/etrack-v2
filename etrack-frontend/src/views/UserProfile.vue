<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card class="elevation-4">
          <v-card-title class="text-center pa-6">
            <v-icon size="48" color="primary" class="mb-4">mdi-account</v-icon>
            <h2 class="text-h4 font-weight-bold">Profil User</h2>
            <p class="text-subtitle-1 text-medium-emphasis mt-2">
              Kelola informasi profil dan keamanan akun
            </p>
          </v-card-title>

          <v-card-text class="pa-6">
            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              class="mb-4"
              closable
              @click:close="errorMessage = ''"
            >
              {{ errorMessage }}
            </v-alert>

            <v-alert
              v-if="successMessage"
              type="success"
              variant="tonal"
              class="mb-4"
              closable
              @click:close="successMessage = ''"
            >
              {{ successMessage }}
            </v-alert>

            <!-- User Information -->
            <v-card variant="outlined" class="mb-6">
              <v-card-title class="text-h6">
                <v-icon class="mr-2">mdi-information</v-icon>
                Informasi User
              </v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="userInfo.username"
                      label="Username"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="userInfo.name"
                      label="Nama Lengkap"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="userInfo.role"
                      label="Role"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="userInfo.status"
                      label="Status"
                      readonly
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <!-- Email Update Form -->
            <v-card variant="outlined" class="mb-6">
              <v-card-title class="text-h6">
                <v-icon class="mr-2">mdi-email</v-icon>
                Update Email
              </v-card-title>
              <v-card-text>
                <v-form @submit.prevent="updateEmail" ref="emailForm">
                  <v-text-field
                    v-model="emailForm.currentEmail"
                    label="Email Saat Ini"
                    readonly
                    variant="outlined"
                    class="mb-4"
                  />
                  
                  <v-text-field
                    v-model="emailForm.newEmail"
                    label="Email Baru"
                    type="email"
                    :error-messages="errors.newEmail"
                    :disabled="loading"
                    required
                    variant="outlined"
                    class="mb-4"
                  />

                  <v-text-field
                    v-model="emailForm.confirmEmail"
                    label="Konfirmasi Email Baru"
                    type="email"
                    :error-messages="errors.confirmEmail"
                    :disabled="loading"
                    required
                    variant="outlined"
                    class="mb-4"
                  />

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="loading"
                    block
                  >
                    <v-icon left>mdi-email-edit</v-icon>
                    Update Email
                  </v-btn>
                </v-form>
              </v-card-text>
            </v-card>

            <!-- MFA Status -->
            <v-card variant="outlined">
              <v-card-title class="text-h6">
                <v-icon class="mr-2">mdi-shield-check</v-icon>
                Multi-Factor Authentication
              </v-card-title>
              <v-card-text>
                <div class="d-flex align-center mb-4">
                  <v-icon :color="mfaStatus.mfa_enabled ? 'success' : 'warning'" class="mr-3">
                    {{ mfaStatus.mfa_enabled ? 'mdi-shield-check' : 'mdi-shield-alert' }}
                  </v-icon>
                  <div>
                    <h3 class="text-h6">Status MFA</h3>
                    <p class="text-body-2 text-medium-emphasis">
                      {{ mfaStatus.mfa_enabled ? 'MFA Aktif' : 'MFA Belum Diaktifkan' }}
                    </p>
                  </div>
                </div>
                
                <p class="text-body-2 mb-4">
                  Email MFA: <strong>{{ mfaStatus.email }}</strong>
                </p>

                <v-btn
                  :color="mfaStatus.mfa_enabled ? 'error' : 'primary'"
                  :variant="mfaStatus.mfa_enabled ? 'outlined' : 'flat'"
                  :loading="mfaLoading"
                  @click="toggleMFA"
                  block
                >
                  <v-icon left>
                    {{ mfaStatus.mfa_enabled ? 'mdi-shield-remove' : 'mdi-shield-plus' }}
                  </v-icon>
                  {{ mfaStatus.mfa_enabled ? 'Nonaktifkan MFA' : 'Aktifkan MFA' }}
                </v-btn>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';

const authStore = useAuthStore();

const loading = ref(false);
const mfaLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const emailForm = ref({
  currentEmail: '',
  newEmail: '',
  confirmEmail: ''
});

const errors = reactive({
  newEmail: [] as string[],
  confirmEmail: [] as string[]
});

const userInfo = ref({
  username: '',
  name: '',
  role: '',
  status: ''
});

const mfaStatus = ref({
  mfa_enabled: false,
  email: ''
});

const loadUserInfo = () => {
  const user = authStore.user;
  if (user) {
    userInfo.value = {
      username: user.username,
      name: user.name,
      role: user.role?.name || 'No Role',
      status: user.status
    };
    emailForm.value.currentEmail = user.email;
  }
};

const loadMFAStatus = async () => {
  try {
    const response = await api.get('/mfa/status');
    if (response.data.success) {
      mfaStatus.value = response.data.data;
    }
  } catch (error: any) {
    console.error('Error loading MFA status:', error);
  }
};

const updateEmail = async () => {
  // Reset errors
  errors.newEmail = [];
  errors.confirmEmail = [];
  errorMessage.value = '';

  // Validate form
  if (!emailForm.value.newEmail) {
    errors.newEmail = ['Email baru harus diisi'];
    return;
  }

  if (!emailForm.value.confirmEmail) {
    errors.confirmEmail = ['Konfirmasi email harus diisi'];
    return;
  }

  if (emailForm.value.newEmail !== emailForm.value.confirmEmail) {
    errors.confirmEmail = ['Email tidak sama'];
    return;
  }

  if (emailForm.value.newEmail === emailForm.value.currentEmail) {
    errorMessage.value = 'Email baru harus berbeda dengan email saat ini';
    return;
  }

  loading.value = true;

  try {
    const response = await api.put('/users/profile', {
      email: emailForm.value.newEmail
    });

    if (response.data.success) {
      successMessage.value = 'Email berhasil diupdate!';
      emailForm.value.currentEmail = emailForm.value.newEmail;
      emailForm.value.newEmail = '';
      emailForm.value.confirmEmail = '';
      
      // Reload user info
      await authStore.getMe();
      loadUserInfo();
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('Error updating email:', error);
    
    if (error.response?.status === 422 && error.response.data?.errors) {
      const errorData = error.response.data.errors;
      if (errorData.email) {
        errors.newEmail = errorData.email;
      }
    } else {
      errorMessage.value = error.response?.data?.message || 'Gagal mengupdate email';
    }
  } finally {
    loading.value = false;
  }
};

const toggleMFA = async () => {
  mfaLoading.value = true;
  errorMessage.value = '';

  try {
    const endpoint = mfaStatus.value.mfa_enabled ? '/mfa/disable' : '/mfa/enable';
    const response = await api.post(endpoint);

    if (response.data.success) {
      successMessage.value = response.data.message;
      await loadMFAStatus();
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('Error toggling MFA:', error);
    errorMessage.value = error.response?.data?.message || 'Gagal mengubah status MFA';
  } finally {
    mfaLoading.value = false;
  }
};

onMounted(() => {
  loadUserInfo();
  loadMFAStatus();
});
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}
</style>
