<template>
  <div class="settings-view-container">
    <header class="view-header">
      <h1 class="view-title">Configuración</h1>
    </header>

    <div class="settings-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        class="tab-btn"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <main class="settings-main">
      <transition name="fade-fast" mode="out-in">
        <!-- Business Tab -->
        <div v-if="activeTab === 'negocio'" key="negocio" class="tab-pane">
          <section class="settings-section">
            <div class="section-info">
              <h3>Información del negocio</h3>
              <p>Estos datos ayudan a NETO AI a personalizar las respuestas.</p>
            </div>
            
            <form @submit.prevent="saveTenant" class="settings-form">
              <div class="form-group">
                <label>Nombre del negocio</label>
                <input v-model="tenantForm.business_name" placeholder="Ej: Panadería San José" />
              </div>
              
              <div class="form-group">
                <label>Descripción corta</label>
                <textarea v-model="tenantForm.description" placeholder="A qué se dedica tu negocio..."></textarea>
              </div>

              <div class="form-group">
                <label>Instrucciones para el bot</label>
                <textarea v-model="tenantForm.custom_prompt" placeholder="Ej: 'Sé amable y siempre ofrece las facturas del día'"></textarea>
                <p class="field-help">
                  <span class="help-icon">💡</span>
                  <strong>Tip:</strong> Sé específico. En lugar de "sé amable", escribí "Cuando el cliente pregunte el precio, siempre mencioná si hay descuento por cantidad."
                </p>
              </div>

              <div class="payment-methods">
                <h3>Medios de pago</h3>
                
                <div class="payment-card">
                  <div class="payment-header">
                    <div class="payment-title">
                      <span class="payment-icon">🏦</span>
                      <div>
                        <p class="name">Transferencia bancaria</p>
                        <p class="desc">CBU, Alias y datos de cuenta</p>
                      </div>
                    </div>
                    <label class="switch">
                      <input type="checkbox" v-model="tenantForm.payment_transfer_enabled">
                      <span class="slider"></span>
                    </label>
                  </div>
                  
                  <transition name="expand">
                    <div v-if="tenantForm.payment_transfer_enabled" class="payment-body">
                      <div class="form-grid">
                        <div class="form-group">
                          <label>CBU o Alias</label>
                          <input v-model="tenantForm.payment_transfer_cbu" placeholder="Ej: neto.bot.banco" />
                          <p class="field-help">
                            <span class="help-icon">💡</span>
                            Este CBU o alias aparecerá automáticamente cuando un cliente confirme que quiere pagar por transferencia.
                          </p>
                        </div>
                        <div class="form-group">
                          <label>Titular</label>
                          <input v-model="tenantForm.payment_transfer_name" placeholder="Ej: Juan Neto" />
                        </div>
                        <div class="form-group full-width">
                          <label>Banco</label>
                          <input v-model="tenantForm.payment_transfer_bank" placeholder="Ej: Banco Nación" />
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>

                <div class="payment-card">
                  <div class="payment-header">
                    <div class="payment-title">
                      <span class="payment-icon">💵</span>
                      <div>
                        <p class="name">Efectivo / Pago al retirar</p>
                        <p class="desc">Cobro presencial en el local</p>
                      </div>
                    </div>
                    <label class="switch">
                      <input type="checkbox" v-model="tenantForm.payment_cash_enabled">
                      <span class="slider"></span>
                    </label>
                  </div>
                  
                  <transition name="expand">
                    <div v-if="tenantForm.payment_cash_enabled" class="payment-body">
                      <div class="form-group full-width">
                        <label>Nota para el cliente</label>
                        <input v-model="tenantForm.payment_cash_note" placeholder="Ej: Abonás al retirar en Av. Rivadavia 123" />
                      </div>
                    </div>
                  </transition>
                </div>
              </div>

              <div class="form-footer">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving" class="btn-spinner"></span>
                  {{ saving ? 'Guardando...' : 'Guardar cambios' }}
                </button>
              </div>
            </form>
          </section>
        </div>

        <!-- WhatsApp Tab -->
        <div v-else-if="activeTab === 'whatsapp'" key="whatsapp" class="tab-pane">
          <section class="settings-section">
            <div class="section-info">
              <h3>Conexión con WhatsApp</h3>
              <p>Vinculá tu número de WhatsApp para que el bot responda automáticamente a tus clientes.</p>
            </div>

            <div class="whatsapp-card" :class="whatsappStatus">
              <div v-if="whatsappStatus === 'connected'" class="ws-connected">
                <div class="ws-status-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="ws-info">
                  <h4>WhatsApp Conectado</h4>
                  <p>🤖 Tu bot ya está respondiendo automáticamente. Tu negocio nunca duerme.</p>
                </div>
                <button @click="disconnectWhatsApp" class="btn btn-outline-danger btn-sm">Desconectar</button>
              </div>

              <div v-else-if="whatsappStatus === 'connecting'" class="ws-connecting">
                <div v-if="loadingWhatsApp" class="ws-loading">
                  <div class="spinner"></div>
                  <p>Generando código QR...</p>
                </div>
                <div v-else-if="qrCode" class="ws-qr-container">
                  <div class="ws-qr-header">
                    <h4>Escaneá el código</h4>
                    <p class="ws-help-text">
                      <strong>📱 Cómo conectar WhatsApp:</strong><br>
                      1. Abrí WhatsApp en tu celular<br>
                      2. Tocá los 3 puntos (⋮) → Dispositivos vinculados<br>
                      3. Tocá "Vincular un dispositivo"<br>
                      4. Escaneá el código QR que aparece abajo
                    </p>
                  </div>
                  <div class="qr-wrapper">
                    <img :src="qrCode" alt="Scan me" class="qr-image" />
                  </div>
                  <button @click="connectWhatsApp" class="btn btn-ghost btn-xs">Actualizar QR</button>
                </div>
              </div>

              <div v-else class="ws-disconnected">
                <div class="ws-status-icon muted">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="ws-info">
                  <h4>Bot Desconectado</h4>
                  <p>Tu WhatsApp puede responder de forma automática. Vinculá tu número para activar la IA.</p>
                </div>
                <button @click="connectWhatsApp" class="btn btn-primary btn-sm">Vincular Dispositivo</button>
              </div>
            </div>
          </section>
        </div>

        <!-- Plan Tab -->
        <div v-else-if="activeTab === 'plan'" key="plan" class="tab-pane">
          <section class="settings-section">
            <div class="section-info">
              <h3>Tu suscripción</h3>
              <p>Detalles de tu plan actual y uso de mensajes.</p>
            </div>

            <div class="plan-card">
              <div class="plan-header">
                <div>
                  <span class="plan-label">Plan Actual</span>
                  <h2 class="plan-name">{{ tenant?.plan?.display_name || 'Free' }}</h2>
                </div>
                <div class="plan-price">
                  <span class="currency">$</span>
                  <span class="amount">{{ tenant?.plan?.price_cents ? (tenant.plan.price_cents / 100).toFixed(0) : '19' }}</span>
                  <span class="period">/mes</span>
                </div>
              </div>

              <div class="usage-section">
                <div class="usage-labels">
                  <span>Mensajes este mes</span>
                  <span>{{ tenant?.messages_used || 0 }} / {{ tenant?.plan?.messages_limit || 1000 }}</span>
                </div>
                <div class="progress-bar-container">
                  <div class="progress-bar-fill" :style="{ width: progressPercent + '%' }"></div>
                </div>
              </div>

              <div class="plan-features">
                <ul class="feature-list">
                  <li v-for="f in features" :key="f" class="feature-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ f }}
                  </li>
                </ul>
              </div>

              <button @click="router.push('/plans')" class="btn btn-outline btn-full">Mejorar Plan</button>
            </div>
          </section>
        </div>
      </transition>
    </main>
    
    <PaymentSuccessModal />
    <Toast 
      v-if="toast.show" 
      :message="toast.message" 
      :type="toast.type" 
      @close="toast.show = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import QRCode from 'qrcode'
