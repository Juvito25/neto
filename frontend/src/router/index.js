import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/AuthView.vue'),
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/AuthView.vue'),
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: () => import('../views/OnboardingView.vue'),
  },
  {
    path: '/',
    component: () => import('../components/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('../views/DashboardView.vue'),
      },
      {
        path: 'conversations',
        name: 'conversations',
        component: () => import('../views/ConversationsView.vue'),
      },
      {
        path: 'inventory',
        name: 'inventory',
        component: () => import('../views/InventoryView.vue'),
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('../views/SettingsView.vue'),
      },
      {
        path: 'catalog',
        name: 'catalog',
        component: () => import('../views/CatalogView.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }

  if ((to.path === '/login' || to.path === '/register') && token) {
    next('/')
    return
  }

  // Verificar onboarding si la ruta requiere auth
  if (to.meta.requiresAuth && to.path !== '/onboarding') {
    try {
      const axios = (await import('axios')).default
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      const { data } = await axios.get('/tenant/onboarding')

      if (data.data && !data.data.completed && !data.data.onboarding_completed) {
        next('/onboarding')
        return
      }
    } catch (error) {
      console.error('Error checking onboarding:', error)
    }
  }

  next()
})

export default router
