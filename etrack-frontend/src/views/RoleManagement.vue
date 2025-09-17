<template>
  <div class="pa-6">
    <!-- Header -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="mr-3">mdi-shield-account</v-icon>
        <span>Manajemen Role & Permission</span>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="openRoleDialog()"
        >
          Tambah Role
        </v-btn>
      </v-card-title>
    </v-card>

    <!-- Statistics Cards -->
    <v-row class="mb-6">
      <v-col cols="12" md="3">
        <v-card color="primary" variant="tonal">
          <v-card-text class="text-center">
            <v-icon size="48" color="primary" class="mb-2">mdi-shield-account</v-icon>
            <div class="text-h4 font-weight-bold">{{ statistics.total_roles }}</div>
            <div class="text-body-2">Total Roles</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="success" variant="tonal">
          <v-card-text class="text-center">
            <v-icon size="48" color="success" class="mb-2">mdi-key</v-icon>
            <div class="text-h4 font-weight-bold">{{ statistics.total_permissions }}</div>
            <div class="text-body-2">Total Permissions</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="info" variant="tonal">
          <v-card-text class="text-center">
            <v-icon size="48" color="info" class="mb-2">mdi-account-group</v-icon>
            <div class="text-h4 font-weight-bold">{{ statistics.roles_with_users }}</div>
            <div class="text-body-2">Roles dengan User</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="warning" variant="tonal">
          <v-card-text class="text-center">
            <v-icon size="48" color="warning" class="mb-2">mdi-chart-pie</v-icon>
            <div class="text-h4 font-weight-bold">{{ roleUsage.length }}</div>
            <div class="text-body-2">Role Usage</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Roles Table -->
    <v-card class="mb-6">
      <v-card-title class="d-flex align-center">
        <v-icon color="primary" class="mr-2">mdi-shield-account</v-icon>
        <span>Daftar Roles</span>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          variant="outlined"
          prepend-icon="mdi-refresh"
          @click="loadRoles"
          :loading="loading"
        >
          Refresh
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-data-table
          :headers="roleHeaders"
          :items="roles"
          :loading="loading"
          class="elevation-1"
        >
          <template v-slot:item.permissions="{ item }">
            <v-chip
              v-for="permission in item.permissions.slice(0, 3)"
              :key="permission.id"
              size="small"
              class="mr-1 mb-1"
            >
              {{ permission.display_name }}
            </v-chip>
            <v-chip
              v-if="item.permissions.length > 3"
              size="small"
              color="grey"
            >
              +{{ item.permissions.length - 3 }} lainnya
            </v-chip>
          </template>
          
          <template v-slot:item.actions="{ item }">
            <v-btn
              icon="mdi-pencil"
              size="small"
              @click="openRoleDialog(item)"
            ></v-btn>
            <v-btn
              icon="mdi-delete"
              size="small"
              color="error"
              @click="deleteRole(item)"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Permissions Table -->
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon color="success" class="mr-2">mdi-key</v-icon>
        <span>Daftar Permissions</span>
        <v-spacer></v-spacer>
        <v-btn
          color="success"
          prepend-icon="mdi-plus"
          @click="openPermissionDialog()"
        >
          Tambah Permission
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-data-table
          :headers="permissionHeaders"
          :items="permissions"
          :loading="loadingPermissions"
          class="elevation-1"
        >
          <template v-slot:item.group="{ item }">
            <v-chip
              :color="getGroupColor(item.group)"
              size="small"
            >
              {{ item.group || 'General' }}
            </v-chip>
          </template>
          
          <template v-slot:item.actions="{ item }">
            <v-btn
              icon="mdi-pencil"
              size="small"
              @click="openPermissionDialog(item)"
            ></v-btn>
            <v-btn
              icon="mdi-delete"
              size="small"
              color="error"
              @click="deletePermission(item)"
            ></v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Role Dialog -->
    <v-dialog v-model="roleDialog" max-width="600">
      <v-card>
        <v-card-title>
          <v-icon class="mr-2">{{ editingRole ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
          {{ editingRole ? 'Edit Role' : 'Tambah Role' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="roleForm" v-model="roleFormValid">
            <v-text-field
              v-model="roleForm.name"
              label="Nama Role"
              :rules="[v => !!v || 'Nama role wajib diisi']"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
            
            <v-text-field
              v-model="roleForm.display_name"
              label="Display Name"
              :rules="[v => !!v || 'Display name wajib diisi']"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
            
            <v-textarea
              v-model="roleForm.description"
              label="Deskripsi"
              variant="outlined"
              class="mb-4"
            ></v-textarea>
            
            <v-select
              v-model="roleForm.permissions"
              :items="permissions"
              item-title="display_name"
              item-value="id"
              label="Permissions"
              multiple
              chips
              variant="outlined"
            ></v-select>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="roleDialog = false">Batal</v-btn>
          <v-btn
            color="primary"
            @click="saveRole"
            :loading="saving"
            :disabled="!roleFormValid"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Permission Dialog -->
    <v-dialog v-model="permissionDialog" max-width="500">
      <v-card>
        <v-card-title>
          <v-icon class="mr-2">{{ editingPermission ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
          {{ editingPermission ? 'Edit Permission' : 'Tambah Permission' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="permissionForm" v-model="permissionFormValid">
            <v-text-field
              v-model="permissionForm.name"
              label="Nama Permission"
              :rules="[v => !!v || 'Nama permission wajib diisi']"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
            
            <v-text-field
              v-model="permissionForm.display_name"
              label="Display Name"
              :rules="[v => !!v || 'Display name wajib diisi']"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
            
            <v-text-field
              v-model="permissionForm.group"
              label="Group"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
            
            <v-textarea
              v-model="permissionForm.description"
              label="Deskripsi"
              variant="outlined"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="permissionDialog = false">Batal</v-btn>
          <v-btn
            color="primary"
            @click="savePermission"
            :loading="saving"
            :disabled="!permissionFormValid"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

// Reactive data
const roles = ref([])
const permissions = ref([])
const statistics = ref({
  total_roles: 0,
  total_permissions: 0,
  roles_with_users: 0
})
const roleUsage = ref([])
const loading = ref(false)
const loadingPermissions = ref(false)
const saving = ref(false)

// Dialog states
const roleDialog = ref(false)
const permissionDialog = ref(false)
const editingRole = ref(null)
const editingPermission = ref(null)

// Form data
const roleForm = ref({
  name: '',
  display_name: '',
  description: '',
  permissions: []
})
const permissionForm = ref({
  name: '',
  display_name: '',
  description: '',
  group: ''
})

// Form validation
const roleFormValid = ref(false)
const permissionFormValid = ref(false)

// Table headers
const roleHeaders = [
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Display Name', key: 'display_name', sortable: true },
  { title: 'Deskripsi', key: 'description', sortable: true },
  { title: 'Permissions', key: 'permissions', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false }
]

const permissionHeaders = [
  { title: 'Nama', key: 'name', sortable: true },
  { title: 'Display Name', key: 'display_name', sortable: true },
  { title: 'Group', key: 'group', sortable: true },
  { title: 'Deskripsi', key: 'description', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Methods
const loadRoles = async () => {
  try {
    loading.value = true
    const response = await api.get('/roles')
    roles.value = response.data.data || []
  } catch (error) {
    console.error('Error loading roles:', error)
  } finally {
    loading.value = false
  }
}

const loadPermissions = async () => {
  try {
    loadingPermissions.value = true
    const response = await api.get('/permissions')
    permissions.value = response.data.data || []
  } catch (error) {
    console.error('Error loading permissions:', error)
  } finally {
    loadingPermissions.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await api.get('/roles/1/statistics')
    statistics.value = response.data.data
    roleUsage.value = response.data.data.role_usage || []
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const openRoleDialog = (role = null) => {
  editingRole.value = role
  if (role) {
    roleForm.value = {
      name: role.name,
      display_name: role.display_name,
      description: role.description || '',
      permissions: role.permissions?.map(p => p.id) || []
    }
  } else {
    roleForm.value = {
      name: '',
      display_name: '',
      description: '',
      permissions: []
    }
  }
  roleDialog.value = true
}

const openPermissionDialog = (permission = null) => {
  editingPermission.value = permission
  if (permission) {
    permissionForm.value = {
      name: permission.name,
      display_name: permission.display_name,
      description: permission.description || '',
      group: permission.group || ''
    }
  } else {
    permissionForm.value = {
      name: '',
      display_name: '',
      description: '',
      group: ''
    }
  }
  permissionDialog.value = true
}

const saveRole = async () => {
  try {
    saving.value = true
    const url = editingRole.value ? `/roles/${editingRole.value.id}` : '/roles'
    const method = editingRole.value ? 'put' : 'post'
    
    await api[method](url, roleForm.value)
    
    roleDialog.value = false
    loadRoles()
    loadStatistics()
  } catch (error) {
    console.error('Error saving role:', error)
  } finally {
    saving.value = false
  }
}

const savePermission = async () => {
  try {
    saving.value = true
    const url = editingPermission.value ? `/permissions/${editingPermission.value.id}` : '/permissions'
    const method = editingPermission.value ? 'put' : 'post'
    
    await api[method](url, permissionForm.value)
    
    permissionDialog.value = false
    loadPermissions()
    loadStatistics()
  } catch (error) {
    console.error('Error saving permission:', error)
  } finally {
    saving.value = false
  }
}

const deleteRole = async (role) => {
  if (confirm(`Apakah Anda yakin ingin menghapus role "${role.display_name}"?`)) {
    try {
      await api.delete(`/roles/${role.id}`)
      loadRoles()
      loadStatistics()
    } catch (error) {
      console.error('Error deleting role:', error)
    }
  }
}

const deletePermission = async (permission) => {
  if (confirm(`Apakah Anda yakin ingin menghapus permission "${permission.display_name}"?`)) {
    try {
      await api.delete(`/permissions/${permission.id}`)
      loadPermissions()
      loadStatistics()
    } catch (error) {
      console.error('Error deleting permission:', error)
    }
  }
}

const getGroupColor = (group) => {
  const colors = {
    'dashboard': 'primary',
    'students': 'success',
    'employees': 'info',
    'users': 'warning',
    'reports': 'error',
    'settings': 'grey'
  }
  return colors[group] || 'grey'
}

// Lifecycle
onMounted(() => {
  loadRoles()
  loadPermissions()
  loadStatistics()
})
</script>
