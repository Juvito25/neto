<template>
  <div class="onboarding-wizard">
    <div class="wizard-container">
      <!-- Header con progreso -->
      <div class="wizard-header">
        <h1>Configuración de tu cuenta</h1>
        <div class="progress-bar">
          <div
            v-for="(step, index) in steps"
            :key="step.id"
            class="progress-step"
            :class="{
              'completed': currentStepIndex > index,
              'active': currentStepIndex === index,
              'locked': currentStepIndex < index
            }"
          >
            <div class="step-number">{{ index + 1 }}</div>
            <span class="step-label">{{ step.label }}</span>
          </div>
        </div>
      </div>

      <!-- Contenido del wizard -->
      <div class="wizard-content">
        <!-- Paso 1: Información del negocio -->
        <div v-if="currentStepIndex === 0" class="step-content">
          <h2>Información de tu negocio</h2>
          <p class="step-description">Contanos sobre tu negocio para personalizar tu chatbot</p>

          <form @submit.prevent="nextStep" class="wizard-form">
            <div class="form-group">
              <label for="rubro">Rubro *</label>
              <input
                type="text"
                id="rubro"
                v-model="formData.rubro"
                required
                placeholder="Ej: Restaurante, Tienda de ropa, Consultoría"
              />
            </div>

            <div class="form-group">
              <label for="description">Descripción *</label>
              <textarea
                id="description"
                v-model="formData.description"
                required
                rows="4"
                placeholder="Describí tu negocio, productos o servicios principales..."
              ></textarea>
            </div>

            <div class="form-group">
              <label>Horarios de atención</label>
              <div class="hours-grid">
                <div class="day-row">
                  <span class="day-label">Lunes a Viernes:</span>
                  <input
                    type="text"
                    v-model="formData.business_hours.weekdays"
                    placeholder="Ej: 09:00 - 18:00"
                  />
                </div>
                <div class="day-row">
                  <span class="day-label">Sábados:</span>
                  <input
                    type="text"
                    v-model="formData.business_hours.saturday"
                    placeholder="Ej: 09:00 - 13:00"
                  />
                </div>
                <div class="day-row">
                  <span class="day-label">Domingos:</span>
                  <input
                    type="text"
                    v-model="formData.business_hours.sunday"
                    placeholder="Ej: Cerrado"
                  />
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>FAQs (Preguntas frecuentes)</label>
              <div class="faqs-list">
                <div v-for="(faq, index) in formData.faqs" :key="index" class="faq-item">
                  <input
                    type="text"
                    v-model="faq.question"
                    placeholder="Pregunta"
                    class="faq-question"
                  />
                  <input
                    type="text"
                    v-model="faq.answer"
                    placeholder="Respuesta"
                    class="faq-answer"
                  />
                  <button type="button" @click="removeFaq(index)" class="btn-remove">×</button>
                </div>
                <button type="button" @click="addFaq" class="btn-add-faq">+ Agregar FAQ</button>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">Continuar</button>
            </div>
          </form>
        </div>

        <!-- Paso 2: Conectar WhatsApp -->
        <div v-if="currentStepIndex === 1" class="step-content">
          <h2>Conectá tu WhatsApp</h2>
          <p class="step-description">Escaneá el código QR para vincular tu número de WhatsApp</p>

          <div class="qr-section">
            <div v-if="qrCode" class="qr-container">
              <div v-if="qrCode.startsWith('data:image')" class="qr-image-wrapper">
                <img :src="qrCode" alt="Código QR" class="qr-image" />
              </div>
              <div v-else class="qr-code-text">
                <p class="qr-text-label">Escaneá este código con WhatsApp:</p>
                <div class="qr-text-code">{{ qrCode }}</div>
              </div>
              <p class="qr-instructions">
                1. Abrí WhatsApp en tu teléfono<br/>
                2. Tocá los tres puntos (Android) o Configuración (iPhone)<br/>
                3. Seleccioná "Dispositivos vinculados" → "Vincular dispositivo"<br/>
                4. Escaneá este código QR
              </p>
            </div>
            <div v-else class="qr-loading">
              <div class="spinner"></div>
              <p>Generando código QR...</p>
            </div>
          </div>

          <div class="connection-status" :class="whatsappStatus">
            <span class="status-indicator"></span>
            {{ whatsappStatusText }}
          </div>

          <div class="form-actions">
            <button type="button" @click="prevStep" class="btn-secondary">Atrás</button>
            <button
              type="button"
              @click="nextStep"
              class="btn-primary"
              :disabled="whatsappStatus !== 'connected'"
            >
              Continuar
            </button>
          </div>
        </div>

        <!-- Paso 3: Subir catálogo -->
        <div v-if="currentStepIndex === 2" class="step-content">
          <h2>Subí tu catálogo de productos/servicios</h2>
          <p class="step-description">El chatbot usará esta información para atender a tus clientes</p>

          <div class="catalog-upload">
            <div class="upload-options">
              <button @click="downloadTemplate('products')" class="btn-outline">
                📥 Descargar plantilla productos
              </button>
              <button @click="downloadTemplate('services')" class="btn-outline">
                📥 Descargar plantilla servicios
              </button>
              <button @click="downloadTemplate('both')" class="btn-outline">
                📥 Descargar plantilla combinada
              </button>
            </div>

            <div class="upload-area" :class="{ 'dragging': isDragging }"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop">

              <input
                type="file"
                ref="fileInput"
                @change="handleFileSelect"
                accept=".csv,.json"
                class="file-input"
              />

              <div class="upload-placeholder" v-if="!selectedFile">
                <span class="upload-icon">📁</span>
                <p>Arrastrá tu archivo CSV o JSON acá</p>
                <p class="upload-hint">o</p>
                <button type="button" @click="$refs.fileInput.click()" class="btn-select">
                  Seleccionar archivo
                </button>
                <p class="upload-max">Máximo 5MB</p>
              </div>

              <div v-else class="file-selected">
                <span class="file-icon">📄</span>
                <span class="file-name">{{ selectedFile.name }}</span>
                <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                <button type="button" @click="selectedFile = null" class="btn-remove-file">×</button>
              </div>
            </div>

            <div v-if="uploadProgress > 0" class="upload-progress">
              <div class="progress-bar-fill" :style="{ width: uploadProgress + '%' }"></div>
              <span>{{ uploadProgress }}%</span>
            </div>

            <div v-if="uploadError" class="upload-error">
              {{ uploadError }}
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="prevStep" class="btn-secondary">Atrás</button>
            <button
              type="button"
              @click="uploadCatalog"
              class="btn-primary"
              :disabled="!selectedFile || isUploading"
            >
              {{ isUploading ? 'Subiendo...' : 'Subir catálogo' }}
            </button>
          </div>
        </div>

        <!-- Paso 4: Personalizar chatbot -->
        <div v-if="currentStepIndex === 3" class="step-content">
          <h2>Personalizá tu chatbot</h2>
          <p class="step-description">Configurá cómo se va a comportar tu asistente virtual</p>

          <form @submit.prevent="nextStep" class="wizard-form">
            <div class="form-group">
              <label for="custom_prompt">Instrucciones del chatbot *</label>
              <textarea
                id="custom_prompt"
                v-model="formData.custom_prompt"
                required
                rows="6"
                placeholder="Ej: Sos un asistente virtual amable y profesional que ayuda a los clientes con preguntas sobre nuestros productos. Respondé de forma concisa y ofrecé ayuda adicional cuando sea necesario..."
              ></textarea>
              <p class="form-hint">Estas instrucciones guiarán el comportamiento y tono de tu chatbot</p>
            </div>

            <div class="form-group">
              <label for="greeting_message">Mensaje de saludo</label>
              <input
                type="text"
                id="greeting_message"
                v-model="formData.greeting_message"
                placeholder="¡Hola! 👋 Bienvenido a {{business_name}}, ¿en qué puedo ayudarte?"
              />
              <p class="form-hint">Mensaje que se envía automáticamente cuando un cliente inicia conversación</p>
            </div>

            <div class="form-actions">
              <button type="button" @click="prevStep" class="btn-secondary">Atrás</button>
              <button type="submit" class="btn-primary">Continuar</button>
            </div>
          </form>
        </div>

        <!-- Paso 5: Revisar y activar -->
        <div v-if="currentStepIndex === 4" class="step-content">
          <h2>Revisá y activá tu cuenta</h2>
          <p class="step-description">Último paso para comenzar a usar NETO</p>

          <div class="review-section">
            <div class="review-card">
              <h3>📋 Información del negocio</h3>
              <ul>
                <li><strong>Rubro:</strong> {{ formData.rubro || 'No completado' }}</li>
                <li><strong>Descripción:</strong> {{ formData.description ? formData.description.substring(0, 100) + '...' : 'No completada' }}</li>
              </ul>
            </div>

            <div class="review-card">
              <h3>📱 WhatsApp</h3>
              <span class="status-badge" :class="whatsappStatus">
                {{ whatsappStatus === 'connected' ? 'Conectado ✓' : 'No conectado' }}
              </span>
            </div>

            <div class="review-card">
              <h3>📦 Catálogo</h3>
              <p>{{ catalogItemsCount }} productos/servicios cargados</p>
            </div>

            <div class="review-card">
              <h3>🤖 Chatbot</h3>
              <p>{{ formData.custom_prompt ? 'Personalizado ✓' : 'No configurado' }}</p>
            </div>
          </div>

          <div class="activation-section">
            <label class="checkbox-label">
              <input type="checkbox" v-model="termsAccepted" />
              Acepto los términos y condiciones de NETO
            </label>
          </div>

          <div class="form-actions">
            <button type="button" @click="prevStep" class="btn-secondary">Atrás</button>
            <button
              type="button"
              @click="activateAccount"
              class="btn-primary btn-activate"
              :disabled="!termsAccepted || !canActivate"
            >
              🚀 Activar mi cuenta
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import QRCode from 'qrcode'

