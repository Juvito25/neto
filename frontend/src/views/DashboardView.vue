<template>
  <div class="dashboard">
    <header class="header">
      <h1>Hola, {{ tenant?.business_name || 'Usuario' }}</h1>
      <div class="status-badge" :class="whatsappStatus">
        {{ whatsappStatus === 'connected' ? 'Conectado' : 'Desconectado' }}
      </div>
    </header>

    <div v-if="trialDaysRemaining > 0" class="trial-banner">
      Te quedan {{ trialDaysRemaining }} días de prueba
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
        No hay conversaciones aún
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const tenant = ref(null)
const whatsappStatus = ref('disconnected')
const trialDaysRemaining = ref(0)
const contacts = ref([])
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
.dashboard { padding: 2rem; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; }
.status-badge.connected { background: #dcfce7; color: #008a45; }
.status-badge.disconnected { background: #fee2e2; color: #dc2626; }
.trial-banner { background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
.metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
.metric-card { background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
.metric-value { display: block; font-size: 2rem; font-weight: 600; color: #0052cc; }
.metric-label { color: #64748b; font-size: 0.875rem; }
.contact-item { display: flex; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
.contact-info { display: flex; flex-direction: column; }
.contact-name { font-weight: 500; }
.contact-phone { font-size: 0.875rem; color: #64748b; }
.empty-state { text-align: center; padding: 2rem; color: #64748b; }
</style>
