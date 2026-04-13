<template>
  <div class="app-wrapper">
    <div v-if="trialExpired" class="global-banner error-banner">
      Tu prueba gratuita venció. El bot está pausado. 
      <router-link to="/plans" class="banner-link">Suscribite para reactivarlo.</router-link>
    </div>
    <div class="app-layout" :class="{ 'has-banner': trialExpired }">
      <aside class="sidebar">
        <div class="logo">NETO</div>
        <nav>
          <router-link to="/">Dashboard</router-link>
          <router-link to="/conversations">Conversaciones</router-link>
          <router-link to="/sales" class="sales-link">
            Ventas <span v-if="pendingSales > 0" class="badge">{{ pendingSales }}</span>
          </router-link>
          <router-link to="/catalog">Catálogo</router-link>
          <router-link to="/settings">Configuración</router-link>
        </nav>
      </aside>
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const trialExpired = ref(false)
const pendingSales = ref(0)

onMounted(async () => {
  try {
    const { data: tenant } = await axios.get('/tenant')
    // Convert to Date object to check if past
    const trialEnd = tenant.trial_ends_at ? new Date(tenant.trial_ends_at) : null
    const isActive = tenant.subscription_status === 'active' || tenant.plan_id === 2 // Pro/Business etc...
    
    // We can rely on a simpler check: if trial end is past and status is not active
    if (trialEnd && trialEnd < new Date() && tenant.subscription_status !== 'active') {
      trialExpired.value = true
    }

    const { data: salesData } = await axios.get('/sales?status=pending')
    pendingSales.value = salesData.data?.length || 0
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped>
.app-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
.global-banner { padding: 0.75rem; text-align: center; font-weight: 500; font-size: 0.875rem; }
.error-banner { background: #fee2e2; color: #dc2626; border-bottom: 1px solid #f87171; }
.banner-link { color: #b91c1c; text-decoration: underline; font-weight: 600; margin-left: 0.5rem; }
.app-layout { display: flex; flex: 1; overflow: hidden; }
.sidebar { width: 240px; background: #0f172a; color: white; padding: 1.5rem; display: flex; flex-direction: column; overflow-y: auto; }
.logo { font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; color: #0052cc; }
.sidebar nav { display: flex; flex-direction: column; gap: 0.5rem; }
.sidebar a { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-radius: 4px; color: #94a3b8; text-decoration: none; }
.sidebar a:hover, .sidebar a.router-link-active { background: #1e293b; color: white; }
.badge { background: #ef4444; color: white; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.main-content { flex: 1; background: #f8fafc; overflow-y: auto; position: relative; }
</style>
