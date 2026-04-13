<template>
  <div class="app-wrapper">
    <!-- Trial Expired Banner -->
    <transition name="slide-down">
      <div v-if="trialExpired" class="global-banner danger-banner">
        <div class="banner-content">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          <span>Tu prueba gratuita venció. El bot está pausado.</span>
          <router-link to="/plans" class="banner-cta">Actualizar Plan</router-link>
        </div>
      </div>
    </transition>

    <div class="app-layout">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="logo-zone">
          <router-link to="/" class="logo" @click="isMobileMenuOpen = false">
            <img 
              :src="botActive ? logoGreen : logoBlue" 
              alt="NETO" 
              class="logo-img"
            >
          </router-link>
        </div>

        <div class="status-pill-container">
          <div class="status-pill" :class="whatsappStatus">
            <span class="status-dot"></span>
            <span class="status-text">{{ statusLabel }}</span>
          </div>
        </div>

        <nav class="sidebar-nav">
          <router-link v-for="item in navItems" :key="item.path" :to="item.path" class="nav-item">
            <component :is="item.icon" class="nav-icon" />
            <span class="nav-label">{{ item.label }}</span>
            <span v-if="item.badge && item.badge() > 0" class="nav-badge">{{ item.badge() }}</span>
          </router-link>
        </nav>

        <div class="sidebar-footer">
          <div class="user-info">
            <p class="business-name">{{ tenant?.name || 'Mi Negocio' }}</p>
            <p class="user-email">{{ userEmail }}</p>
          </div>
          <button @click="logout" class="logout-btn" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          </button>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useSalesStore } from '@/stores/sales'
import logoBlue from '@/assets/branding/media__1776087843743.png'
import logoGreen from '@/assets/branding/media__1776087843817.png'

const router = useRouter()
const salesStore = useSalesStore()
const tenant = ref(null)
const userEmail = ref('')
const trialExpired = ref(false)
const whatsappStatus = ref('disconnected') // 'connected', 'disconnected', 'connecting'
const logoFailed = ref(false)
const botActive = computed(() => whatsappStatus.value === 'connected')

const onLogoError = () => {
  logoFailed.value = true
}

const statusLabel = computed(() => {
  switch (whatsappStatus.value) {
    case 'connected': return 'Bot activo'
    case 'connecting': return 'Conectando...'
    default: return 'Bot inactivo'
  }
})

// Icons as functions that return VNodes (to avoid importing many components)
const IconDashboard = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('rect', { x: '3', y: '3', width: '7', height: '7' }), h('rect', { x: '14', y: '3', width: '7', height: '7' }), h('rect', { x: '14', y: '14', width: '7', height: '7' }), h('rect', { x: '3', y: '14', width: '7', height: '7' })])
const IconChat = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' })])
const IconSales = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('circle', { cx: '9', cy: '21', r: '1' }), h('circle', { cx: '20', cy: '21', r: '1' }), h('path', { d: 'M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6' })])
const IconCatalog = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('path', { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' }), h('path', { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' })])
const IconSettings = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('circle', { cx: '12', cy: '12', r: '3' }), h('path', { d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z' })])

const navItems = [
  { path: '/', label: 'Dashboard', icon: IconDashboard },
  { path: '/conversations', label: 'Conversaciones', icon: IconChat },
  { path: '/sales', label: 'Ventas', icon: IconSales, badge: () => salesStore.pendingCount },
  { path: '/catalog', label: 'Catálogo', icon: IconCatalog },
  { path: '/settings', label: 'Configuración', icon: IconSettings },
]

let statusInterval = null

const fetchStatus = async () => {
  try {
    const { data: tenantData } = await axios.get('/tenant')
    tenant.value = tenantData
    whatsappStatus.value = tenantData.whatsapp_status || 'disconnected'
    
    // Check trial
    const trialEnd = tenantData.trial_ends_at ? new Date(tenantData.trial_ends_at) : null
    if (trialEnd && trialEnd < new Date() && tenantData.subscription_status !== 'active') {
      trialExpired.value = true
    } else {
      trialExpired.value = false
    }
  } catch (e) {
    console.error('Error fetching status:', e)
  }
}

const fetchPendingSales = async () => {
  await salesStore.fetchPendingCount()
}

const logout = () => {
  localStorage.removeItem('token')
  router.push('/auth')
}

onMounted(() => {
  fetchStatus()
  fetchPendingSales()
  const userData = localStorage.getItem('user')
  if (userData) {
    userEmail.value = JSON.parse(userData).email
  } else {
    userEmail.value = 'usuario@neto.com'
  }
  statusInterval = setInterval(fetchStatus, 30000) // Poll every 30s
})

onUnmounted(() => {
  if (statusInterval) clearInterval(statusInterval)
})
</script>

<style scoped>
.app-wrapper {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

/* Banner */
.global-banner {
  height: 40px;
  background: var(--color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.danger-banner {
  background: var(--color-danger);
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
  font-weight: 500;
}

.banner-cta {
  background: white;
  color: var(--color-danger);
  padding: 2px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

/* Layout */
.app-layout {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  width: 240px;
  background: var(--color-dark);
  color: white;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

.logo-zone {
  height: 64px;
  padding: 0 20px;
  display: flex;
  align-items: center;
}

.logo-container {
  display: flex;
  align-items: center;
}

.logo-img {
  height: 24px;
  object-fit: contain;
}

.logo-fallback {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: white;
}

.is-breathing {
  animation: breathing 3s ease-in-out infinite;
}

/* Status Pill */
.status-pill-container {
  padding: 0 20px;
  margin-bottom: 24px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.05);
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-muted);
}

.status-pill.connected {
  background: rgba(0, 138, 69, 0.15);
  color: #10b981;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.connected .status-dot {
  background: var(--color-success);
  animation: pulse-success 2s infinite;
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  padding: 0 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  height: 40px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: var(--radius-md);
  color: #94a3b8;
  font-size: 14px;
  font-weight: 500;
  transition: all 120ms ease;
  position: relative;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #cbd5e1;
}

.nav-item.router-link-active {
  background: rgba(0, 82, 204, 0.15);
  color: white;
}

.nav-item.router-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  bottom: 8px;
  width: 3px;
  background: var(--color-primary);
  border-radius: 0 4px 4px 0;
}

.nav-icon {
  width: 20px;
  height: 20px;
  stroke-width: 1.5;
}

.router-link-active .nav-icon {
  color: var(--color-primary);
}

.nav-badge {
  margin-left: auto;
  background: var(--color-danger);
  color: white;
  font-size: 10px;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  padding: 0 5px;
}

/* Sidebar Footer */
.sidebar-footer {
  margin-top: auto;
  padding: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.business-name {
  font-size: 13px;
  font-weight: 600;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 11px;
  color: var(--color-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.logout-btn {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 120ms ease;
}

.logout-btn:hover {
  color: var(--color-danger);
  background: rgba(220, 38, 38, 0.1);
}

/* Main Content */
.main-content {
  flex: 1;
  background: var(--color-surface);
  overflow-y: auto;
  position: relative;
}

/* Transitions */
.slide-down-enter-active, .slide-down-leave-active {
  transition: transform 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  transform: translateY(-100%);
}

.page-enter-active, .page-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}
.page-enter-from { opacity: 0; transform: translateY(8px); }
.page-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
