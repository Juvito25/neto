<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import logoBlue from '@/assets/branding/media__1776087843743.png'

const router = useRouter()
const plans = ref([])
const loading = ref(true)
const selecting = ref(false)
const selectedPlan = ref(null)

/** Cotización dólar blue (https://dolarapi.com/v1/dolares/blue) — `venta` para pasar USD → ARS aproximado */
const dolarBlue = ref(null)
const cotizacionLista = ref(false)

const DOLAR_BLUE_URL = 'https://dolarapi.com/v1/dolares/blue'

async function fetchDolarBlue() {
  try {
    const res = await fetch(DOLAR_BLUE_URL)
    if (!res.ok) return
    const data = await res.json()
    if (data && typeof data.venta === 'number' && data.venta > 0) {
      dolarBlue.value = data
    }
  } catch (e) {
    console.warn('No se pudo obtener cotización dólar blue:', e)
  } finally {
    cotizacionLista.value = true
  }
}

onMounted(async () => {
  const dolarPromise = fetchDolarBlue()
  try {
    const { data } = await axios.get('/plans')
    let allPlans = data.data || []
    if (allPlans.length > 1) {
      const proPlan = allPlans.find(p => p.name === 'pro')
      plans.value = proPlan ? [proPlan] : [allPlans[0]]
    } else {
      plans.value = allPlans
    }
  } catch (e) {
    console.error('Error loading plans:', e)
  } finally {
    loading.value = false
  }
  await dolarPromise
})

function parseFeatures(features) {
  if (Array.isArray(features)) return features
  try {
    return JSON.parse(features)
  } catch {
    return []
  }
}

async function selectPlan(plan) {
  selecting.value = true
  selectedPlan.value = plan.id
  
  try {
    const token = localStorage.getItem('token')
    await axios.put('/tenant', { plan_id: plan.id }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    if (plan.price_cents > 0) {
      const { data } = await axios.post('/billing/subscription', {}, {
        headers: { Authorization: `Bearer ${token}` }
      })
      if (data.already_active) {
        router.push('/settings?payment=success')
        return
      }
      if (data.init_point) {
        window.location.href = data.init_point
        return
      }
    }

    router.push('/onboarding')
  } catch (e) {
    console.error('Error selecting plan:', e)
    const errorData = e.response?.data
    const message = errorData?.message || 'Error al procesar el plan'
    const details = errorData?.details ? JSON.stringify(errorData.details, null, 2) : ''
    
    alert(message + (details ? '\n\nDetalles:\n' + details : ''))
  } finally {
    selecting.value = false
  }
}

/** Precio en USD desde cents (planes vienen en USD). */
function formatUsdFromCents(cents) {
  return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

/** ARS/mes aproximado: USD del plan × dólar blue venta. */
function arsPerMonthFromPlan(plan) {
  if (!plan?.price_cents || plan.price_cents <= 0) return null
  const cur = (plan.currency || 'USD').toString().toUpperCase()
  if (cur !== 'USD') return null
  const venta = dolarBlue.value?.venta
  if (typeof venta !== 'number' || venta <= 0) return null
  return Math.round((plan.price_cents / 100) * venta)
}

function formatArs(amount) {
  return amount.toLocaleString('es-AR', { maximumFractionDigits: 0 })
}

function formatDolarUpdatedAt(iso) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleString('es-AR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

function isPopular(index) {
  return index === 1 || (plans.value.length === 1 && index === 0)
}
</script>

<template>
  <div class="plans-page">
    <!-- Abstract Background Elements -->
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <div class="plans-container">
      <header class="plans-header">
        <div class="logo-mini">
          <img :src="logoBlue" alt="NETO" class="brand-logo-mini">
        </div>
        <h1 class="title">Tu negocio, ahora en piloto automático</h1>
        <p class="subtitle">Un único plan con todo lo que necesitas para escalar tus ventas.</p>
      </header>
      
      <div v-if="loading" class="plans-loading">
        <div class="shimmer-plans">
          <div v-for="n in 3" :key="n" class="shimmer-card"></div>
        </div>
      </div>
      
      <div v-else class="plans-grid">
        <div 
          v-for="(plan, index) in plans" 
          :key="plan.id" 
          class="plan-card"
          :class="{ 
            featured: isPopular(index), 
            selected: selectedPlan === plan.id 
          }"
        >
          <div class="popular-tag" v-if="isPopular(index)">RECOMENDADO</div>
          
          <div class="card-top">
            <h2 class="plan-name">{{ plan.display_name }}</h2>
            <div class="plan-price">
              <template v-if="!plan.price_cents">
                <span class="amount amount-free">Gratis</span>
              </template>
              <template v-else>
                <span class="currency">USD</span>
                <span class="amount">{{ formatUsdFromCents(plan.price_cents) }}</span>
                <span class="period">/mes</span>
              </template>
            </div>
          </div>
          
          <div class="divider"></div>
          
          <div class="card-features">
            <div class="feature-item">
              <span class="icon">📦</span>
              <div class="text">
                <strong>{{ plan.catalog_items_limit }}</strong> productos
              </div>
            </div>
            <div class="feature-item">
              <span class="icon">💬</span>
              <div class="text">
                <strong>{{ plan.messages_limit }}</strong> mensajes/mes
              </div>
            </div>
            
            <div 
              v-for="(feature, fIndex) in parseFeatures(plan.features)" 
              :key="fIndex" 
              class="feature-item custom"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="text">{{ feature }}</span>
            </div>
          </div>
          
          <div class="card-footer">
            <button 
              @click="selectPlan(plan)" 
              class="btn-select"
              :class="{ 'btn-primary': isPopular(index), 'btn-outline': !isPopular(index) }"
              :disabled="selecting"
            >
              {{ selecting && selectedPlan === plan.id ? 'Procesando...' : 'Comenzar ahora' }}
            </button>
            <p class="trial-note">Cancela cuando quieras</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plans-page {
  min-height: 100vh;
  background: var(--color-surface);
  position: relative;
  overflow: hidden;
  padding: 80px 24px;
}

/* Background Blurs */
.bg-blur {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  filter: blur(120px);
  z-index: 0;
  opacity: 0.15;
}
.blur-1 { top: -200px; right: -100px; background: var(--color-primary); }
.blur-2 { bottom: -200px; left: -100px; background: var(--color-success); }

.plans-container {
  max-width: 1040px;
  margin: 0 auto;
  position: relative;
  z-index: 10;
}

.plans-header {
  text-align: center;
  margin-bottom: 64px;
}

.logo-mini {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.brand-logo-mini {
  height: 48px;
  object-fit: contain;
}

.isotype-vertical .bar {
  height: 6px;
  background: var(--color-primary);
  border-radius: 1px 4px 4px 1px;
}
.bar-1 { width: 100%; opacity: 0.4; }
.bar-2 { width: 80%; opacity: 0.7; }
.bar-3 { width: 60%; }

.title {
  font-size: 3rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--color-dark);
  margin-bottom: 12px;
}

.subtitle {
  font-size: 1.25rem;
  color: var(--color-text-muted);
  max-width: 500px;
  margin: 0 auto;
}

/* Plans Grid */
.plans-grid {
  display: flex;
  justify-content: center;
  gap: 24px;
  align-items: center;
}

.plan-card {
  max-width: 500px;
  width: 100%;
}

@media (max-width: 900px) {
  .plans-grid { flex-direction: column; }
  .title { font-size: 2.5rem; }
}

.plan-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
  border: 1px solid var(--color-border);
  border-radius: 24px;
  padding: 40px;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.plan-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
  border-color: var(--color-primary-10);
}

.plan-card.featured {
  background: white;
  border-color: var(--color-primary);
  border-width: 2px;
  box-shadow: 0 12px 32px rgba(0, 82, 204, 0.08);
  padding: 48px 40px;
}

.popular-tag {
  position: absolute;
  top: 16px;
  right: 16px;
  background: var(--color-primary);
  color: white;
  padding: 6px 12px;
  border-radius: 99px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.plan-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 24px;
}

.plan-price {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  margin-bottom: 32px;
}

.plan-price .currency {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-dark);
}

