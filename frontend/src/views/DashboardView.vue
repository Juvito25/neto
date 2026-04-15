<template>
  <div class="dashboard-view">
    <!-- Header -->
    <header class="view-header">
      <div class="header-content">
        <h1 class="view-title">Hola, {{ tenant?.business_name || 'Usuario' }}</h1>
        <div class="header-badges">
          <span class="plan-badge">{{ tenant?.plan?.display_name || 'Plan Free' }}</span>
          <div class="status-indicator">
            <span class="status-dot-sm" :class="tenant?.whatsapp_status"></span>
            <span class="status-label-sm">{{ whatsappStatusLabel }}</span>
          </div>
        </div>
      </div>
      <button @click="router.push('/plans')" class="btn btn-outline btn-sm">Cambiar plan</button>
    </header>

    <!-- Trial Banner -->
    <transition name="fade-slide">
      <div v-if="tenant?.plan?.price_cents > 0 && trialDaysRemaining > 0" class="trial-banner">
        <div class="trial-banner-content">
          <div class="trial-icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
          </div>
          <div class="trial-text">
            <p class="trial-title">Te quedan {{ trialDaysRemaining }} días de prueba gratuita</p>
            <p class="trial-subtitle">Aprovechá al máximo tu bot antes de que termine.</p>
          </div>
        </div>
        <router-link to="/plans" class="trial-button">¡Actualizar ahora!</router-link>
      </div>
    </transition>

    <!-- Onboarding Checklist -->
    <OnboardingChecklist v-if="!tenant?.onboarding_completed" @complete="onOnboardingComplete" />

    <!-- Metrics Grid -->
    <div class="metrics-grid">
      <div v-for="metric in metricsList" :key="metric.label" class="metric-card" :class="metric.class">
        <div class="metric-card-header">
          <span class="metric-label">{{ metric.label }}</span>
          <div class="metric-icon-bg" v-html="metric.icon"></div>
        </div>
        <div class="metric-value-container">
          <span class="metric-prefix" v-if="metric.prefix">{{ metric.prefix }}</span>
          <span class="metric-value">{{ metric.isCurrency ? formatCurrency(metric.displayValue) : metric.displayValue }}</span>
          <span class="metric-suffix" v-if="metric.suffix">{{ metric.suffix }}</span>
        </div>
        <div class="metric-footer" v-if="metric.footer">
          {{ metric.footer }}
        </div>
      </div>
    </div>

    <!-- Main Sections Grid -->
    <div class="dashboard-grid">
      <!-- Recent Conversations -->
      <section class="dashboard-section recent-conversations">
        <div class="section-header">
          <h2 class="section-title">Conversaciones recientes</h2>
          <router-link to="/conversations" class="section-link">Ver todas →</router-link>
        </div>
        
        <div class="conversations-table-wrapper">
          <table v-if="contacts.length > 0" class="conversations-table">
            <thead>
              <tr>
                <th>Contacto</th>
                <th>Último mensaje</th>
                <th>Tiempo</th>
                <th class="text-right">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="contact in contacts" :key="contact.id" @click="goToConversation(contact.id)" class="clickable-row">
                <td>
                  <div class="contact-cell">
                    <div class="contact-avatar" :style="{ backgroundColor: getAvatarColor(contact.phone) }">
                      {{ getInitials(contact.name || contact.phone) }}
                    </div>
                    <div class="contact-name-info">
                      <p class="contact-name">{{ contact.name || 'Desconocido' }}</p>
                      <p class="contact-phone">{{ formatPhone(contact.phone) }}</p>
                    </div>
                  </div>
                </td>
                <td>
                  <p class="last-message-text">{{ contact.last_message || 'Sin mensajes' }}</p>
                </td>
                <td>
                  <span class="time-text">{{ contact.time || 'Hace poco' }}</span>
                </td>
                <td class="text-right">
                  <button class="btn-ghost btn-xs">Ver chat</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else class="empty-state">
            <div class="empty-isotype">
              <div class="isotype-bar bar-1"></div>
              <div class="isotype-bar bar-2"></div>
              <div class="isotype-bar bar-3"></div>
            </div>
            <p class="empty-text">Aún no hay conversaciones.</p>
            <p class="empty-subtext">Conectá WhatsApp para empezar a automatizar tus ventas.</p>
          </div>
        </div>
      </section>

      <!-- Sales Summary Side -->
      <section class="dashboard-section mini-sales-card">
        <div class="section-header">
          <h2 class="section-title">Resumen de Ventas</h2>
          <router-link to="/sales" class="section-link">Ver todas</router-link>
        </div>
        <div class="mini-sales-content">
          <div class="sale-stat">
            <span class="stat-label">Pendientes de confirmación</span>
            <span class="stat-value warning">{{ pendingSalesCount }}</span>
          </div>
          <div class="sale-stat">
            <span class="stat-label">Confirmadas (Total)</span>
            <span class="stat-value success">{{ metricsData.salesCount || 0 }}</span>
          </div>
          
          <div class="amount-summary">
            <div class="revenue-item">
              <span class="rev-label">Por cobrar</span>
              <span class="rev-value">$ {{ formatCurrency(displayMetrics.pendingRevenue) }}</span>
            </div>
            <div class="revenue-item confirmed">
              <span class="rev-label">Confirmado</span>
              <span class="rev-value highlight">$ {{ formatCurrency(displayMetrics.totalRevenue) }}</span>
            </div>
          </div>
          
          <button @click="router.push('/sales')" class="btn btn-primary btn-full">Gestionar Ventas</button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import OnboardingChecklist from '@/components/OnboardingChecklist.vue'

