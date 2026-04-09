<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const step = ref('select') // select, upload, viewing
const catalogType = ref(null)
const catalog = ref(null)
const items = ref([])
const loading = ref(false)
const uploading = ref(false)
const error = ref('')
const file = ref(null)

const hasCatalog = computed(() => catalog.value && catalog.value.status === 'active')

onMounted(async () => {
  await loadCatalog()
})

async function loadCatalog() {
  loading.value = true
  try {
    const { data } = await axios.get('/catalog')
    if (data.success && data.data) {
      catalog.value = data.data
      step.value = 'viewing'
      await loadItems()
    } else {
      step.value = 'select'
    }
  } catch (e) {
    step.value = 'select'
  } finally {
    loading.value = false
  }
}

async function loadItems() {
  if (!catalog.value) return
  try {
    const { data } = await axios.get(`/catalog/${catalog.value.id}/items`)
    if (data.success) {
      items.value = data.data.data || []
    }
  } catch (e) {
    console.error(e)
  }
}

function selectType(type) {
  catalogType.value = type
  step.value = 'upload'
}

async function downloadTemplate() {
  try {
    const response = await axios.get(`/catalog/template?type=${catalogType.value}`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${catalogType.value}_template.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    error.value = 'Error al descargar template'
  }
}

function handleFileSelect(event) {
  const selected = event.target.files[0]
  if (selected) {
    if (selected.size > 5 * 1024 * 1024) {
      error.value = 'El archivo debe ser menor a 5MB'
      return
    }
    file.value = selected
    error.value = ''
  }
}

function handleDrop(event) {
  event.preventDefault()
  const dropped = event.dataTransfer.files[0]
  if (dropped) {
    if (dropped.size > 5 * 1024 * 1024) {
      error.value = 'El archivo debe ser menor a 5MB'
      return
    }
    file.value = dropped
    error.value = ''
  }
}

async function uploadFile() {
  if (!file.value) {
    error.value = 'Selecciona un archivo'
    return
  }

  uploading.value = true
  error.value = ''

  const formData = new FormData()
  formData.append('file', file.value)
  formData.append('type', catalogType.value)

  try {
    const { data } = await axios.post('/catalog/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (data.success) {
      catalog.value = data.data
      items.value = []
      step.value = 'viewing'
      if (data.data.total_items > 0) {
        await loadItems()
      }
    } else {
      error.value = data.message
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al subir archivo'
  } finally {
    uploading.value = false
  }
}

async function deleteCatalog() {
  if (!confirm('¿Estás seguro de eliminar el catálogo?')) return

  try {
    await axios.delete(`/catalog/${catalog.value.id}`)
    catalog.value = null
    items.value = []
    step.value = 'select'
  } catch (e) {
    error.value = 'Error al eliminar catálogo'
  }
}

function reset() {
  step.value = 'select'
  catalogType.value = null
  catalog.value = null
  items.value = []
  file.value = null
  error.value = ''
}
</script>

<template>
  <div class="catalog-view">
    <header class="header">
      <h1>Mi Catálogo</h1>
      <button v-if="hasCatalog" @click="reset" class="btn-secondary">
        Nuevo Catálogo
      </button>
    </header>

    <div v-if="loading" class="loading">Cargando...</div>

    <!-- Step 1: Select Type -->
    <div v-else-if="step === 'select'" class="type-selector">
      <h2>¿Qué tipo de negocio tienes?</h2>
      <p class="subtitle">Esto determinará cómo el chatbot responderá a tus clientes</p>

      <div class="options">
        <button class="option-card" @click="selectType('products')">
          <div class="icon">📦</div>
          <h3>Productos Físicos</h3>
          <p>Vendes ropa, electrónica, alimentos, etc.</p>
        </button>

        <button class="option-card" @click="selectType('services')">
          <div class="icon">🛠️</div>
          <h3>Servicios</h3>
          <p>Ofreces clases, consultorías, reparaciones, etc.</p>
        </button>

        <button class="option-card" @click="selectType('both')">
          <div class="icon">🏪</div>
          <h3>Ambos</h3>
          <p>Vendes productos Y servicios</p>
        </button>
      </div>
    </div>

    <!-- Step 2: Upload -->
    <div v-else-if="step === 'upload'" class="upload-section">
      <button @click="step = 'select'" class="back-link">← Volver</button>
      
      <h2>Sube tu Catálogo</h2>
      <p class="type-badge">
        Tipo: 
        <span v-if="catalogType === 'products'">Productos Físicos</span>
        <span v-else-if="catalogType === 'services'">Servicios</span>
        <span v-else>Ambos</span>
      </p>

      <div class="template-download">
        <button @click="downloadTemplate" class="btn-outline">
          📥 Descargar Plantilla
        </button>
      </div>

      <div 
        class="upload-area"
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent
      >
        <input 
          type="file"
          accept=".csv,.json"
          @change="handleFileSelect"
          class="file-input"
        />
        
        <div v-if="!file" class="placeholder">
          <div class="upload-icon">📁</div>
          <p>Arrastra tu archivo aquí o haz click para seleccionar</p>
          <span>CSV o JSON (máx 5MB)</span>
        </div>
        
        <div v-else class="file-preview">
          <div class="file-icon">✅</div>
          <p class="file-name">{{ file.name }}</p>
          <p class="file-size">{{ (file.size / 1024).toFixed(2) }} KB</p>
        </div>
      </div>

      <p v-if="error" class="error">{{ error }}</p>

      <button 
        @click="uploadFile" 
        class="btn-primary"
        :disabled="!file || uploading"
      >
        {{ uploading ? 'Procesando...' : 'Subir Catálogo' }}
      </button>
    </div>

    <!-- Step 3: Viewing -->
    <div v-else-if="step === 'viewing'" class="catalog-viewing">
      <div class="status-card">
        <div class="status-info">
          <span class="status-label">Estado:</span>
          <span class="status-value" :class="catalog.status">{{ catalog.status }}</span>
        </div>
        <div class="status-info">
          <span class="status-label">Tipo:</span>
          <span class="status-value">
            {{ catalog.type === 'products' ? 'Productos' : catalog.type === 'services' ? 'Servicios' : 'Ambos' }}
          </span>
        </div>
        <div class="status-info">
          <span class="status-label">Items:</span>
          <span class="status-value">{{ catalog.total_items }}</span>
        </div>
        <div class="status-info">
          <span class="status-label">Última actualización:</span>
          <span class="status-value">{{ new Date(catalog.last_sync).toLocaleDateString() }}</span>
        </div>
      </div>

      <div v-if="error" class="error-banner">{{ error }}</div>

      <div class="items-section">
        <h3>Productos/Servicios</h3>
        
        <div v-if="items.length === 0" class="empty-state">
          <p>No hay items en el catálogo</p>
        </div>

        <div v-else class="items-grid">
          <div v-for="item in items" :key="item.id" class="item-card">
            <h4>{{ item.name }}</h4>
            <p v-if="item.description">{{ item.description }}</p>
            <div class="item-details">
              <span v-if="item.price" class="price">${{ item.price }}</span>
              <span v-if="item.quantity !== null" class="stock">Stock: {{ item.quantity }}</span>
              <span v-if="item.duration_minutes" class="duration">{{ item.duration_minutes }} min</span>
            </div>
          </div>
        </div>
      </div>

      <div class="actions">
        <button @click="deleteCatalog" class="btn-danger">Eliminar Catálogo</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.catalog-view {
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 1.5rem;
  color: var(--color-dark);
}

.type-selector {
  text-align: center;
}

.type-selector h2 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: var(--color-text-muted);
  margin-bottom: 2rem;
}

.options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  max-width: 800px;
  margin: 0 auto;
}

