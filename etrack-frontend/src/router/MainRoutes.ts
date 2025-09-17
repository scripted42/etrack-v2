const MainRoutes = {
  path: '/dashboard',
  meta: {
    requiresAuth: true
  },
  component: () => import('@/layouts/dashboard/DashboardLayout.vue'),
  children: [
    {
      name: 'Dashboard',
      path: '',
      component: () => import('@/views/DashboardImproved.vue')
    },
    {
      name: 'Students',
      path: '/students',
      component: () => import('@/views/students/StudentList.vue')
    },
    {
      name: 'Employees',
      path: '/employees',
      component: () => import('@/views/employees/EmployeeList.vue')
    },
    {
      name: 'Users',
      path: '/users',
      component: () => import('@/views/StarterPage.vue')
    },
    {
      name: 'AuditLogs',
      path: '/audit-logs',
      component: () => import('@/views/AuditTrailSimple.vue')
    },
    {
      name: 'BackupDashboard',
      path: '/backup',
      component: () => import('@/views/BackupDashboard.vue')
    },
    {
      name: 'Reports',
      path: '/reports',
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
