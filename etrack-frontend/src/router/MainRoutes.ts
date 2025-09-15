const MainRoutes = {
  path: '/main',
  meta: {
    requiresAuth: true
  },
  redirect: '/dashboard',
  component: () => import('@/layouts/dashboard/DashboardLayout.vue'),
  children: [
    {
      name: 'LandingPage',
      path: '/',
      component: () => import('@/views/dashboard/DefaultDashboard.vue')
    },
    {
      name: 'Dashboard',
      path: '/dashboard',
      component: () => import('@/views/dashboard/DefaultDashboard.vue')
    },
    {
      name: 'Students',
      path: '/students',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'Employees',
      path: '/employees',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'Users',
      path: '/users',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'Reports',
      path: '/reports',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'AuditLogs',
      path: '/audit-logs',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'Profile',
      path: '/profile',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'Settings',
      path: '/settings',
      component: () => import('@/views/StarterPage.vue')
    }
  ]
};

export default MainRoutes;