.option-card {
  background: var(--color-white);
  border: 2px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.option-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.option-card .icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.option-card h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.option-card p {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.upload-section {
  max-width: 600px;
  margin: 0 auto;
}

.back-link {
  background: none;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.upload-section h2 {
  margin-bottom: 0.5rem;
}

.type-badge {
  background: var(--color-surface);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  display: inline-block;
  margin-bottom: 1.5rem;
}

.template-download {
  margin-bottom: 1.5rem;
}

.upload-area {
  border: 2px dashed var(--color-border);
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  position: relative;
  margin-bottom: 1.5rem;
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.placeholder .upload-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.placeholder p {
  color: var(--color-text-muted);
}

.placeholder span {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.file-preview {
  background: var(--color-surface);
  padding: 1.5rem;
  border-radius: 8px;
}

.file-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.file-name {
  font-weight: 500;
}

.file-size {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.btn-primary {
  width: 100%;
  padding: 0.75rem;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
}

.btn-outline {
  padding: 0.5rem 1rem;
  background: transparent;
  border: 1px solid var(--color-primary);
  color: var(--color-primary);
  border-radius: 6px;
  cursor: pointer;
}

.btn-danger {
  padding: 0.5rem 1rem;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.status-card {
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.status-info {
  display: flex;
  flex-direction: column;
}

.status-label {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  text-transform: uppercase;
}

.status-value {
  font-weight: 500;
}

.status-value.active {
  color: var(--color-success);
}

.status-value.processing {
  color: #f59e0b;
}

.status-value.error {
  color: #dc2626;
}

.items-section h3 {
  margin-bottom: 1rem;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.item-card {
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 1rem;
}

.item-card h4 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.item-card p {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 0.5rem;
}

.item-details {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
}

.price {
  color: var(--color-primary);
  font-weight: 500;
}

.stock, .duration {
  color: var(--color-text-muted);
}

.actions {
  margin-top: 2rem;
  text-align: center;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
}
</style>