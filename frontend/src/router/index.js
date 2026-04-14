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
    path: '/plans',
    name: 'plans',
    component: () => import('../views/PlansView.vue'),
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
        path: 'sales',
        name: 'sales',
        component: () => import('../views/SalesView.vue'),
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
  
  // Rutas públicas
  const publicRoutes = ['/login', '/register', '/onboarding', '/plans']
  const isPublicRoute = publicRoutes.includes(to.path)
  
  // Si requiere auth pero no hay token -> login
  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }
  
  // Si hay token y va a login/register -> dashboard
  if ((to.path === '/login' || to.path === '/register') && token) {
    next('/')
    return
  }
  
  // Si hay token, verificar onboarding y plan (solo para rutas principales)
  if (token && (to.path === '/' || to.path === '')) {
    try {
      const axios = (await import('axios')).default
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      const { data } = await axios.get('/tenant')
      
      // Si no tiene plan -> ir a seleccionar plan
      if (!data.plan_id) {
        next('/plans')
        return
      }
      
      // Si tiene plan pero no completó onboarding -> ir a onboarding
      if (!data.onboarding_completed) {
        next('/onboarding')
        return
      }

    } catch (error) {
      console.error('Error checking tenant:', error)
      localStorage.removeItem('token')
      next('/login')
      return
    }
  }
  
  next()
})

export default router