const router = useRouter()
const tenant = ref(null)
const trialDaysRemaining = ref(0)
const contacts = ref([])
const pendingSalesCount = ref(0)

const onOnboardingComplete = () => {
  tenant.value.onboarding_completed = true
}

const metricsData = ref({
  received: 0,
  responded: 0,
  rate: 0,
  totalRevenue: 0,
  pendingRevenue: 0,
  salesCount: 0
})

const displayMetrics = ref({
  received: 0,
  responded: 0,
  rate: 0,
  totalRevenue: 0,
  pendingRevenue: 0
})

const metricsList = computed(() => [
  { 
    label: 'VENTAS CONFIRMADAS', 
    displayValue: displayMetrics.value.totalRevenue, 
    isCurrency: true,
    prefix: '$',
    class: 'card-highlight',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>' 
  },
  { 
    label: 'MENSAJES RECIBIDOS', 
    displayValue: displayMetrics.value.received, 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' 
  },
  { 
    label: 'TASA DE RESPUESTA', 
    displayValue: displayMetrics.value.rate, 
    suffix: '%', 
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>' 
  }
])

const whatsappStatusLabel = computed(() => {
  if (tenant.value?.whatsapp_status === 'connected') return 'WhatsApp conectado'
  return 'WhatsApp desconectado'
})

const formatCurrency = (val) => {
  return val.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

const getAvatarColor = (phone) => {
  if (!phone) return 'var(--color-primary)'
  const colors = ['#0052CC', '#008A45', '#D97706', '#DC2626', '#7C3AED', '#DB2777']
  const hash = phone.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
  return colors[hash % colors.length]
}

const formatPhone = (phone) => {
  if (!phone) return ''
  return phone.replace(/(\+?\d{2})(\d{1})(\d{4})(\d{4})/, '$1 $2 $3-$4')
}

const animateValue = (key, targetValue, duration = 800) => {
  const startValue = displayMetrics.value[key]
  if (startValue === targetValue) return
  
  const startTime = performance.now()

  const update = (now) => {
    const elapsed = now - startTime
    const progress = Math.min(elapsed / duration, 1)
    // Ease out cubic
    const ease = 1 - Math.pow(1 - progress, 3)
    displayMetrics.value[key] = Math.floor(startValue + (targetValue - startValue) * ease)

    if (progress < 1) {
      requestAnimationFrame(update)
    }
  }

  requestAnimationFrame(update)
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/tenant')
    tenant.value = data
    trialDaysRemaining.value = data.days_remaining_in_trial || 0

    const { data: convData } = await axios.get('/dashboard/recent-conversations')
    contacts.value = convData.data || []

    const { data: metricsDataRes } = await axios.get('/dashboard/metrics')
    metricsData.value = metricsDataRes
    
    // Start animations
    setTimeout(() => {
      animateValue('received', metricsData.value.messagesReceived || 0)
      animateValue('responded', metricsData.value.messagesResponded || 0)
      animateValue('rate', metricsData.value.responseRate || 0)
      animateValue('totalRevenue', metricsData.value.totalRevenue || 0)
      animateValue('pendingRevenue', metricsData.value.pendingRevenue || 0)
    }, 100)

    const { data: salesData } = await axios.get('/sales?status=pending')
    pendingSalesCount.value = salesData.total || salesData.data?.length || 0
  } catch (e) {
    console.error('Error fetching dashboard data:', e)
  }
})

