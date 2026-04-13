<template>
  <div class="onboarding-page">
    <div class="onboarding-card">
      <!-- Sidebar Progress -->
      <aside class="onboarding-sidebar">
        <div class="onboarding-brand">
          <img :src="logoHorizontal" alt="NETO" class="brand-logo">
        </div>

        <nav class="steps-nav">
          <div
            v-for="(step, index) in steps"
            :key="step.id"
            class="nav-step"
            :class="{
              'completed': currentStepIndex > index,
              'active': currentStepIndex === index,
              'locked': currentStepIndex < index
            }"
          >
            <div class="step-icon">
              <span v-if="currentStepIndex > index" class="check">✓</span>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <div class="step-info">
              <span class="step-label">{{ step.label }}</span>
              <span class="step-desc">{{ step.shortDesc }}</span>
            </div>
          </div>
        </nav>

        <div class="sidebar-footer">
          <p>Tu asistente IA está casi listo.</p>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="onboarding-main">
        <transition name="fade-slide" mode="out-in">
          <!-- Step 1: Business Info -->
          <div v-if="currentStepIndex === 0" key="step1" class="step-pane">
            <header class="step-header">
              <h2>Configurá tu negocio</h2>
              <p>Contanos un poco sobre lo que hacés para entrenar a tu IA.</p>
            </header>

            <form @submit.prevent="nextStep" class="onboarding-form">
              <div class="form-group">
                <label>¿A qué se dedica tu negocio? *</label>
                <input
                  v-model="formData.rubro"
                  required
                  placeholder="Ej: Restaurante Vegano, Tienda de Mascotas"
                />
              </div>

              <div class="form-group">
                <label>Descripción detallada *</label>
                <textarea
                  v-model="formData.description"
                  required
                  placeholder="Ej: Somos una pizzería artesanal que hace envíos en el barrio de Belgrano de 19 a 23hs."
                ></textarea>
              </div>

              <div class="form-group">
                <label>Días y horarios</label>
                <div class="horizontal-inputs">
                  <div class="sub-group">
                    <span class="input-label">Lunes a Viernes</span>
                    <input v-model="formData.business_hours.weekdays" placeholder="09:00 - 18:00" />
                  </div>
                  <div class="sub-group">
                    <span class="input-label">Fines de semana</span>
                    <input v-model="formData.business_hours.saturday" placeholder="10:00 - 14:00" />
                  </div>
                </div>
              </div>

              <footer class="step-footer">
                <button type="submit" class="btn btn-primary">Siguiente paso</button>
              </footer>
            </form>
          </div>

          <!-- Step 2: WhatsApp Connection -->
          <div v-else-if="currentStepIndex === 1" key="step2" class="step-pane">
            <header class="step-header text-center">
              <h2>Conectá tu WhatsApp</h2>
              <p>Escaneá el código para que el bot pueda empezar a responder.</p>
            </header>

            <div class="qr-preview-area">
              <div v-if="qrCode" class="qr-box">
                <div class="qr-border">
                  <img :src="qrCode" alt="QR" class="qr-image" />
                </div>
                <div class="qr-steps">
                  <p>1. Abrí WhatsApp en tu celu</p>
                  <p>2. Menú -> Dispositivos vinculados</p>
                  <p>3. Escaneá el código</p>
                </div>
              </div>
              <div v-else class="qr-placeholder">
                <div class="loader"></div>
                <p>Generando código seguro...</p>
              </div>
            </div>

            <div class="status-indicator-bar" :class="whatsappStatus">
              <span class="dot"></span>
              {{ whatsappStatus === 'connected' ? 'Número vinculado con éxito' : 'Esperando conexión...' }}
            </div>

            <footer class="step-footer split">
              <button @click="prevStep" class="btn btn-outline">Atrás</button>
              <button @click="nextStep" class="btn btn-primary" :disabled="whatsappStatus !== 'connected'">
                {{ whatsappStatus === 'connected' ? 'Continuar' : 'Vincular para seguir' }}
              </button>
            </footer>
          </div>

          <!-- Step 3: Catalog Upload -->
          <div v-else-if="currentStepIndex === 2" key="step3" class="step-pane">
            <header class="step-header">
              <h2>Cargá tus productos</h2>
              <p>Subí tu catálogo para que el bot conozca tus precios y stock.</p>
            </header>

            <div class="upload-vibe">
              <div class="template-selector">
                <button @click="downloadTemplate('both')" class="btn-template">
                  <span class="icon">📥</span>
                  <span>Descargar Plantilla CSV</span>
                </button>
              </div>

              <div 
                class="dropzone-area" 
                :class="{ 'dragging': isDragging, 'has-file': selectedFile }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
              >
                <input type="file" ref="fileInput" @change="handleFileSelect" accept=".csv" class="hidden-input" />
                
                <div v-if="!selectedFile" class="dropzone-empty" @click="$refs.fileInput.click()">
                  <div class="drop-icon">📄</div>
                  <p>Arrastrá tu archivo o buscá en tu equipo</p>
                  <span class="hint">Admite CSV y JSON hasta 5MB</span>
                </div>
                <div v-else class="dropzone-file">
                  <span class="file-icon">✅</span>
                  <div class="file-info">
                    <p class="file-name">{{ selectedFile.name }}</p>
                    <p class="file-meta">{{ formatFileSize(selectedFile.size) }}</p>
                  </div>
                  <button @click="selectedFile = null" class="btn-clear-file">×</button>
                </div>
              </div>

              <div v-if="uploadProgress > 0" class="mini-progress-bar">
                <div class="bar-fill" :style="{ width: uploadProgress + '%' }"></div>
              </div>

              <p v-if="uploadError" class="error-text">{{ uploadError }}</p>
            </div>

            <footer class="step-footer split">
              <button @click="prevStep" class="btn btn-outline">Atrás</button>
              <button 
                @click="uploadCatalog" 
                class="btn btn-primary" 
                :disabled="!selectedFile || isUploading"
              >
                {{ isUploading ? 'Procesando...' : 'Subir y continuar' }}
              </button>
            </footer>
          </div>

          <!-- Step 4: AI Behavior -->
          <div v-else-if="currentStepIndex === 3" key="step4" class="step-pane">
            <header class="step-header">
              <h2>Personalizá tu IA</h2>
              <p>Dale instrucciones específicas a tu chatbot.</p>
            </header>

            <form @submit.prevent="nextStep" class="onboarding-form">
              <div class="form-group">
                <label>Comportamiento del bot *</label>
                <textarea
                  v-model="formData.custom_prompt"
                  required
                  rows="6"
                  placeholder="Ej: Sé amable, usá emojis. Si preguntan por envíos, deciles que tardamos 48hs."
                ></textarea>
                <p class="field-hint">Este es el "cerebro" de tu bot. Sé lo más claro posible.</p>
              </div>

              <div class="form-group">
                <label>Mensaje de bienvenida</label>
                <input
                  v-model="formData.greeting_message"
                  placeholder="Hola! Bienvenido a mi negocio..."
                />
              </div>

              <footer class="step-footer split">
                <button @click="prevStep" class="btn btn-outline">Atrás</button>
                <button type="submit" class="btn btn-primary">Casi listo</button>
              </footer>
            </form>
          </div>

          <!-- Step 5: Final Review -->
          <div v-else-if="currentStepIndex === 4" key="step5" class="step-pane">
            <header class="step-header">
              <h2>Revisá tu configuración</h2>
              <p>Hicimos un resumen de todo lo configurado.</p>
            </header>

            <div class="review-grid">
              <div class="review-item">
                <span class="review-icon">🏢</span>
                <div class="review-data">
                  <strong>Negocio</strong>
                  <p>{{ formData.rubro }}</p>
                </div>
              </div>
              <div class="review-item">
                <span class="review-icon">📱</span>
                <div class="review-data">
                  <strong>WhatsApp</strong>
                  <p class="status-ok">Viculado correctamente</p>
                </div>
              </div>
              <div class="review-item">
                <span class="review-icon">🤖</span>
                <div class="review-data">
                  <strong>Personalidad</strong>
                  <p>Instrucciones de IA guardadas</p>
                </div>
              </div>
            </div>

            <div class="tos-agreement">
              <label class="check-container">
                <input type="checkbox" v-model="termsAccepted" />
                <span class="checkmark"></span>
                Acepto los términos del servicio de NETO
              </label>
            </div>

            <footer class="step-footer">
              <button 
                @click="activateAccount" 
                class="btn btn-activate btn-primary-gradient"
                :disabled="!termsAccepted || !canActivate"
              >
                🚀 Activar mi chatbot inteligente
              </button>
            </footer>
          </div>
        </transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import QRCode from 'qrcode'
