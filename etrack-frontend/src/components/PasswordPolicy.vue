<template>
  <div class="password-policy">
    <!-- Password Input -->
    <v-text-field
      v-model="password"
      :type="showPassword ? 'text' : 'password'"
      :label="label"
      :placeholder="placeholder"
      :error-messages="errorMessages"
      :rules="passwordRules"
      :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
      @click:append-inner="showPassword = !showPassword"
      @input="validatePassword"
      variant="outlined"
      density="comfortable"
    />

    <!-- Password Strength Indicator -->
    <div v-if="password && showStrength" class="password-strength mt-2">
      <div class="d-flex align-center mb-2">
        <span class="text-caption text-medium-emphasis mr-2">Kekuatan Password:</span>
        <v-chip
          :color="getStrengthColor(validationData.strength)"
          size="small"
          variant="flat"
        >
          {{ validationData.strength_description || 'Memvalidasi...' }}
        </v-chip>
      </div>
      
      <!-- Strength Bar -->
      <v-progress-linear
        :model-value="validationData.strength"
        :color="getStrengthColor(validationData.strength)"
        height="4"
        rounded
        class="mb-2"
      />
      
      <!-- Validation Errors -->
      <div v-if="validationData.errors && validationData.errors.length > 0" class="validation-errors">
        <v-alert
          type="error"
          variant="tonal"
          density="compact"
          class="mb-2"
        >
          <div class="text-caption">
            <div v-for="error in validationData.errors" :key="error" class="mb-1">
              <v-icon size="small" class="mr-1">mdi-close-circle</v-icon>
              {{ error }}
            </div>
          </div>
        </v-alert>
      </div>
      
      <!-- Password Suggestion -->
      <div v-if="validationData.suggestion" class="password-suggestion">
        <v-alert
          type="info"
          variant="tonal"
          density="compact"
          class="mb-2"
        >
          <div class="text-caption">
            <div class="d-flex align-center mb-1">
              <v-icon size="small" class="mr-1">mdi-lightbulb</v-icon>
              <strong>Saran Password:</strong>
            </div>
            <div class="d-flex align-center">
              <code class="text-caption">{{ validationData.suggestion }}</code>
              <v-btn
                size="x-small"
                variant="text"
                @click="useSuggestion"
                class="ml-2"
              >
                <v-icon size="small">mdi-content-copy</v-icon>
              </v-btn>
            </div>
          </div>
        </v-alert>
      </div>
    </div>

    <!-- Password Requirements -->
    <div v-if="showRequirements" class="password-requirements mt-2">
      <v-expansion-panels variant="accordion" density="compact">
        <v-expansion-panel>
          <v-expansion-panel-title>
            <div class="d-flex align-center">
              <v-icon size="small" class="mr-2">mdi-shield-check</v-icon>
              <span class="text-caption">Persyaratan Password</span>
            </div>
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <div class="text-caption">
              <div class="mb-1">
                <v-icon 
                  :color="password.length >= 8 ? 'success' : 'error'" 
                  size="small" 
                  class="mr-1"
                >
                  {{ password.length >= 8 ? 'mdi-check' : 'mdi-close' }}
                </v-icon>
                Minimal 8 karakter
              </div>
              <div class="mb-1">
                <v-icon 
                  :color="hasUppercase ? 'success' : 'error'" 
                  size="small" 
                  class="mr-1"
                >
                  {{ hasUppercase ? 'mdi-check' : 'mdi-close' }}
                </v-icon>
                Mengandung huruf kapital (A-Z)
              </div>
              <div class="mb-1">
                <v-icon 
                  :color="hasLowercase ? 'success' : 'error'" 
                  size="small" 
                  class="mr-1"
                >
                  {{ hasLowercase ? 'mdi-check' : 'mdi-close' }}
                </v-icon>
                Mengandung huruf kecil (a-z)
              </div>
              <div class="mb-1">
                <v-icon 
                  :color="hasNumber ? 'success' : 'error'" 
                  size="small" 
                  class="mr-1"
                >
                  {{ hasNumber ? 'mdi-check' : 'mdi-close' }}
                </v-icon>
                Mengandung angka (0-9)
              </div>
              <div class="mb-1">
                <v-icon 
                  :color="hasSpecial ? 'success' : 'error'" 
                  size="small" 
                  class="mr-1"
                >
                  {{ hasSpecial ? 'mdi-check' : 'mdi-close' }}
                </v-icon>
                Mengandung karakter khusus (!@#$%^&*)
              </div>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useApi } from '@/utils/helpers/api'

interface PasswordValidation {
  is_valid: boolean
  errors: string[]
  strength: number
  strength_description: string
  suggestion?: string
}

interface Props {
  modelValue: string
  label?: string
  placeholder?: string
  showStrength?: boolean
  showRequirements?: boolean
  errorMessages?: string[]
  rules?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Password',
  placeholder: 'Masukkan password',
  showStrength: true,
  showRequirements: true,
  errorMessages: () => [],
  rules: () => []
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'validation-change': [validation: PasswordValidation]
}>()

const api = useApi()
const password = ref(props.modelValue)
const showPassword = ref(false)
const validationData = ref<PasswordValidation>({
  is_valid: false,
  errors: [],
  strength: 0,
  strength_description: '',
  suggestion: undefined
})

// Computed properties for requirements
const hasUppercase = computed(() => /[A-Z]/.test(password.value))
const hasLowercase = computed(() => /[a-z]/.test(password.value))
const hasNumber = computed(() => /[0-9]/.test(password.value))
const hasSpecial = computed(() => /[^A-Za-z0-9]/.test(password.value))

// Password rules
const passwordRules = computed(() => [
  ...props.rules,
  (v: string) => {
    if (!v) return true
    return validationData.value.is_valid || 'Password tidak memenuhi standar keamanan'
  }
])

// Watch for password changes
watch(password, (newValue) => {
  emit('update:modelValue', newValue)
})

// Validate password with backend
const validatePassword = async () => {
  if (!password.value) {
    validationData.value = {
      is_valid: false,
      errors: [],
      strength: 0,
      strength_description: '',
      suggestion: undefined
    }
    return
  }

  try {
    const response = await api.post('/validate-password', {
      password: password.value
    })
    
    if (response.data.success) {
      validationData.value = response.data.data
      emit('validation-change', validationData.value)
    }
  } catch (error) {
    console.error('Password validation error:', error)
  }
}

// Get strength color
const getStrengthColor = (strength: number): string => {
  if (strength >= 80) return 'success'
  if (strength >= 60) return 'info'
  if (strength >= 40) return 'warning'
  if (strength >= 20) return 'error'
  return 'error'
}

// Use suggestion
const useSuggestion = () => {
  if (validationData.value.suggestion) {
    password.value = validationData.value.suggestion
    validatePassword()
  }
}

// Debounced validation
let validationTimeout: NodeJS.Timeout
watch(password, () => {
  clearTimeout(validationTimeout)
  validationTimeout = setTimeout(() => {
    validatePassword()
  }, 500)
})
</script>

<style scoped>
.password-policy {
  width: 100%;
}

.validation-errors {
  margin-top: 8px;
}

.password-requirements {
  margin-top: 8px;
}

.password-suggestion {
  margin-top: 8px;
}

.validation-errors .v-alert {
  border-radius: 8px;
}

.password-suggestion .v-alert {
  border-radius: 8px;
}

code {
  background-color: rgba(var(--v-theme-surface-variant), 0.1);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}
</style>
