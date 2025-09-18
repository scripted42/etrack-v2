<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card class="elevation-12" rounded="lg">
          <v-card-title class="text-center pa-8">
            <v-icon size="64" color="primary" class="mb-4">mdi-shield-check</v-icon>
            <h2 class="text-h5 font-weight-bold">Verifikasi MFA</h2>
            <p class="text-subtitle-1 text-medium-emphasis mt-2">
              Masukkan kode OTP yang dikirim ke email Anda
            </p>
          </v-card-title>

          <v-card-text class="pa-8">
            <v-form @submit.prevent="verifyOTP" ref="otpForm">
              <v-text-field
                v-model="otp"
                label="Kode OTP"
                prepend-inner-icon="mdi-key"
                variant="outlined"
                :error-messages="errors.otp"
                :disabled="loading"
                maxlength="6"
                required
                class="mb-4"
                @input="formatOTP"
              />

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

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="loading"
                :disabled="loading || otp.length !== 6"
                class="mb-4"
              >
                <v-icon left>mdi-check</v-icon>
                Verifikasi
              </v-btn>

              <v-btn
                variant="outlined"
                size="large"
                block
                :loading="requestingOTP"
                :disabled="requestingOTP"
                @click="requestNewOTP"
                class="mb-4"
              >
                <v-icon left>mdi-refresh</v-icon>
                Kirim Ulang OTP
              </v-btn>

              <div class="text-center">
                <p class="text-caption text-medium-emphasis">
                  Kode OTP berlaku selama 5 menit
                </p>
                <p class="text-caption text-medium-emphasis">
                  Tidak menerima email? Cek folder spam
                </p>
              </div>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const otpForm = ref();
const loading = ref(false);
const requestingOTP = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const otp = ref('');
const errors = reactive({
  otp: [] as string[]
});

const formatOTP = () => {
  // Remove non-numeric characters
  otp.value = otp.value.replace(/\D/g, '');
  // Limit to 6 digits
  otp.value = otp.value.substring(0, 6);
};

const verifyOTP = async () => {
  // Reset errors
  errors.otp = [];
  errorMessage.value = '';

  // Validate form
  const { valid } = await otpForm.value.validate();
  if (!valid) return;

  if (otp.value.length !== 6) {
    errors.otp = ['Kode OTP harus 6 digit'];
    return;
  }

  loading.value = true;

  try {
    const response = await api.post('/mfa/verify-otp', {
      otp: otp.value
    });

    if (response.data.success) {
      successMessage.value = response.data.message;
      // Redirect to dashboard after successful verification
      setTimeout(() => {
        router.push('/dashboard');
      }, 2000);
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('OTP verification error:', error);
    
    if (error.response?.status === 422 && error.response.data?.errors) {
      const errorData = error.response.data.errors;
      if (errorData.otp) {
        errors.otp = errorData.otp;
      }
    } else {
      errorMessage.value = error.response?.data?.message || 'Gagal verifikasi OTP';
    }
  } finally {
    loading.value = false;
  }
};

const requestNewOTP = async () => {
  requestingOTP.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/mfa/request-otp');
    if (response.data.success) {
      successMessage.value = response.data.message;
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error: any) {
    console.error('Error requesting OTP:', error);
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim OTP';
  } finally {
    requestingOTP.value = false;
  }
};
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.v-card {
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}
</style>
