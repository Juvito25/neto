<template>
  <div class="sales-view">
    <header class="header">
      <div>
        <h1>Ventas</h1>
        <p class="header-subtitle">Gestioná tus pedidos cerrados por el bot</p>
      </div>
      <div class="filters">
        <select v-model="statusFilter" @change="loadSales">
          <option value="">Todos los estados</option>
          <option value="pending">Pendientes</option>
          <option value="confirmed">Confirmadas</option>
          <option value="cancelled">Canceladas</option>
        </select>
      </div>
    </header>

    <div class="table-container">
      <table v-if="sales.length > 0" class="sales-table">
        <thead>
          <tr>
            <th>Contacto</th>
            <th>Pedido</th>
            <th>Medio de Pago</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sale in sales" :key="sale.id">
            <td>
              <strong>{{ sale.contact.name || sale.contact.phone }}</strong>
              <div class="subtitle">{{ sale.contact.phone }}</div>
            </td>
            <td>{{ sale.items_description }}</td>
            <td>
              <span class="payment-badge" :class="'method-' + sale.payment_method">
                {{ sale.payment_method === 'transfer' ? 'Transferencia' : 'Efectivo/Local' }}
              </span>
            </td>
            <td>{{ formatDate(sale.created_at) }}</td>
            <td>
              <span class="status-badge" :class="sale.status">
                {{ statusTranslations[sale.status] }}
              </span>
            </td>
            <td class="actions">
              <button 
                v-if="sale.status === 'pending'"
                @click="updateStatus(sale, 'confirmed')"
                class="btn-action confirm"
                title="Confirmar Venta"
              >✅</button>
              <button 
                v-if="sale.status === 'pending'"
                @click="updateStatus(sale, 'cancelled')"
                class="btn-action cancel"
                title="Cancelar Venta"
              >❌</button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-else class="empty-state">
        <div class="empty-icon">🛒</div>
        <p>No se encontraron ventas</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const sales = ref([])
const statusFilter = ref('')

const statusTranslations = {
  pending: 'Pendiente',
  confirmed: 'Confirmada',
  cancelled: 'Cancelada',
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('es-AR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(date)
}

const loadSales = async () => {
  try {
    const url = statusFilter.value ? `/sales?status=${statusFilter.value}` : '/sales'
    const { data } = await axios.get(url)
    sales.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const updateStatus = async (sale, newStatus) => {
  try {
    const { data } = await axios.patch(`/sales/${sale.id}/status`, { status: newStatus })
    const index = sales.value.findIndex(s => s.id === sale.id)
    if (index !== -1) {
      sales.value[index] = data
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadSales()
})
</script>

<style scoped>
.sales-view { padding: 2rem; max-width: 1200px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.header h1 { font-size: 1.75rem; font-weight: 600; color: var(--color-dark); margin-bottom: 0.25rem; }
.header-subtitle { color: var(--color-text-muted); font-size: 0.9375rem; }
.filters select { padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid var(--color-border); outline: none; }
.table-container { background: white; border-radius: 12px; border: 1px solid var(--color-border); overflow: hidden; }
.sales-table { width: 100%; border-collapse: collapse; text-align: left; }
.sales-table th { background: #f8fafc; padding: 1rem; color: #64748b; font-weight: 500; font-size: 0.875rem; border-bottom: 1px solid var(--color-border); }
.sales-table td { padding: 1rem; border-bottom: 1px solid var(--color-border); vertical-align: middle; }
.subtitle { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }

.status-badge { padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500; display: inline-block; }
.status-badge.pending { background: #fef08a; color: #854d0e; }
.status-badge.confirmed { background: #dcfce7; color: #166534; }
.status-badge.cancelled { background: #f1f5f9; color: #475569; }

.payment-badge { padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; background: #e0e7ff; color: #3730a3; }

.actions { display: flex; gap: 0.5rem; }
.btn-action { background: none; border: none; font-size: 1.25rem; cursor: pointer; transition: transform 0.1s; }
.btn-action:hover { transform: scale(1.1); }

.empty-state { text-align: center; padding: 3rem; background: white; }
.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state p { color: var(--color-text-muted); font-size: 1rem; }
</style>