import PaymentSuccessModal from '@/components/PaymentSuccessModal.vue'
import Toast from '@/components/Toast.vue'

const router = useRouter()
const route = useRoute()
const activeTab = ref(route.query.tab || 'negocio')
const tenant = ref(null)
const whatsappStatus = ref('disconnected')
const qrCode = ref(null)
const loadingWhatsApp = ref(false)
const saving = ref(false)
let pollingInterval = null
const toast = ref({ show: false, message: '', type: 'success' })

const tabs = [
  { id: 'negocio', label: 'Negocio' },
  { id: 'whatsapp', label: 'WhatsApp' },
  { id: 'plan', label: 'Plan' },
]

const features = [
  'Bot activo 24/7',
  'IA Avanzada Llama 3',
  'Dashboard de métricas',
  'Soporte prioritario',
]

const tenantForm = ref({
  business_name: '',
  description: '',
  custom_prompt: '',
  payment_transfer_enabled: false,
  payment_transfer_cbu: '',
  payment_transfer_name: '',
  payment_transfer_bank: '',
  payment_cash_enabled: false,
  payment_cash_note: '',
})

const progressPercent = computed(() => {
  if (!tenant.value || !tenant.value.plan) return 0
  const limit = tenant.value.plan.messages_limit || 1000
  return Math.min(100, (tenant.value.messages_used / limit) * 100)
})

