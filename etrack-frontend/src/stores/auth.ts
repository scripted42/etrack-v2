import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User } from '@/types/user';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
  // initial state
  const user = ref<User | null>(null);
  const token = ref<string | null>(null);
  const returnUrl = ref<string | null>(null);

  // Initialize from localStorage
  try {
    const userData = localStorage.getItem('user');
    const tokenData = localStorage.getItem('authToken');
    
    if (userData && userData !== 'undefined' && userData !== 'null') {
      user.value = JSON.parse(userData);
    }
    
    if (tokenData && tokenData !== 'undefined' && tokenData !== 'null') {
      token.value = tokenData;
    }
  } catch (error) {
    console.error('Error parsing user data from localStorage:', error);
    localStorage.removeItem('user');
    localStorage.removeItem('authToken');
    user.value = null;
    token.value = null;
  }

  // computed
  const isLoggedIn = computed(() => !!user.value && !!token.value);

  // actions
  async function login(username: string, password: string) {
    try {
      const response = await api.post('/login', { username, password });
      
      const payload = response.data?.data;
      if (response.data?.success && payload?.user && payload?.token) {
        // update pinia state
        user.value = payload.user;
        token.value = payload.token;

        // store user details and jwt in local storage
        localStorage.setItem('user', JSON.stringify(payload.user));
        localStorage.setItem('authToken', payload.token);

        // Return response with MFA status
        return {
          returnUrl: returnUrl.value || '/dashboard',
          mfa_required: payload.mfa_required || false
        };
      } else {
        throw new Error('Login failed');
      }
    } catch (error: any) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  async function logout() {
    try {
      await api.post('/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      user.value = null;
      token.value = null;
      localStorage.clear(); // Clear all localStorage
    }
  }

  async function getMe() {
    try {
      const response = await api.get('/me');
      if (response.data) {
        user.value = response.data;
        localStorage.setItem('user', JSON.stringify(response.data));
      }
    } catch (error) {
      console.error('Get user info failed:', error);
      logout();
    }
  }

  return {
    user,
    token,
    returnUrl,
    isLoggedIn,
    login,
    logout,
    getMe
  };
});