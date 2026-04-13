<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const step = ref('loading') // loading, select, upload, viewing
const catalogType = ref(null)
const catalog = ref(null)
const items = ref([])
const loading = ref(true)
const uploading = ref(false)
const error = ref('')
const file = ref(null)
const showNewItemForm = ref(false)
const newItem = ref({ name: '', price: null, description: '' })
const creatingItem = ref(false)
const showDeleteConfirm = ref(false)

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
      items.value = (data.data.data || []).map(i => ({
        ...i,
        editing: false,
        editName: i.name,
        editPrice: i.price
      }))
    }
  } catch (e) {
    console.error('Error loading items:', e)
  }
}

async function saveEdit(item) {
  try {
    const { data } = await axios.put(`/catalog/items/${item.id}`, { 
      name: item.editName, 
      price: item.editPrice 
    })
    if (data.success) {
      item.name = item.editName
      item.price = item.editPrice
      item.editing = false
    }
  } catch (e) {
    alert('Error al guardar el item')
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
  try {
    await axios.delete(`/catalog/${catalog.value.id}`)
    catalog.value = null
    items.value = []
    step.value = 'select'
    showDeleteConfirm.value = false
  } catch (e) {
    error.value = 'Error al eliminar catálogo'
  }
}

async function createItem() {
  if (!newItem.value.name) return
  creatingItem.value = true
  try {
    const { data } = await axios.post('/catalog/items', newItem.value)
    if (data.success) {
      items.value.push({
        ...data.data,
        editing: false,
        editName: data.data.name,
        editPrice: data.data.price
      })
      newItem.value = { name: '', price: null, description: '' }
      showNewItemForm.value = false
      if (catalog.value) catalog.value.total_items++
    }
  } catch (e) {
    alert('Error al crear el producto')
  } finally {
    creatingItem.value = false
  }
}
</script>

<template>
  <div class="catalog-view">
    <header class="view-header">
      <h1 class="view-title">Catálogo</h1>
      <p v-if="step === 'viewing'" class="view-subtitle">Gestioná tus productos y servicios</p>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="shimmer-card"></div>
      <div class="grid-shimmer">
        <div class="shimmer-item" v-for="n in 4" :key="n"></div>
      </div>
    </div>

    <!-- Step: Select Type -->
    <div v-else-if="step === 'select'" class="selection-step">
      <div class="step-card">
        <h2>¿Qué tipo de negocio tenés?</h2>
        <p>Esto ayuda a NETO AI a entender cómo presentar tus productos.</p>
        
        <div class="type-grid">
          <button class="type-card" @click="selectType('products')">
            <div class="card-icon">📦</div>
            <h3>Productos</h3>
            <p>Venta de artículos físicos (ropa, comida, etc.)</p>
          </button>
          
          <button class="type-card" @click="selectType('services')">
            <div class="card-icon">🛠️</div>
            <h3>Servicios</h3>
            <p>Turnos, clases, consultoría o mantenimiento.</p>
          </button>
          
          <button class="type-card" @click="selectType('both')">
            <div class="card-icon">🏪</div>
            <h3>Ambos</h3>
            <p>Si ofrecés tanto productos como servicios.</p>
          </button>
        </div>
      </div>
    </div>

    <!-- Step: Upload -->
    <div v-else-if="step === 'upload'" class="upload-step">
      <div class="step-card">
        <button @click="step = 'select'" class="btn-back">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Volver
        </button>
        
        <h2>Subí tu catálogo</h2>
        <p>Descargá nuestra plantilla y subí el archivo completo.</p>

        <div class="template-box">
          <button @click="downloadTemplate" class="btn btn-outline btn-full">
            Descargar Plantilla ({{ catalogType }})
          </button>
        </div>

        <div 
          class="dropzone"
          :class="{ 'has-file': file }"
          @drop="handleDrop"
          @dragover.prevent
          @dragenter.prevent
        >
          <input type="file" @change="handleFileSelect" class="file-input" />
          <div v-if="!file" class="dropzone-content">
            <div class="drop-icon">📁</div>
            <p>Arrastrá tu archivo acá o hacé click</p>
            <span>Formatos aceptados: CSV, JSON</span>
          </div>
          <div v-else class="file-selected">
            <div class="file-icon">✅</div>
            <p class="file-name">{{ file.name }}</p>
            <button @click.stop="file = null" class="btn-remove">Quitar archivo</button>
          </div>
        </div>

        <div v-if="error" class="error-msg">{{ error }}</div>

        <button 
          @click="uploadFile" 
          class="btn btn-primary btn-full"
          :disabled="!file || uploading"
        >
          {{ uploading ? 'Procesando...' : 'Comenzar importación' }}
        </button>
      </div>
    </div>

    <!-- Step: Viewing -->
    <div v-else-if="step === 'viewing'" class="viewing-step">
      <!-- Info Card -->
      <div class="catalog-summary">
        <div class="summary-item">
          <span class="summary-label">Estado</span>
          <span class="status-badge" :class="catalog.status">{{ catalog.status }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Tipo</span>
          <span class="summary-value">{{ catalog.type }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Items</span>
          <span class="summary-value">{{ catalog.total_items }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Sincronización</span>
          <span class="summary-value">{{ new Date(catalog.last_sync).toLocaleDateString() }}</span>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="items-bar">
        <h2 class="items-title">Lista de productos</h2>
        <button @click="showNewItemForm = !showNewItemForm" class="btn btn-primary btn-sm">
          {{ showNewItemForm ? 'Cancelar' : '+ Agregar Nuevo' }}
        </button>
      </div>

      <!-- New Item Card -->
      <transition name="slide-up">
        <div v-if="showNewItemForm" class="new-item-card">
          <h3>Nuevo Producto</h3>
          <div class="form-grid">
            <div class="form-group">
              <label>Nombre</label>
              <input v-model="newItem.name" placeholder="Ej: Pizza Napolitana" />
            </div>
            <div class="form-group">
              <label>Precio ($)</label>
              <input v-model="newItem.price" type="number" placeholder="0.00" />
            </div>
            <div class="form-group full-width">
              <label>Descripción</label>
              <textarea v-model="newItem.description" placeholder="Ingredientes, talle o detalles..."></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button @click="createItem" class="btn btn-primary" :disabled="creatingItem || !newItem.name">
              {{ creatingItem ? 'Agregando...' : 'Guardar Producto' }}
            </button>
          </div>
        </div>
      </transition>

      <!-- Items Grid -->
      <div v-if="items.length > 0" class="products-grid">
        <div v-for="item in items" :key="item.id" class="product-card">
          <div v-if="!item.editing" class="product-view">
            <div class="product-header">
              <h4 class="product-name">{{ item.name }}</h4>
              <button @click="item.editing = true" class="btn-edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
            </div>
            <p v-if="item.description" class="product-desc">{{ item.description }}</p>
            <div class="product-footer">
              <span class="product-price">${{ parseFloat(item.price).toLocaleString('es-AR') }}</span>
              <span v-if="item.duration_minutes" class="product-meta">{{ item.duration_minutes }} min</span>
              <span v-if="item.quantity !== null" class="product-meta">Stock: {{ item.quantity }}</span>
            </div>
          </div>
          
          <div v-else class="product-edit">
            <div class="form-group">
              <label>Nombre</label>
              <input v-model="item.editName" />
            </div>
            <div class="form-group">
              <label>Precio</label>
              <input type="number" v-model="item.editPrice" />
            </div>
            <div class="edit-actions">
              <button @click="saveEdit(item)" class="btn btn-primary btn-xs">Guardar</button>
              <button @click="item.editing = false" class="btn btn-outline btn-xs">Cancelar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty Grid -->
      <div v-else-if="!showNewItemForm" class="empty-items">
        <div class="empty-icon">📦</div>
        <p>No hay items en tu catálogo</p>
      </div>

      <!-- Delete Section -->
      <div class="delete-zone">
        <button @click="showDeleteConfirm = true" class="btn-delete-catalog">
          Eliminar todo el catálogo
        </button>
      </div>
    </div>

    <!-- Delete Modal -->
    <transition name="fade">
      <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
        <div class="modal-card">
          <h2>¿Eliminar catálogo?</h2>
          <p>Esta acción borrará todos tus productos y NETO AI dejará de conocer tus precios. No se puede deshacer.</p>
          <div class="modal-actions">
            <button @click="showDeleteConfirm = false" class="btn btn-outline">Cancelar</button>
            <button @click="deleteCatalog" class="btn btn-danger">Sí, eliminar catálogo</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.catalog-view {
  padding: 32px;
  max-width: 1000px;
  margin: 0 auto;
}

.view-header {
  margin-bottom: 32px;
}

.view-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 4px;
}

.view-subtitle {
  color: var(--color-text-muted);
  font-size: 15px;
}

/* Steps Cards */
.step-card {
  background: white;
  padding: 40px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  text-align: center;
}

.step-card h2 {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 8px;
}

.step-card p {
  color: var(--color-text-muted);
  margin-bottom: 32px;
}

/* Selection Step */
.type-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.type-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 24px 16px;
  cursor: pointer;
  transition: all var(--transition-normal);
}

.type-card:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-10);
  transform: translateY(-2px);
}