.plan-price .amount {
  font-size: 3.5rem;
  font-weight: 800;
  color: var(--color-dark);
  letter-spacing: -0.02em;
}

.plan-price .period {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin-left: 4px;
}

.amount-free {
  font-size: 3.5rem;
  font-weight: 800;
  color: var(--color-success);
  letter-spacing: -0.02em;
}

.price-hint {
  width: 100%;
  margin-top: 12px;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: var(--color-text-muted);
  font-weight: 500;
}

.divider {
  height: 1px;
  background: var(--color-border);
  margin-bottom: 32px;
}

.card-features {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 40px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.feature-item .icon { font-size: 1.25rem; }
.feature-item .text { font-size: 0.95rem; color: var(--color-dark); }
.feature-item.custom .text { color: var(--color-text-muted); }

.check {
  color: var(--color-success);
  flex-shrink: 0;
}

/* Footer & Buttons */
.card-footer { text-align: center; }

.btn-select {
  width: 100%;
  padding: 16px;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.btn-primary { background: var(--color-primary); color: white; border-color: var(--color-primary); }
.btn-primary:hover { opacity: 0.9; transform: scale(1.02); }

.btn-outline { background: white; border-color: var(--color-border); color: var(--color-dark); }
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

.trial-note {
  margin-top: 16px;
  font-size: 12px;
  color: var(--color-text-muted);
}

/* Shimmer Loading */
.plans-loading { padding: 40px 0; }
.shimmer-plans { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.shimmer-card { height: 500px; background: white; border-radius: 24px; animation: pulse 1.5s infinite; }

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 0.4; }
  100% { opacity: 0.6; }
}

/* Fade In Animation */
.plans-grid {
  animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(40px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