const loadData = async () => {
  try {
    const { data } = await axios.get('/tenant')
    tenant.value = data
    tenantForm.value = {
      business_name: data.business_name || '',
      description: data.description || '',
      custom_prompt: data.custom_prompt || '',
      payment_transfer_enabled: Boolean(data.payment_transfer_enabled),
      payment_transfer_cbu: data.payment_transfer_cbu || '',
      payment_transfer_name: data.payment_transfer_name || '',
      payment_transfer_bank: data.payment_transfer_bank || '',
      payment_cash_enabled: Boolean(data.payment_cash_enabled),
      payment_cash_note: data.payment_cash_note || '',
    }
  } catch (e) {
    console.error('Error loading settings:', e)
  }
}

const loadWhatsAppStatus = async () => {
  try {
    const { data } = await axios.get('/whatsapp/status')
    const wasConnected = whatsappStatus.value === 'connected'
    whatsappStatus.value = data.status
    
    if (whatsappStatus.value === 'connecting' && !qrCode.value) {
      fetchQRCode()
    }
    
    if (whatsappStatus.value === 'connected') {
      qrCode.value = null
      if (!wasConnected) {
        toast.value = { show: true, message: '¡WhatsApp conectado! Tu bot está activo 🤖', type: 'success' }
      }
    }
  } catch (e) {
    console.error('Error loading WhatsApp status:', e)
  }
}

const fetchQRCode = async () => {
  try {
    const { data } = await axios.get('/whatsapp/qr')
    if (data.qr_code) {
      if (data.qr_code.startsWith('data:')) {
        qrCode.value = data.qr_code
      } else {
        qrCode.value = await QRCode.toDataURL(data.qr_code, {
          width: 280,
          margin: 2,
          color: { dark: '#0F172A', light: '#FFFFFF' }
        })
      }
    }
  } catch (e) {
    console.error('Error fetching QR code:', e)
  }
}

const saveTenant = async () => {
  saving.value = true
  try {
    await axios.put('/tenant', tenantForm.value)
    toast.value = { show: true, message: 'Cambios guardados correctamente', type: 'success' }
  } catch (e) {
    console.error(e)
    toast.value = { show: true, message: 'Error al guardar los cambios', type: 'error' }
  } finally {
    saving.value = false
  }
}

const connectWhatsApp = async () => {
  try {
    loadingWhatsApp.value = true
    whatsappStatus.value = 'connecting'
    await axios.post('/whatsapp/connect')
    qrCode.value = null
    await fetchQRCode()
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
    qrCode.value = null
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadData()
  loadWhatsAppStatus()
  pollingInterval = setInterval(loadWhatsAppStatus, 15000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
</script>

<style scoped>
.settings-view-container {
  padding: 32px;
  max-width: 800px;
  margin: 0 auto;
}

.view-header {
  margin-bottom: 24px;
}

.view-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-dark);
}

/* Tabs */
.settings-tabs {
  display: flex;
  gap: 32px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 32px;
}

.tab-btn {
  background: none;
  border: none;
  padding: 12px 4px;
  font-size: 15px;
  font-weight: 500;
  color: var(--color-text-muted);
  cursor: pointer;
  position: relative;
  transition: all var(--transition-fast);
}

.tab-btn:hover { color: var(--color-dark); }
.tab-btn.active { color: var(--color-primary); font-weight: 600; }
.tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--color-primary);
}

