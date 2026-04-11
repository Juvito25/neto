<template>
  <div class="settings-view">
    <h1>⚙️ CONFIGURACIÓN - NETO</h1>

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
        <span style="margin-left: 10px; font-size: 12px; color: #666;">(Debug: qrCode={{ qrCode ? 'SET' : 'null' }})</span>
      </div>
      
      <div v-if="whatsappStatus !== 'connected'" class="qr-section">
        <p>Escaneá el código QR con tu WhatsApp para conectar</p>
        <div v-if="loadingWhatsApp" class="loading">Cargando QR...</div>
        <div v-else-if="qrCode" class="qr-code">
          <img :src="qrCode" alt="QR Code" />
        </div>
        <div v-else style="padding: 20px; color: #999;">
          {{ loadingWhatsApp ? 'Esperando...' : 'Hacé click en el botón para generar el QR' }}
        </div>
        <button @click="connectWhatsApp" class="btn btn-primary" style="background: red !important;">
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
        <p><strong>Plan actual:</strong> {{ tenant?.plan?.name?.toUpperCase() || 'Free' }}</p>
        <p><strong>Mensajes usados:</strong> {{ tenant?.messages_used }} / {{ tenant?.messages_limit }}</p>
        <div class="progress-bar">
          <div class="progress" :style="{ width: progressPercent + '%' }"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import QRCode from 'qrcode'

const activeTab = ref('negocio')
const tenant = ref(null)
const whatsappStatus = ref('disconnected')
const qrCode = ref(null)
const loadingWhatsApp = ref(false)
let pollingInterval = null

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
    const { data } = await axios.get('/tenant')
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
    console.log('Loading WhatsApp status...')
    const { data } = await axios.get('/whatsapp/status')
    console.log('Status response:', data)
    whatsappStatus.value = data.status
    console.log('whatsappStatus set to:', data.status)

    // Si está conectando pero no tenemos el QR aún, intentar obtenerlo
    if (whatsappStatus.value === 'connecting' && !qrCode.value) {
      fetchQRCode()
    }
    
    // Si ya se conectó, limpiar el QR
    if (whatsappStatus.value === 'connected') {
      qrCode.value = null
    }
  } catch (e) {
    console.error('Error loading WhatsApp status:', e)
  }
}

const fetchQRCode = async () => {
  try {
    console.log('Fetching QR code...')
    const { data } = await axios.get('/whatsapp/qr')
    console.log('QR response:', data)
    
    if (data.qr_code) {
      console.log('QR code received, length:', data.qr_code.length)
      if (data.qr_code.startsWith('data:')) {
        qrCode.value = data.qr_code
      } else {
        qrCode.value = await QRCode.toDataURL(data.qr_code, {
          width: 280,
          margin: 2,
          color: { dark: '#000000', light: '#FFFFFF' }
        })
      }
      console.log('QR code set in ref')
    }
  } catch (e) {
    console.error('Error fetching QR code:', e)
  }
}

const saveTenant = async () => {
  try {
    await axios.put('/tenant', tenantForm.value)
    alert('Guardado!')
  } catch (e) {
    console.error(e)
  }
}

const connectWhatsApp = async () => {
  try {
    loadingWhatsApp.value = true
    whatsappStatus.value = 'connecting'
    console.log('Calling /whatsapp/connect...')
    await axios.post('/whatsapp/connect')
    console.log('Connect response received')
    
    qrCode.value = null // Reset QR before fetching new one
    await fetchQRCode()
    
    setTimeout(loadWhatsAppStatus, 10000)
  } catch (e) {
    console.error('Error connecting WhatsApp:', e)
  } finally {
    loadingWhatsApp.value = false
  }
}

const disconnectWhatsApp = async () => {
  if (!confirm('¿Desconectar WhatsApp?')) return
  try {
    await axios.post('/whatsapp/disconnect')
    whatsappStatus.value = 'disconnected'
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadData()
  loadWhatsAppStatus()
  pollingInterval = setInterval(loadWhatsAppStatus, 10000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
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
.btn { padding: 0.75rem 1.5rem; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s ease; }
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,82,204,0.3); }
.btn:active { transform: translateY(0); }
.btn-primary { background: #0052cc; color: white; }
.btn-primary:hover { background: #0044b3; }
.btn-danger { background: #dc2626; color: white; }
.status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; }
.status-badge.connected { background: #dcfce7; color: #008a45; }
.status-badge.connecting { background: #fef3c7; color: #92400e; }
.status-badge.disconnected { background: #fee2e2; color: #dc2626; }
.qr-section { text-align: center; padding: 2rem; }
.qr-code img { max-width: 300px; margin: 1rem 0; }
.loading { padding: 2rem; color: #666; }
.progress-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 0.5rem; }
.progress { height: 100%; background: #0052cc; transition: width 0.3s; }
</style>