import logoHorizontal from '@/assets/branding/media__1776087843743.png'

const router = useRouter()

const steps = [
  { id: 'business', label: 'Negocio', shortDesc: 'Info base' },
  { id: 'whatsapp', label: 'WhatsApp', shortDesc: 'Conexión' },
  { id: 'catalog', label: 'Catálogo', shortDesc: 'Productos' },
  { id: 'chatbot', label: 'IA', shortDesc: 'Personalidad' },
  { id: 'review', label: 'Activar', shortDesc: 'Final' },
]

const currentStepIndex = ref(0)
const formData = ref({
  rubro: '',
  description: '',
  business_hours: { weekdays: '', saturday: '', sunday: '' },
  faqs: [],
  custom_prompt: '',
  greeting_message: '',
})

const qrCode = ref(null)
const whatsappStatus = ref('disconnected')
const selectedFile = ref(null)
const isDragging = ref(false)
const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadError = ref(null)
const termsAccepted = ref(false)
const catalogItemsCount = ref(0)

const canActivate = computed(() => {
  return formData.value.rubro &&
         formData.value.description &&
         whatsappStatus.value === 'connected' &&
         catalogItemsCount.value >= 0 && // Even 0 is ok if they just start
         formData.value.custom_prompt
})

onMounted(async () => {
  await loadOnboardingStatus()
  loadQRCode()
})