/* Sections */
.settings-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.section-info h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}

.section-info p {
  font-size: 14px;
  color: var(--color-text-muted);
}

/* Forms */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-dark);
}

.form-group input, .form-group textarea {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: white;
  font-size: 14px;
  transition: all var(--transition-fast);
}

.form-group textarea { min-height: 100px; resize: vertical; }

.form-group input:focus, .form-group textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-10);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.full-width { grid-column: span 2; }

/* Payment Methods */
.payment-methods h3 {
  font-size: 16px;
  font-weight: 600;
  margin-top: 24px;
  margin-bottom: 16px;
}

.payment-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  margin-bottom: 12px;
  overflow: hidden;
}

.payment-header {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.payment-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.payment-icon { font-size: 20px; }

.payment-title .name { font-size: 14px; font-weight: 600; }
.payment-title .desc { font-size: 12px; color: var(--color-text-muted); }

.payment-body {
  padding: 0 20px 20px 20px;
  border-top: 1px solid var(--color-surface);
  background: var(--color-surface);
}

/* Custom Toggle */
.switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.switch input { opacity: 0; width: 0; height: 0; }

.slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #CBD5E1;
  transition: .4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider { background-color: var(--color-success); }
input:checked + .slider:before { transform: translateX(20px); }

/* WhatsApp Card */
.whatsapp-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 32px;
}

.whatsapp-card.connected { border-color: var(--color-success); background: var(--color-success-10); }

.ws-connected, .ws-disconnected {
  display: flex;
  align-items: center;
  gap: 20px;
}

.ws-status-icon {
  width: 64px;
  height: 64px;
  background: var(--color-success);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ws-status-icon.muted { background: var(--color-surface); color: var(--color-text-muted); }

.ws-info h4 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.ws-info p { font-size: 14px; color: var(--color-text-muted); }

.ws-info { flex: 1; }

.ws-loading { text-align: center; padding: 20px; }
.spinner {
  width: 40px; height: 40px; border: 3px solid var(--color-primary-10); border-top-color: var(--color-primary);
  border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.ws-qr-container { text-align: center; }
.ws-qr-header { margin-bottom: 24px; }
.qr-wrapper {
  background: white; padding: 16px; border-radius: 12px; display: inline-block;
  box-shadow: var(--shadow-md); margin-bottom: 16px; border: 1px solid var(--color-border);
}
.qr-image { width: 240px; height: 240px; }

/* Plan Card */
.plan-card {
  background: var(--color-dark);
  color: white;
  padding: 32px;
  border-radius: var(--radius-lg);
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.plan-label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; }
.plan-name { font-size: 28px; font-weight: 700; }

.plan-price .currency { font-size: 20px; vertical-align: super; }
.plan-price .amount { font-size: 40px; font-weight: 700; }
.plan-price .period { font-size: 16px; color: var(--color-text-muted); }

.usage-section { margin-bottom: 32px; }
.usage-labels { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.progress-bar-container { height: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: var(--color-primary); border-radius: 4px; transition: width 0.5s ease; }

.feature-list { list-style: none; margin-bottom: 32px; }
.feature-item { display: flex; align-items: center; gap: 10px; font-size: 14px; margin-bottom: 12px; color: #94A3B8; }
.feature-item svg { color: var(--color-success); }

/* Helpers */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 24px;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all var(--transition-fast);
  border: 1px solid transparent;
}

.btn-primary { background: var(--color-primary); color: white; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 8px;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-outline { background: white; border-color: var(--color-border); color: var(--color-dark); }
.btn-outline-danger { background: white; border-color: var(--color-danger); color: var(--color-danger); }
.btn-ghost { background: transparent; color: var(--color-text-muted); }
.btn-ghost:hover { background: var(--color-surface); color: var(--color-primary); }
.btn-full { width: 100%; }
.btn-sm { padding: 6px 12px; font-size: 13px; }
.btn-xs { padding: 4px 8px; font-size: 12px; }

/* Transitions */
.fade-fast-enter-active, .fade-fast-leave-active { transition: opacity 0.15s ease; }
.fade-fast-enter-from, .fade-fast-leave-to { opacity: 0; }

.expand-enter-active, .expand-leave-active { transition: all 0.25s ease; max-height: 400px; overflow: hidden; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; }
</style>
