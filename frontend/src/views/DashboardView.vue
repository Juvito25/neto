<template>
  <div class="dashboard">
    <header class="header">
      <div>
        <h1>Hola, {{ tenant?.business_name || 'Usuario' }}</h1>
        <p class="header-subtitle">{{ tenant?.plan?.display_name }} · {{ tenant?.whatsapp_status === 'connected' ? 'WhatsApp conectado' : 'WhatsApp desconectado' }}</p>
      </div>
      <router-link to="/plans" class="upgrade-btn">Cambiar plan</router-link>
    </header>

    <div v-if="tenant?.plan?.price_cents > 0 && trialDaysRemaining > 0" class="trial-banner">
      <div class="trial-info">
        <span class="trial-icon">🎁</span>
        <span>Te quedan <strong>{{ trialDaysRemaining }} días</strong> de prueba gratuita</span>
      </div>
      <router-link to="/plans" class="trial-cta">¡Actualizar ahora!</router-link>
    </div>

    <div v-if="!tenant?.whatsapp_status || tenant?.whatsapp_status === 'disconnected'" class="whatsapp-prompt">
      <div class="prompt-content">
        <div class="prompt-icon">📱</div>
        <div class="prompt-text">
          <h3>Conectá WhatsApp</h3>
          <p>Vinculá tu número para empezar a recibir mensajes</p>
        </div>
        <router-link to="/settings" class="prompt-btn">Configurar WhatsApp</router-link>
      </div>
    </div>

    <div class="metrics">
      <div class="metric-card">
        <span class="metric-value">{{ metrics.messagesReceived }}</span>
        <span class="metric-label">Mensajes recibidos</span>
      </div>
      <div class="metric-card">
        <span class="metric-value">{{ metrics.messagesResponded }}</span>
        <span class="metric-label">Mensajes respondidos</span>
      </div>
      <div class="metric-card">
        <span class="metric-value">{{ metrics.responseRate }}%</span>
        <span class="metric-label">Tasa de respuesta</span>
      </div>
    </div>

    <div class="conversations-list">
      <h2>Conversaciones recientes</h2>
      <div v-for="contact in contacts" :key="contact.id" class="contact-item">
        <div class="contact-info">
          <span class="contact-name">{{ contact.name || contact.phone }}</span>
          <span class="contact-phone">{{ contact.phone }}</span>
        </div>
        <span class="last-message">{{ contact.last_message }}</span>
        <span class="message-time">{{ contact.time }}</span>
      </div>
      <div v-if="contacts.length === 0" class="empty-state">
        <div class="empty-icon">💬</div>
        <p>No hay conversaciones aún</p>
        <p class="empty-hint">Conectá WhatsApp para empezar a recibir mensajes</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const tenant = ref(null)
const whatsappStatus = ref('disconnected')
const trialDaysRemaining = ref(0)
const contacts = ref([])
const showPlansModal = ref(false)
const metrics = ref({
  messagesReceived: 0,
  messagesResponded: 0,
  responseRate: 0,
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/tenant')
    tenant.value = data
    whatsappStatus.value = data.whatsapp_status
    trialDaysRemaining.value = data.days_remaining_in_trial || 0
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped>
.dashboard {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.header h1 {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 0.25rem;
}

.header-subtitle {
  color: var(--color-text-muted);
  font-size: 0.9375rem;
}

.upgrade-btn {
  background: white;
  border: 1px solid var(--color-border);
  color: var(--color-dark);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.upgrade-btn:hover {
  background: var(--color-surface);
  border-color: var(--color-primary);
}

.trial-banner {
  background: linear-gradient(135deg, #0052CC 0%, #0044b3 100%);
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.trial-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.trial-icon {
  font-size: 1.25rem;
}

.trial-cta {
  background: white;
  color: var(--color-primary);
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.trial-cta:hover {
  transform: scale(1.02);
}

.whatsapp-prompt {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.prompt-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
}

.prompt-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.prompt-text {
  flex-grow: 1;
}

.prompt-text h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 0.25rem;
}

.prompt-text p {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.prompt-btn {
  background: var(--color-primary);
  color: white;
  padding: 0.625rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
  text-decoration: none;
  transition: background 0.2s;
}

.prompt-btn:hover {
  background: #0044b3;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.metric-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  text-align: center;
}

.metric-value {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 0.25rem;
}

.metric-label {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}

.conversations-list h2 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 1rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  margin-bottom: 0.5rem;
}

.contact-info {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.contact-name {
  font-weight: 500;
  color: var(--color-dark);
}

.contact-phone {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.last-message {
  flex-grow: 1;
  color: var(--color-text-muted);
  font-size: 0.9375rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.message-time {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 12px;
  border: 1px solid var(--color-border);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-state p {
  color: var(--color-text-muted);
  font-size: 1rem;
}

.empty-hint {
  font-size: 0.875rem !important;
  margin-top: 0.5rem;
}
</style>
