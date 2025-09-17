<template>
  <div class="change-password-page">
    <v-container fluid>
      <v-row justify="center">
        <v-col cols="12" sm="8" md="6" lg="4">
          <v-card class="pa-6" elevation="2">
            <v-card-title class="text-center mb-6">
              <div class="d-flex align-center justify-center">
                <v-icon color="primary" size="large" class="mr-2">mdi-shield-key</v-icon>
                <span class="text-h5">Ubah Password</span>
              </div>
            </v-card-title>

            <v-form ref="formRef" v-model="formValid" @submit.prevent="changePassword">
              <!-- Current Password -->
              <div class="mb-4">
                <v-text-field
                  v-model="form.currentPassword"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  label="Password Lama"
                  placeholder="Masukkan password lama"
                  :error-messages="errors.currentPassword"
                  :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="showCurrentPassword = !showCurrentPassword"
                  variant="outlined"
                  density="comfortable"
                  required
                />
              </div>

              <!-- New Password -->
              <div class="mb-4">
                <PasswordPolicy
                  v-model="form.newPassword"
                  label="Password Baru"
                  placeholder="Masukkan password baru"
                  :error-messages="errors.newPassword"
                  :show-strength="true"
                  :show-requirements="true"
                  @validation-change="onPasswordValidationChange"
                />
              </div>

              <!-- Confirm Password -->
              <div class="mb-4">
                <v-text-field
                  v-model="form.confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  label="Konfirmasi Password"
                  placeholder="Konfirmasi password baru"
                  :error-messages="errors.confirmPassword"
                  :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="showConfirmPassword = !showConfirmPassword"
                  variant="outlined"
                  density="comfortable"
                  required
                />
              </div>

              <!-- Password Match Indicator -->
              <div v-if="form.newPassword && form.confirmPassword" class="mb-4">
                <v-alert
                  :type="passwordsMatch ? 'success' : 'error'"
                  variant="tonal"
                  density="compact"
                  class="mb-2"
                >
                  <div class="d-flex align-center">
                    <v-icon size="small" class="mr-2">
                      {{ passwordsMatch ? 'mdi-check-circle' : 'mdi-close-circle' }}
                    </v-icon>
                    <span class="text-caption">
                      {{ passwordsMatch ? 'Password cocok' : 'Password tidak cocok' }}
                    </span>
                  </div>
                </v-alert>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex gap-3">
                <v-btn
                  color="secondary"
                  variant="outlined"
                  block
                  @click="resetForm"
                  :disabled="loading"
                >
                  <v-icon class="mr-2">mdi-refresh</v-icon>
                  Reset
                </v-btn>
                
                <v-btn
                  color="primary"
                  variant="flat"
                  block
                  type="submit"
                  :loading="loading"
                  :disabled="!canSubmit"
                >
                  <v-icon class="mr-2">mdi-shield-check</v-icon>
                  Ubah Password
                </v-btn>
              </div>
            </v-form>

            <!-- Success Message -->
            <v-alert
              v-if="successMessage"
              type="success"
              variant="tonal"
              class="mt-4"
              closable
              @click:close="successMessage = ''"
            >
              <div class="d-flex align-center">
                <v-icon class="mr-2">mdi-check-circle</v-icon>
                {{ successMessage }}
              </div>
            </v-alert>

            <!-- Error Message -->
            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              class="mt-4"
              closable
              @click:close="errorMessage = ''"
            >
              <div class="d-flex align-center">
                <v-icon class="mr-2">mdi-alert-circle</v-icon>
                {{ errorMessage }}
              </div>
            </v-alert>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/utils/helpers/api'
import PasswordPolicy from '@/components/PasswordPolicy.vue'

interface FormData {
  currentPassword: string
  newPassword: string
  confirmPassword: string
}

interface FormErrors {
  currentPassword: string[]
  newPassword: string[]
  confirmPassword: string[]
}

const router = useRouter()
const api = useApi()

// Form state
const formRef = ref()
const formValid = ref(false)
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Form data
const form = ref<FormData>({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// Form errors
const errors = ref<FormErrors>({
  currentPassword: [],
  newPassword: [],
  confirmPassword: []
})

// Password visibility
const showCurrentPassword = ref(false)
const showConfirmPassword = ref(false)

// Password validation state
const passwordValidation = ref({
  is_valid: false,
  strength: 0
})

// Computed properties
const passwordsMatch = computed(() => {
  return form.value.newPassword && form.value.confirmPassword && 
         form.value.newPassword === form.value.confirmPassword
})

const canSubmit = computed(() => {
  return formValid.value && 
         form.value.currentPassword && 
         form.value.newPassword && 
         form.value.confirmPassword &&
         passwordsMatch.value &&
         passwordValidation.value.is_valid
})

// Methods
const onPasswordValidationChange = (validation: any) => {
  passwordValidation.value = validation
}

const resetForm = () => {
  form.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  }
  errors.value = {
    currentPassword: [],
    newPassword: [],
    confirmPassword: []
  }
  successMessage.value = ''
  errorMessage.value = ''
  formRef.value?.reset()
}

const changePassword = async () => {
  if (!canSubmit.value) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/change-password', {
      current_password: form.value.currentPassword,
      new_password: form.value.newPassword,
      new_password_confirmation: form.value.confirmPassword
    })

    if (response.data.success) {
      successMessage.value = response.data.message
      resetForm()
      
      // Redirect after 2 seconds
      setTimeout(() => {
        router.push('/dashboard')
      }, 2000)
    }
  } catch (error: any) {
    console.error('Change password error:', error)
    
    if (error.response?.data?.errors) {
      const apiErrors = error.response.data.errors
      errors.value = {
        currentPassword: apiErrors.current_password || [],
        newPassword: apiErrors.new_password || [],
        confirmPassword: apiErrors.new_password_confirmation || []
      }
    } else {
      errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengubah password'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.change-password-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem 0;
}

.v-card {
  border-radius: 12px;
}

.v-btn {
  border-radius: 8px;
}

.gap-3 {
  gap: 12px;
}
</style>