.card-icon {
  font-size: 32px;
  margin-bottom: 16px;
}

.type-card h3 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 8px;
}

.type-card p {
  font-size: 13px;
  margin-bottom: 0;
  line-height: 1.4;
}

/* Upload Step */
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  color: var(--color-primary);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  margin-bottom: 24px;
}

.template-box {
  margin-bottom: 24px;
}

.dropzone {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: 48px 24px;
  position: relative;
  margin-bottom: 24px;
  transition: all var(--transition-fast);
}

.dropzone:hover {
  border-color: var(--color-primary);
  background: var(--color-surface);
}

.dropzone.has-file {
  border-color: var(--color-success);
  background: var(--color-success-10);
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.drop-icon, .file-icon {
  font-size: 40px;
  margin-bottom: 16px;
}

.dropzone-content span {
  font-size: 12px;
  color: var(--color-text-muted);
}

.btn-remove {
  background: none;
  border: none;
  color: var(--color-danger);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

/* Viewing Step: Summary */
.catalog-summary {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 20px 24px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 40px;
}

.summary-item {
  display: flex;
  flex-direction: column;
}

.summary-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-dark);
}

.status-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  display: inline-block;
  width: fit-content;
}

.status-badge.active { background: var(--color-success-10); color: var(--color-success); }
.status-badge.processing { background: #FFF7ED; color: #EA580C; }

/* Items Bar */
.items-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.items-title {
  font-size: 18px;
  font-weight: 600;
}

/* New Item Card */
.new-item-card {
  background: white;
  border: 1px solid var(--color-primary);
  border-radius: var(--radius-md);
  padding: 24px;
  margin-bottom: 32px;
  box-shadow: 0 4px 12px rgba(0, 82, 204, 0.08);
}

.new-item-card h3 {
  font-size: 16px;
  margin-bottom: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.full-width { grid-column: span 2; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-dark);
}

.form-group input, .form-group textarea {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: 14px;
}

.form-group textarea {
  min-height: 80px;
  resize: vertical;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}

/* Products Grid */
.products-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 768px) {
  .products-grid { grid-template-columns: 1fr; }
}

