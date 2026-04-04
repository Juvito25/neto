<template>
  <div class="settings-view">
    <h1>Configuración</h1>

    <div class="tabs">
      <button 
        v-for="tab in ['negocio', 'whatsapp', 'plan']" 
        :key="tab"
        :class="{ active: activeTab === tab }"
        @click="activeTab = tab"
      >
        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
      </button>
    </div>

    <div v-if="activeTab === 'negocio'" class="tab-content">
      <h2>Información del negocio</h2>
      <form @submit.prevent="saveTenant">
        <div class="form-group">
          <label>Nombre del negocio</label>
          <input v-model="tenantForm.business_name" />
        </div>
        <div class="form-group">
          <label>Descripción</label>
          <textarea v-model="tenantForm.description"></textarea>
        </div>
        <div class="form-group">
          <label>Instrucciones especiales para el bot</label>
          <textarea v-model="tenantForm.custom_prompt" placeholder="Agregá instrucciones personalizadas..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </form>
    </div>

    <div v-if="activeTab === 'whatsapp'" class="tab-content">
      <h2>WhatsApp</h2>
      <div class="whatsapp-status">
        <span class="status-badge" :class="whatsappStatus">
          {{ whatsappStatus === 'connected' ? 'Conectado' : whatsappStatus === 'connecting' ? 'Conectando...' : 'Desconectado' }}
        </span>
      </div>
      
      <div v-if="whatsappStatus !== 'connected'" class="qr-section">
        <p>Escaneá el código QR con tu WhatsApp para conectar</p>
        <div v-if="qrCode" class="qr-code">
          <img :src="qrCode" alt="QR Code" />
        </div>
        <button @click="connectWhatsApp" class="btn btn-primary">
          {{ whatsappStatus === 'connecting' ? 'Actualizar QR' : 'Conectar WhatsApp' }}
        </button>
      </div>

      <div v-else>
        <button @click="disconnectWhatsApp" class="btn btn-danger">Desconectar</button>
      </div>
    </div>

    <div v-if="activeTab === 'plan'" class="tab-content">
      <h2>Tu plan</h2>
      <div class="plan-info">
        <p><strong>Plan actual:</strong> {{ tenant?.plan?.toUpperCase() }}</p>
        <p><strong>Mensajes usados:</strong> {{ tenant?.messages_used }} / {{ tenant?.messages_limit }}</p>
        <div class="progress-bar">
          <div class="progress" :style="{ width: progressPercent + '%' }"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('negocio')
const tenant = ref(null)
const whatsappStatus = ref('disconnected')
const qrCode = ref(null)

const tenantForm = ref({
  business_name: '',
  description: '',
  custom_prompt: '',
})

const progressPercent = computed(() => {
  if (!tenant.value) return 0
  return Math.min(100, (tenant.value.messages_used / tenant.value.messages_limit) * 100)
})

const loadData = async () => {
  try {
    const { data } = await axios.get('/api/tenant')
    tenant.value = data
    tenantForm.value = {
      business_name: data.business_name || '',
      description: data.description || '',
      custom_prompt: data.custom_prompt || '',
    }
  } catch (e) {
    console.error(e)
  }
}

const loadWhatsAppStatus = async () => {
  try {
    const { data } = await axios.get('/api/whatsapp/status')
    whatsappStatus.value = data.status
  } catch (e) {
    console.error(e)
  }
}

const saveTenant = async () => {
  try {
    await axios.put('/api/tenant', tenantForm.value)
    alert('Guardado!')
  } catch (e) {
    console.error(e)
  }
}

const connectWhatsApp = async () => {
  try {
    whatsappStatus.value = 'connecting'
    await axios.post('/api/whatsapp/connect')
    const { data } = await axios.get('/api/whatsapp/qr')
    qrCode.value = data.qr_code
    setTimeout(loadWhatsAppStatus, 3000)
  } catch (e) {
    console.error(e)
  }
}

const disconnectWhatsApp = async () => {
  if (!confirm('¿Desconectar WhatsApp?')) return
  try {
    await axios.post('/api/whatsapp/disconnect')
    whatsappStatus.value = 'disconnected'
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadData()
  loadWhatsAppStatus()
})
</script>

<style scoped>
.settings-view { padding: 2rem; max-width: 800px; margin: 0 auto; }
.tabs { display: flex; gap: 1rem; margin: 2rem 0; border-bottom: 1px solid #e2e8f0; }
.tabs button { padding: 0.75rem 1.5rem; background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; }
.tabs button.active { border-color: #0052cc; color: #0052cc; }
.tab-content { padding: 1rem 0; }
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
.form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 4px; }
.form-group textarea { min-height: 100px; }
.btn { padding: 0.75rem 1.5rem; border-radius: 4px; border: none; cursor: pointer; }
.btn-primary { background: #0052cc; color: white; }
.btn-danger { background: #dc2626; color: white; }
.status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; }
.status-badge.connected { background: #dcfce7; color: #008a45; }
.status-badge.connecting { background: #fef3c7; color: #92400e; }
.status-badge.disconnected { background: #fee2e2; color: #dc2626; }
.qr-section { text-align: center; padding: 2rem; }
.qr-code img { max-width: 300px; margin: 1rem 0; }
.progress-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 0.5rem; }
.progress { height: 100%; background: #0052cc; transition: width 0.3s; }
</style>