async function loadOnboardingStatus() {
  try {
    const { data } = await axios.get('/tenant/onboarding')
    const { data: responseData } = data

    if (responseData.onboarding_step) {
      const stepIndex = steps.findIndex(s => s.id === responseData.onboarding_step)
      if (stepIndex !== -1) currentStepIndex.value = stepIndex
    }

    const tenantResponse = await axios.get('/tenant')
    const td = tenantResponse.data

    formData.value.rubro = td.rubro || ''
    formData.value.description = td.description || ''
    formData.value.business_hours = td.business_hours || { weekdays: '', saturday: '', sunday: '' }
    formData.value.custom_prompt = td.custom_prompt || ''
    whatsappStatus.value = td.whatsapp_status || 'disconnected'
  } catch (error) {
    console.error('Error loading onboarding:', error)
  }
}

async function loadQRCode() {
  try {
    const statusResponse = await axios.get('/whatsapp/status')
    if (statusResponse.data.status === 'disconnected') {
      await axios.post('/whatsapp/connect')
    }
    
    const { data } = await axios.get('/whatsapp/qr')
    if (data.qr_code) {
      if (data.qr_code.startsWith('data:')) {
        qrCode.value = data.qr_code
      } else {
        qrCode.value = await QRCode.toDataURL(data.qr_code, { width: 400, margin: 2 })
      }
    }
    
    setInterval(checkWhatsAppStatus, 8000)
  } catch (error) {
    console.error('Error loading QR:', error)
  }
}

async function checkWhatsAppStatus() {
  try {
    const { data } = await axios.get('/whatsapp/status')
    whatsappStatus.value = data.status || 'disconnected'
    if (whatsappStatus.value === 'connected' && currentStepIndex.value === 1) {
      // Auto move if we are in QR step
    }
  } catch (error) {
    console.error('Error status check:', error)
  }
}

function downloadTemplate(type) {
  window.open(`/catalog/template?type=${type}`, '_blank')
}

function handleFileSelect(event) {
  selectedFile.value = event.target.files[0]
}

function handleDrop(event) {
  isDragging.value = false
  selectedFile.value = event.dataTransfer.files[0]
}

function formatFileSize(bytes) {
  return (bytes / 1024).toFixed(1) + ' KB'
}

async function uploadCatalog() {
  if (!selectedFile.value) return
  isUploading.value = true
  uploadError.value = null

  const fd = new FormData()
  fd.append('file', selectedFile.value)
  fd.append('type', 'both')

  try {
    const { data } = await axios.post('/catalog/upload', fd, {
      onUploadProgress: (e) => uploadProgress.value = Math.round((e.loaded * 100) / e.total)
    })
    if (data.success) {
      catalogItemsCount.value = data.data.total_items
      await nextStep()
    } else {
      uploadError.value = data.message
    }
  } catch (e) {
    uploadError.value = 'Error al subir catálogo.'
  } finally {
    isUploading.value = false
  }
}

async function nextStep() {
  await saveOnboardingStep()
  if (currentStepIndex.value < steps.length - 1) {
    currentStepIndex.value++
  }
}

function prevStep() {
  if (currentStepIndex.value > 0) currentStepIndex.value--
}

async function saveOnboardingStep() {
  try {
    const p = {
      onboarding_step: steps[currentStepIndex.value].id,
      rubro: formData.value.rubro,
      description: formData.value.description,
      custom_prompt: formData.value.custom_prompt,
      business_hours: formData.value.business_hours,
    }
    await axios.put('/tenant/onboarding', p)
  } catch (error) {
    console.error('Error saving step:', error)
  }
}

async function activateAccount() {
  try {
    await axios.put('/tenant/onboarding', {
      ...formData.value,
      onboarding_step: 'review',
      onboarding_completed: true,
    })
    router.push('/')
  } catch (error) {
    alert('Error al activar. Intentá de nuevo.')
  }
}
</script>