const router = useRouter()

const steps = [
  { id: 'business', label: 'Negocio' },
  { id: 'whatsapp', label: 'WhatsApp' },
  { id: 'catalog', label: 'Catálogo' },
  { id: 'chatbot', label: 'Chatbot' },
  { id: 'review', label: 'Activar' },
]

const currentStepIndex = ref(0)
const formData = ref({
  rubro: '',
  description: '',
  business_hours: {
    weekdays: '',
    saturday: '',
    sunday: '',
  },
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

const whatsappStatusText = computed(() => {
  const statusMap = {
    disconnected: 'Desconectado',
    connecting: 'Conectando...',
    connected: 'Conectado',
  }
  return statusMap[whatsappStatus.value] || 'Desconectado'
})

const canActivate = computed(() => {
  return formData.value.rubro &&
         formData.value.description &&
         whatsappStatus.value === 'connected' &&
         catalogItemsCount.value > 0 &&
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
      if (stepIndex !== -1) {
        currentStepIndex.value = stepIndex
      }
    }

    // Cargar datos existentes
    const tenantResponse = await axios.get('/tenant')
    const tenantData = tenantResponse.data

    if (tenantData.rubro) formData.value.rubro = tenantData.rubro
    if (tenantData.description) formData.value.description = tenantData.description
    if (tenantData.business_hours) {
      formData.value.business_hours = {
        weekdays: tenantData.business_hours.weekdays || '',
        saturday: tenantData.business_hours.saturday || '',
        sunday: tenantData.business_hours.sunday || '',
      }
    }
    if (tenantData.faqs) formData.value.faqs = tenantData.faqs
    if (tenantData.custom_prompt) formData.value.custom_prompt = tenantData.custom_prompt
    if (tenantData.whatsapp_status) whatsappStatus.value = tenantData.whatsapp_status
  } catch (error) {
    console.error('Error loading onboarding status:', error)
  }
}

async function loadQRCode() {
  try {
    // Primero verificar si hay instancia y su estado
    const statusResponse = await axios.get('/whatsapp/status')
    const currentStatus = statusResponse.data.status
    
    if (currentStatus === 'not_configured' || currentStatus === 'disconnected') {
      // Crear instancia primero
      await axios.post('/whatsapp/connect')
    }
    
    // Ahora obtener el QR
    const { data } = await axios.get('/whatsapp/qr')
    
    if (data.qr_code) {
      // Puede ser base64 (data:image/png;base64,...) o un código de texto
      if (data.qr_code.startsWith('data:')) {
        qrCode.value = data.qr_code // Ya es una imagen base64
      } else {
        // Generar imagen QR desde el código de texto
        try {
          qrCode.value = await QRCode.toDataURL(data.qr_code, {
            width: 280,
            margin: 2,
            color: {
              dark: '#000000',
              light: '#FFFFFF'
            }
          })
        } catch (e) {
          console.error('Error generating QR:', e)
          qrCode.value = data.qr_code // Fallback to text
        }
      }
    } else if (data.status === 'connecting') {
      qrCode.value = null // Se muestra el spinner
    }

    // Polling para verificar estado
    setInterval(checkWhatsAppStatus, 5000)
  } catch (error) {
    console.error('Error loading QR code:', error)
    qrCode.value = null
  }
}

async function checkWhatsAppStatus() {
  try {
    const { data } = await axios.get('/whatsapp/status')
    whatsappStatus.value = data.status || 'disconnected'

    if (whatsappStatus.value === 'connected') {
      await saveOnboardingStep()
    }
  } catch (error) {
    console.error('Error checking WhatsApp status:', error)
  }
}

function addFaq() {
  formData.value.faqs.push({ question: '', answer: '' })
}

function removeFaq(index) {
  formData.value.faqs.splice(index, 1)
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
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

async function uploadCatalog() {
  if (!selectedFile.value) return

  isUploading.value = true
  uploadError.value = null
  uploadProgress.value = 0

  const formDataUpload = new FormData()
  formDataUpload.append('file', selectedFile.value)
  formDataUpload.append('type', 'both')
  formDataUpload.append('name', 'Mi Catálogo')

  try {
    const { data } = await axios.post('/catalog/upload', formDataUpload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    if (data.success) {
      catalogItemsCount.value = data.data.total_items || 0
      selectedFile.value = null
      await nextStep()
    } else {
      uploadError.value = data.message || 'Error al subir el catálogo'
    }
  } catch (error) {
    uploadError.value = error.response?.data?.message || 'Error al subir el catálogo'
  } finally {
    isUploading.value = false
  }
}

async function nextStep() {
  if (currentStepIndex.value === 0) {
    await saveOnboardingStep()
  } else if (currentStepIndex.value === 2) {
    // El catálogo ya se subió, solo avanzamos
    await saveOnboardingStep()
  } else if (currentStepIndex.value === 3) {
    await saveOnboardingStep()
  }

  if (currentStepIndex.value < steps.length - 1) {
    currentStepIndex.value++
  }
}

function prevStep() {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
  }
}

async function saveOnboardingStep() {
  try {
    const currentStepId = steps[currentStepIndex.value].id

    const payload = {
      onboarding_step: currentStepId,
    }

    // Incluir datos del paso actual
    if (currentStepIndex.value === 0) {
      payload.rubro = formData.value.rubro
      payload.description = formData.value.description
      payload.business_hours = formData.value.business_hours
      payload.faqs = formData.value.faqs
    } else if (currentStepIndex.value === 3) {
      payload.custom_prompt = formData.value.custom_prompt
    }

    await axios.put('/tenant/onboarding', payload)
  } catch (error) {
    console.error('Error saving onboarding step:', error)
  }
}

async function activateAccount() {
  try {
    await axios.put('/tenant/onboarding', {
      onboarding_step: 'review',
      onboarding_completed: true,
      rubro: formData.value.rubro,
      description: formData.value.description,
      business_hours: formData.value.business_hours,
      faqs: formData.value.faqs,
      custom_prompt: formData.value.custom_prompt,
    })

    // Redirigir al dashboard
    router.push('/')
  } catch (error) {
    console.error('Error activating account:', error)
    alert('Hubo un error al activar tu cuenta. Por favor intentá de nuevo.')
  }
}
</script>

<style scoped>
.onboarding-wizard {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.wizard-container {
  max-width: 800px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.wizard-header {
  padding: 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.wizard-header h1 {
  margin: 0 0 1.5rem 0;
  color: #1e293b;
  font-size: 1.5rem;
}

.progress-bar {
  display: flex;
  justify-content: space-between;
  position: relative;
}

.progress-bar::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 0;
  right: 0;
  height: 2px;
  background: #e2e8f0;
  z-index: 0;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
  flex: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
}

.progress-step.completed .step-number {
  background: #10b981;
  color: white;
}

.progress-step.active .step-number {
  background: #667eea;
  color: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
}

.step-label {
  font-size: 0.75rem;
  color: #64748b;
  text-align: center;
}

.wizard-content {
  padding: 2rem;
}

.step-content h2 {
  margin: 0 0 0.5rem 0;
  color: #1e293b;
}

.step-description {
  color: #64748b;
  margin-bottom: 2rem;
}

.wizard-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #475569;
}

.form-group input,
.form-group textarea {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.form-hint {
  font-size: 0.875rem;
  color: #64748b;
}

.hours-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.day-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.day-label {
  min-width: 120px;
  font-size: 0.875rem;
  color: #64748b;
}

.day-row input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.faqs-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.faq-item {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.faq-question {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.faq-answer {
  flex: 2;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.btn-remove {
  width: 32px;
  height: 32px;
  border: none;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-add-faq {
  padding: 0.5rem 1rem;
  border: 2px dashed #e2e8f0;
  background: #f8fafc;
  color: #667eea;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-add-faq:hover {
  border-color: #667eea;
  background: #f1f5f9;
}

.qr-section {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
}

.qr-container {
  text-align: center;
}

.qr-image-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.qr-image {
  width: 256px;
  height: 256px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
}

.qr-code-text {
  background: #f8fafc;
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.qr-text-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}

.qr-text-code {
  font-family: monospace;
  font-size: 0.75rem;
  word-break: break-all;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.qr-instructions {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.6;
}

.qr-loading {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.connection-status {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  font-weight: 500;
}

.connection-status.connected {
  background: #dcfce7;
  color: #008a45;
}

.connection-status.disconnected,
.connection-status.connecting {
  background: #fef3c7;
  color: #92400e;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}

.upload-options {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.upload-area {
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  transition: all 0.2s;
  background: #f8fafc;
}

.upload-area.dragging {
  border-color: #667eea;
  background: #f1f5f9;
}

.file-input {
  display: none;
}

.upload-placeholder {
  color: #64748b;
}

.upload-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.upload-hint {
  color: #94a3b8;
  margin: 0.5rem 0;
}

.btn-select {
  padding: 0.75rem 1.5rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  margin-top: 0.5rem;
}

.upload-max {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.5rem;
}

.file-selected {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.file-icon {
  font-size: 2rem;
}

.file-name {
  flex: 1;
  font-weight: 500;
  color: #1e293b;
}

.file-size {
  color: #64748b;
  font-size: 0.875rem;
}

.btn-remove-file {
  width: 32px;
  height: 32px;
  border: none;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
}

.upload-progress {
  position: relative;
  height: 32px;
  background: #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  margin-top: 1rem;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea, #764ba2);
  transition: width 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.upload-error {
  background: #fee2e2;
  color: #dc2626;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.btn-primary {
  flex: 1;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  flex: 1;
  padding: 1rem 2rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-outline {
  padding: 0.75rem 1rem;
  background: transparent;
  color: #667eea;
  border: 1px solid #667eea;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-outline:hover {
  background: #f1f5f9;
}

.review-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.review-card {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.review-card h3 {
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  color: #1e293b;
}

.review-card ul {
  margin: 0;
  padding-left: 1.5rem;
  color: #475569;
}

.review-card li {
  margin-bottom: 0.5rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge.connected {
  background: #dcfce7;
  color: #008a45;
}

.status-badge.disconnected {
  background: #fee2e2;
  color: #dc2626;
}

.activation-section {
  background: #fef3c7;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: 500;
  color: #92400e;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.btn-activate {
  font-size: 1.125rem;
}
</style>
