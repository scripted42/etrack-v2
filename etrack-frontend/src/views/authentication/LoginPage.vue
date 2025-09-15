<template>
  <v-app>
    <v-main>
      <v-container fluid class="fill-height">
        <v-row align="center" justify="center" class="fill-height">
          <v-col cols="12" sm="8" md="6" lg="4" xl="3">
            <v-card class="elevation-12" rounded="lg">
              <v-card-title class="text-center pa-8">
                <div class="d-flex flex-column align-center">
                  <v-avatar size="80" class="mb-4">
                    <v-img src="/logo-smpn14.png" alt="Logo SMPN 14 Surabaya" />
                  </v-avatar>
                  <h2 class="text-h5 font-weight-bold text-primary">E-Track</h2>
                  <p class="text-subtitle-1 text-medium-emphasis mt-2">
                    Tracking & Manajemen Data
                  </p>
                  <p class="text-subtitle-2 text-medium-emphasis">
                    SMP Negeri 14 Surabaya
                  </p>
                </div>
              </v-card-title>

              <v-card-text class="pa-8">
                <v-form @submit.prevent="handleLogin" ref="loginForm">
                  <v-text-field
                    v-model="form.username"
                    label="Username atau Email"
                    prepend-inner-icon="mdi-account"
                    variant="outlined"
                    :error-messages="errors.username"
                    :disabled="loading"
                    required
                    class="mb-4"
                  />

                  <v-text-field
                    v-model="form.password"
                    label="Password"
                    prepend-inner-icon="mdi-lock"
                    variant="outlined"
                    type="password"
                    :error-messages="errors.password"
                    :disabled="loading"
                    required
                    class="mb-4"
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

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                    :disabled="loading"
                    class="mb-4"
                  >
                    <v-icon left>mdi-login</v-icon>
                    Masuk
                  </v-btn>
                </v-form>
              </v-card-text>

              <v-card-actions class="pa-8 pt-0">
                <v-divider class="mb-4" />
                <div class="text-center w-100">
                  <p class="text-caption text-medium-emphasis">
                    © 2024 SMP Negeri 14 Surabaya. All rights reserved.
                  </p>
                  <p class="text-caption text-medium-emphasis">
                    Sistem Manajemen Data Siswa & Pegawai
                  </p>
                </div>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import type { LoginCredentials } from '@/types/user';

const router = useRouter();
const authStore = useAuthStore();

const loginForm = ref();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive<LoginCredentials>({
  username: '',
  password: ''
});

const errors = reactive({
  username: [] as string[],
  password: [] as string[]
});

const handleLogin = async () => {
  // Reset errors
  errors.username = [];
  errors.password = [];
  errorMessage.value = '';

  // Validate form
  const { valid } = await loginForm.value.validate();
  if (!valid) return;

  loading.value = true;

  try {
    const returnUrl = await authStore.login(form.username, form.password);
    router.push(returnUrl);
  } catch (error: any) {
    console.error('Login error:', error);
    
    if (error.response?.data?.errors) {
      // Handle validation errors
      const errorData = error.response.data.errors;
      if (errorData.username) errors.username = errorData.username;
      if (errorData.password) errors.password = errorData.password;
    } else {
      // Handle general error
      errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat login. Silakan coba lagi.';
    }
  } finally {
    loading.value = false;
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