const goToConversation = (contactId) => {
  router.push({ path: '/conversations', query: { selected_id: contactId } })
}
</script>

<style scoped>
.dashboard-view {
  padding: 32px;
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.view-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 8px;
  letter-spacing: -0.02em;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 12px;
}

.plan-badge {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: 4px;
  background: var(--color-primary-10);
  color: var(--color-primary);
  letter-spacing: 0.05em;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.status-dot-sm {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-text-muted);
}

.status-dot-sm.connected {
  background: var(--color-success);
  box-shadow: 0 0 0 2px var(--color-success-10);
}

.status-label-sm {
  font-size: 12px;
  color: var(--color-text-muted);
  font-weight: 500;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all var(--transition-fast);
  border: 1px solid transparent;
}

.btn-full { width: 100% }

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.btn-outline {
  background: transparent;
  border-color: var(--color-border);
  color: var(--color-dark);
}

.btn-outline:hover {
  background: var(--color-surface);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover {
  opacity: 0.9;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.metric-card {
  background: white;
  padding: 24px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  transition: all var(--transition-normal);
}

.metric-card.card-highlight {
  background: linear-gradient(to bottom right, white, var(--color-surface));
  border-color: var(--color-primary-10);
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
  border-color: var(--color-primary);
}

.metric-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.metric-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  letter-spacing: 0.08em;
}

.metric-icon-bg {
  width: 32px;
  height: 32px;
  background: var(--color-primary-10);
  color: var(--color-primary);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.metric-icon-bg :deep(svg) {
  width: 18px;
  height: 18px;
}

.metric-value-container {
  display: flex;
  align-items: baseline;
  gap: 2px;
}

.metric-prefix, .metric-suffix {
  font-size: 20px;
  font-weight: 600;
  color: var(--color-primary);
  opacity: 0.7;
}

.metric-value {
  font-size: 40px;
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: -0.02em;
}

/* Dashboard Sections Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 32px;
  align-items: start;
}

@media (max-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

.dashboard-section {
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.section-header {
  padding: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border);
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-dark);
}

.section-link {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-primary);
}

/* Conversations Table */
.conversations-table-wrapper {
  overflow-x: auto;
}

.conversations-table {
  width: 100%;
  border-collapse: collapse;
}

.conversations-table th {
  text-align: left;
  padding: 12px 24px;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-surface);
}

.conversations-table td {
  padding: 16px 24px;
  border-bottom: 1px solid var(--color-border);
}

.clickable-row {
  cursor: pointer;
}

.clickable-row:hover {
  background: var(--color-surface);
}

.contact-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.contact-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.contact-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 2px;
}

.contact-phone {
  font-size: 12px;
  color: var(--color-text-muted);
}

.last-message-text {
  font-size: 13px;
  color: var(--color-text-muted);
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.time-text {
  font-size: 12px;
  color: var(--color-text-muted);
}

.text-right {
  text-align: right;
}

.btn-ghost {
  background: transparent;
  border: none;
  color: var(--color-primary);
  font-weight: 600;
}

/* Mini Sales Card */
.mini-sales-content {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sale-stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  font-size: 13px;
  color: var(--color-text-muted);
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
}

.stat-value.warning { color: #D97706; }
.stat-value.success { color: var(--color-success); }

.amount-summary {
  background: var(--color-surface);
  padding: 20px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.revenue-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.rev-label {
  font-size: 12px;
  color: var(--color-text-muted);
}

.rev-value {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-dark);
}

.rev-value.highlight {
  font-size: 18px;
  color: var(--color-primary);
  font-weight: 700;
}

/* Empty State */
.empty-state {
  padding: 64px 32px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-isotype {
  width: 64px;
  height: 48px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 24px;
}

.isotype-bar {
  height: 10px;
  background: var(--color-primary-10);
  border-radius: 2px 8px 8px 2px;
}

.bar-1 { width: 100%; animation: staggerBar 1.5s infinite 0s; }
.bar-2 { width: 80%; animation: staggerBar 1.5s infinite 0.2s; }
.bar-3 { width: 60%; animation: staggerBar 1.5s infinite 0.4s; }

@keyframes staggerBar {
  0% { transform: scaleX(1); opacity: 0.5; }
  50% { transform: scaleX(1.1); opacity: 1; }
  100% { transform: scaleX(1); opacity: 0.5; }
}

.empty-text {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 8px;
}

.empty-subtext {
  font-size: 14px;
  color: var(--color-text-muted);
}
</style>
