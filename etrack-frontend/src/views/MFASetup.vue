<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card class="elevation-4">
          <v-card-title class="text-center pa-6">
            <v-icon size="48" color="primary" class="mb-4">mdi-shield-check</v-icon>
            <h2 class="text-h4 font-weight-bold">Multi-Factor Authentication</h2>
            <p class="text-subtitle-1 text-medium-emphasis mt-2">
              Tingkatkan keamanan akun Anda dengan MFA
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

            <!-- MFA Status -->
            <v-card variant="outlined" class="mb-6">
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
                
                <p class="text-body-2">
                  Email: <strong>{{ mfaStatus.email }}</strong>
                </p>
              </v-card-text>
            </v-card>

            <!-- MFA Actions -->
            <div v-if="!mfaStatus.mfa_enabled" class="text-center">
              <v-btn
                color="primary"
                size="large"
                :loading="loading"
                @click="enableMFA"
                class="mb-4"
              >
                <v-icon left>mdi-shield-plus</v-icon>
                Aktifkan MFA
              </v-btn>
              
              <p class="text-caption text-medium-emphasis">
                Setelah mengaktifkan MFA, Anda akan menerima kode OTP via email setiap kali login.
              </p>
            </div>

            <div v-else class="text-center">
              <v-btn
                color="error"
                variant="outlined"
                size="large"
                :loading="loading"
                @click="disableMFA"
                class="mb-4"
              >
                <v-icon left>mdi-shield-remove</v-icon>
                Nonaktifkan MFA
              </v-btn>
              
              <p class="text-caption text-medium-emphasis">
                MFA aktif. Anda akan menerima kode OTP via email saat login.
              </p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const mfaStatus = ref({
  mfa_enabled: false,
  email: ''
});

const loadMFAStatus = async () => {
  try {
    const response = await api.get('/mfa/status');
    if (response.data.success) {
      mfaStatus.value = response.data.data;
    }
  } catch (error: any) {
    console.error('Error loading MFA status:', error);
    errorMessage.value = 'Gagal memuat status MFA';
  }
};

const enableMFA = async () => {
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await api.post('/mfa/enable');
    if (response.data.success) {
      successMessage.value = response.data.message;
      await loadMFAStatus();
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('Error enabling MFA:', error);
    errorMessage.value = error.response?.data?.message || 'Gagal mengaktifkan MFA';
  } finally {
    loading.value = false;
  }
};

const disableMFA = async () => {
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await api.post('/mfa/disable');
    if (response.data.success) {
      successMessage.value = response.data.message;
      await loadMFAStatus();
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('Error disabling MFA:', error);
    errorMessage.value = error.response?.data?.message || 'Gagal menonaktifkan MFA';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadMFAStatus();
});
</script>

<style scoped>
.v-card {
  border-radius: 12px;
}
</style>
