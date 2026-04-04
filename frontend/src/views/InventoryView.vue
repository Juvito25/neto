<template>
  <div class="inventory-view">
    <div class="header">
      <h1>Inventario</h1>
      <div class="actions">
        <button @click="showImportModal = true" class="btn btn-secondary">Importar CSV</button>
        <button @click="showProductModal = true" class="btn btn-primary">Nuevo Producto</button>
      </div>
    </div>

    <table class="products-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in products" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.description?.substring(0, 50) }}...</td>
          <td>${{ product.price }}</td>
          <td>{{ product.stock ?? 'Sin stock' }}</td>
          <td>
            <span class="badge" :class="product.active ? 'active' : 'inactive'">
              {{ product.active ? 'Activo' : 'Inactivo' }}
            </span>
          </td>
          <td>
            <button @click="editProduct(product)" class="btn btn-sm">Editar</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="products.length === 0" class="empty-state">
      No hay productos cargados
    </div>

    <div v-if="showProductModal" class="modal">
      <div class="modal-content">
        <h2>{{ editingProduct ? 'Editar' : 'Nuevo' }} Producto</h2>
        <form @submit.prevent="saveProduct">
          <input v-model="form.name" placeholder="Nombre" required />
          <textarea v-model="form.description" placeholder="Descripción"></textarea>
          <input v-model.number="form.price" type="number" step="0.01" placeholder="Precio" required />
          <input v-model.number="form.stock" type="number" placeholder="Stock" />
          <label>
            <input v-model="form.active" type="checkbox" />
            Activo
          </label>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const products = ref([])
const showProductModal = ref(false)
const showImportModal = ref(false)
const editingProduct = ref(null)
const form = ref({ name: '', description: '', price: 0, stock: null, active: true })

const loadProducts = async () => {
  try {
    const { data } = await axios.get('/api/products')
    products.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const editProduct = (product) => {
  editingProduct.value = product
  form.value = { ...product }
  showProductModal.value = true
}

const saveProduct = async () => {
  try {
    if (editingProduct.value) {
      await axios.put(`/api/products/${editingProduct.value.id}`, form.value)
    } else {
      await axios.post('/api/products', form.value)
    }
    closeModal()
    loadProducts()
  } catch (e) {
    console.error(e)
  }
}

const closeModal = () => {
  showProductModal.value = false
  editingProduct.value = null
  form.value = { name: '', description: '', price: 0, stock: null, active: true }
}

onMounted(loadProducts)
</script>

<style scoped>
.inventory-view { padding: 2rem; }
.header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
.actions { display: flex; gap: 1rem; }
.btn { padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; border: none; }
.btn-primary { background: #0052cc; color: white; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
.products-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
.products-table th, .products-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.products-table th { background: #f8fafc; font-weight: 500; }
.badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
.badge.active { background: #dcfce7; color: #008a45; }
.badge.inactive { background: #fee2e2; color: #dc2626; }
.modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 2rem; border-radius: 8px; width: 100%; max-width: 500px; }
.modal-content form { display: flex; flex-direction: column; gap: 1rem; }
.modal-content input, .modal-content textarea { padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem; }
.empty-state { text-align: center; padding: 3rem; color: #64748b; }
</style>