.product-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 16px;
  transition: all var(--transition-fast);
}

.product-card:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.product-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-dark);
}

.btn-edit {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}

.btn-edit:hover { background: var(--color-surface); color: var(--color-primary); }

.product-desc {
  font-size: 13px;
  color: var(--color-text-muted);
  margin-bottom: 12px;
  line-height: 1.4;
}

.product-footer {
  display: flex;
  align-items: center;
  gap: 12px;
}

.product-price {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-primary);
}

.product-meta {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  background: var(--color-surface);
  padding: 2px 6px;
  border-radius: 4px;
}

/* Edit State */
.edit-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

/* Danger Zone */
.delete-zone {
  margin-top: 64px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
  text-align: center;
}

.btn-delete-catalog {
  background: none;
  border: 1px solid var(--color-danger);
  color: var(--color-danger);
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-delete-catalog:hover {
  background: #FEF2F2;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-card {
  background: white;
  width: 90%;
  max-width: 400px;
  padding: 32px;
  border-radius: var(--radius-lg);
}

.modal-card h2 {
  font-size: 20px;
  margin-bottom: 12px;
  color: var(--color-danger);
}

.modal-card p {
  font-size: 14px;
  color: var(--color-text-muted);
  margin-bottom: 24px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

/* Buttons Helper */
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

.btn-full { width: 100%; }

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover { opacity: 0.9; transform: scale(0.98); }

.btn-outline {
  border-color: var(--color-border);
  color: var(--color-dark);
}

.btn-danger {
  background: var(--color-danger);
  color: white;
}

.btn-xs { padding: 4px 8px; font-size: 12px; }

/* Transitions */
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(10px); }
.slide-up-leave-to { opacity: 0; transform: translateY(10px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.loading-state {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.shimmer-card {
  height: 80px;
  background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.grid-shimmer {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.shimmer-item {
  height: 120px;
  background: var(--color-surface);
  border-radius: 8px;
}
</style>