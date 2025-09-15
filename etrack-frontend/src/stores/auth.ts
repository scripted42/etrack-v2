import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User } from '@/types/user';
import apiService from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
  // initial state
  const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'));
  const token = ref<string | null>(localStorage.getItem('auth_token'));
  const returnUrl = ref<string | null>(null);

  // computed
  const isLoggedIn = computed(() => !!user.value && !!token.value);

  // actions
  async function login(username: string, password: string) {
    try {
      const response = await apiService.login({ username, password });
      
      if (response.success) {
        // update pinia state
        user.value = response.data.user;
        token.value = response.data.token;

        // store user details and jwt in local storage
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('auth_token', response.data.token);

        return returnUrl.value || '/dashboard';
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error: any) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  async function logout() {
    try {
      await apiService.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      user.value = null;
      token.value = null;
      localStorage.removeItem('user');
      localStorage.removeItem('auth_token');
    }
  }

  async function getMe() {
    try {
      const response = await apiService.getMe();
      if (response.success) {
        user.value = response.data;
        localStorage.setItem('user', JSON.stringify(response.data));
      }
    } catch (error) {
      console.error('Get user info failed:', error);
      logout();
    }
  }

  async function changePassword(currentPassword: string, newPassword: string, confirmPassword: string) {
    try {
      const response = await apiService.changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword
      });
      return response;
    } catch (error) {
      console.error('Change password failed:', error);
      throw error;
    }
  }

  return {
    user,
    token,
    returnUrl,
    isLoggedIn,
    login,
    logout,
    getMe,
    changePassword
  };
});