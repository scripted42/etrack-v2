import { createRouter, createWebHistory } from 'vue-router';
import MainRoutes from './MainRoutes';
import PublicRoutes from './PublicRoutes';
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';

export const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login'
    },
    PublicRoutes,
    MainRoutes,
    {
      path: '/:pathMatch(.*)*',
      component: () => import('@/views/pages/maintenance/error/Error404Page.vue')
    }
  ]
});

router.beforeEach(async (to, from, next) => {
  try {
    const auth = useAuthStore();
    
    // Public pages that don't require authentication
    const publicPages = ['/login', '/error'];
    const isPublicPage = publicPages.includes(to.path);
    
    // Check if route requires authentication
    const authRequired = to.matched.some((record) => record.meta.requiresAuth);

    // Normalize inconsistent state: user exists but token missing
    if (auth.user && !auth.token) {
      auth.logout();
    }
    
    // If user is not logged in and trying to access protected route
    if (authRequired && !auth.isLoggedIn) {
      auth.returnUrl = to.fullPath;
      next('/login');
    }
    // If user is logged in and trying to access login page
    else if (auth.isLoggedIn && to.path === '/login') {
      next('/dashboard');
    }
    // Allow access to public pages or authenticated routes
    else {
      next();
    }
  } catch (error) {
    console.error('Router guard error:', error);
    next('/login');
  }
});

router.beforeEach(() => {
  const uiStore = useUIStore();
  uiStore.isLoading = true;
});

router.afterEach(() => {
  const uiStore = useUIStore();
  uiStore.isLoading = false;
});