<style scoped>
.onboarding-page {
  min-height: 100vh;
  background: var(--color-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.onboarding-card {
  width: 100%;
  max-width: 1000px;
  min-height: 640px;
  background: white;
  border-radius: 24px;
  display: flex;
  overflow: hidden;
  box-shadow: 0 40px 100px rgba(15, 23, 42, 0.1);
  border: 1px solid var(--color-border);
}

/* Sidebar */
.onboarding-sidebar {
  width: 280px;
  background: var(--color-dark);
  color: white;
  padding: 40px;
  display: flex;
  flex-direction: column;
}

.onboarding-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 64px;
}

.brand-logo {
  height: 32px;
  object-fit: contain;
}

.steps-nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.nav-step {
  display: flex;
  align-items: center;
  gap: 16px;
  opacity: 0.4;
  transition: all 0.3s;
}

.nav-step.active, .nav-step.completed { opacity: 1; }

.step-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
}

.nav-step.completed .step-icon {
  background: var(--color-success);
  border-color: var(--color-success);
}

.nav-step.active .step-icon {
  background: var(--color-primary);
  border-color: var(--color-primary);
}

.step-info {
  display: flex;
  flex-direction: column;
}

.step-label { font-size: 14px; font-weight: 700; }
.step-desc { font-size: 11px; opacity: 0.6; }

.sidebar-footer {
  margin-top: 40px;
  font-size: 12px;
  color: #64748B;
}

/* Main Content */
.onboarding-main {
  flex: 1;
  padding: 64px;
  background: white;
  position: relative;
}

.step-header {
  margin-bottom: 40px;
}

.step-header h2 {
  font-size: 28px;
  font-weight: 800;
  color: var(--color-dark);
  margin-bottom: 8px;
}

.step-header p {
  color: var(--color-text-muted);
  font-size: 16px;
}

/* Forms */
.onboarding-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 700;
  color: var(--color-dark);
}

.form-group input, .form-group textarea {
  padding: 12px 16px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  font-size: 15px;
  transition: all var(--transition-fast);
  background: var(--color-surface);
}

.form-group input:focus, .form-group textarea:focus {
  outline: none;
  background: white;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px var(--color-primary-10);
}

.horizontal-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.sub-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.input-label { font-size: 11px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; }

/* QR Section */
.qr-preview-area {
  display: flex;
  justify-content: center;
  margin-bottom: 32px;
}

.qr-box {
  text-align: center;
}

.qr-border {
  padding: 24px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 20px;
  box-shadow: var(--shadow-md);
  margin-bottom: 24px;
}

.qr-image { width: 240px; height: 240px; }

.qr-steps { font-size: 13px; color: var(--color-text-muted); line-height: 1.6; }

.status-indicator-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  background: var(--color-surface);
  margin-bottom: 32px;
}

.status-indicator-bar.connected { background: var(--color-success-10); color: var(--color-success); }

.dot {
  width: 8px; height: 8px; border-radius: 50%; background: #64748B;
}
.connected .dot { background: var(--color-success); animation: pulse 2s infinite; }

/* Upload Area */
.dropzone-area {
  border: 2px dashed var(--color-border);
  border-radius: 20px;
  padding: 64px 32px;
  background: var(--color-surface);
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
}

.dropzone-area:hover, .dropzone-area.dragging {
  border-color: var(--color-primary);
  background: white;
}

.drop-icon { font-size: 40px; margin-bottom: 16px; }

.dropzone-file {
  display: flex;
  align-items: center;
  gap: 16px;
  text-align: left;
}

.file-info { flex: 1; }
.file-name { font-weight: 700; color: var(--color-dark); margin-bottom: 2px; }
.file-meta { font-size: 12px; color: var(--color-text-muted); }

/* Review Grid */
.review-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.review-item {
  background: var(--color-surface);
  padding: 20px;
  border-radius: 16px;
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.review-icon { font-size: 24px; }
.review-data strong { font-size: 12px; text-transform: uppercase; color: var(--color-text-muted); display: block; }
.review-data p { font-size: 14px; font-weight: 600; color: var(--color-dark); margin-top: 4px; }

/* Buttons */
.step-footer {
  margin-top: 40px;
  display: flex;
}

.step-footer.split { justify-content: space-between; gap: 16px; }

.btn {
  padding: 12px 32px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-primary { background: var(--color-primary); color: white; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-outline { background: white; border-color: var(--color-border); color: var(--color-dark); }
.btn-activate { width: 100%; padding: 18px; font-size: 16px; }

.btn-primary-gradient {
  background: linear-gradient(135deg, var(--color-primary) 0%, #764ba2 100%);
  color: white;
  border: none;
}

/* Animations */
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateX(20px); }
.fade-slide-leave-to { opacity: 0; transform: translateX(-20px); }

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.5); opacity: 0.5; }
  100% { transform: scale(1); opacity: 1; }
}

.hidden-input { display: none; }
</style>
