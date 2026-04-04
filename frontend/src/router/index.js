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
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
