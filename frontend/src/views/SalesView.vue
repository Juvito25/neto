<template>
  <div class="sales-view-container">
    <header class="view-header">
      <div class="header-main">
        <div class="title-group">
          <h1 class="view-title">Ventas cerradas</h1>
          <span class="total-badge">{{ totalSales }} pedidos</span>
        </div>
        <div class="header-tabs">
          <button 
            v-for="tab in tabs" 
            :key="tab.value" 
            class="tab-btn" 
            :class="{ active: statusFilter === tab.value }"
            @click="setStatusFilter(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>
    </header>

    <div class="table-card">
      <div class="table-viewport">
        <table v-if="sales.length > 0" class="sales-table">
          <thead>
            <tr>
              <th>Contacto</th>
              <th>Descripción del pedido</th>
              <th>Medio de pago</th>
              <th>Monto</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <transition-group name="list" tag="tbody">
            <tr v-for="sale in sales" :key="sale.id" class="sale-row">
              <td>
                <div class="contact-info">
                  <span class="contact-name">{{ sale.contact.name || 'Desconocido' }}</span>
                  <span class="contact-phone">{{ formatPhone(sale.contact.phone) }}</span>
                </div>
              </td>
              <td class="order-desc">
                <p>{{ sale.items_description }}</p>
              </td>
              <td>
                <span class="payment-badge" :class="sale.payment_method">
                  {{ sale.payment_method === 'transfer' ? 'Transferencia' : 'Efectivo' }}
                </span>
              </td>
              <td>
                <span class="amount-text">${{ formatAmount(sale.total_amount) }}</span>
              </td>
              <td>
                <span class="status-pill" :class="sale.status">
                  <span class="pill-dot"></span>
                  {{ statusTranslations[sale.status] }}
                </span>
              </td>
              <td>
                <span class="date-text">{{ formatDate(sale.created_at) }}</span>
              </td>
              <td class="text-right">
                <div v-if="sale.status === 'pending'" class="action-group">
                  <button @click="updateStatus(sale, 'confirmed')" class="btn-icon btn-confirm" title="Confirmar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  <button @click="updateStatus(sale, 'cancelled')" class="btn-icon btn-cancel" title="Cancelar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
                <div v-else class="status-text-muted">
                  {{ sale.status === 'confirmed' ? 'Confirmado' : 'Cancelado' }}
                </div>
              </td>
            </tr>
          </transition-group>
        </table>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="empty-isotype">
            <div class="isotype-bar bar-1"></div>
            <div class="isotype-bar bar-2"></div>
            <div class="isotype-bar bar-3"></div>
          </div>
          <h3>No hay ventas aún</h3>
          <p>El bot registrará automáticamente cada venta que cierre en el chat.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useSalesStore } from '@/stores/sales'

const salesStore = useSalesStore()
const sales = ref([])
const statusFilter = ref('')
const totalSales = ref(0)

const tabs = [
  { label: 'Todas', value: '' },
  { label: 'Pendientes', value: 'pending' },
  { label: 'Confirmadas', value: 'confirmed' },
  { label: 'Canceladas', value: 'cancelled' },
]

const statusTranslations = {
  pending: 'Pendiente',
  confirmed: 'Confirmada',
  cancelled: 'Cancelada',
}

const setStatusFilter = (val) => {
  statusFilter.value = val
  loadSales()
}

const loadSales = async () => {
  try {
    const url = statusFilter.value ? `/sales?status=${statusFilter.value}` : '/sales'
    const { data } = await axios.get(url)
    sales.value = data.data
    totalSales.value = data.total || data.data.length
  } catch (e) {
    console.error('Error loading sales:', e)
  }
}

const updateStatus = async (sale, newStatus) => {
  try {
    const { data } = await axios.patch(`/sales/${sale.id}/status`, { status: newStatus })
    // If we are filtering, we might want to remove the item or update it
    const index = sales.value.findIndex(s => s.id === sale.id)
    if (index !== -1) {
      if (statusFilter.value && statusFilter.value !== newStatus) {
        sales.value.splice(index, 1)
      } else {
        sales.value[index] = { ...sales.value[index], ...data }
      }
      // Refresh global badge count
      salesStore.fetchPendingCount()
    }
  } catch (e) {
    console.error('Error updating status:', e)
  }
}

const formatPhone = (phone) => {
  if (!phone) return ''
  return phone.replace(/(\+?\d{2})(\d{1})(\d{4})(\d{4})/, '$1 $2 $3-$4')
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('es-AR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(date)
}

const formatAmount = (amount) => {
  if (!amount) return '0.00'
  return parseFloat(amount).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(() => {
  loadSales()
})
</script>

<style scoped>
.sales-view-container {
  padding: 32px;
  max-width: 1200px;
  margin: 0 auto;
}

.view-header {
  margin-bottom: 32px;
}

.header-main {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 0;
}

.title-group {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 16px;
}

.view-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-dark);
}

.total-badge {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  padding: 2px 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-muted);
}

.header-tabs {
  display: flex;
  gap: 24px;
}

.tab-btn {
  background: none;
  border: none;
  padding: 16px 4px;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-muted);
  cursor: pointer;
  position: relative;
  transition: all var(--transition-fast);
}

.tab-btn:hover {
  color: var(--color-dark);
}

.tab-btn.active {
  color: var(--color-primary);
  font-weight: 600;
}

.tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--color-primary);
}

/* Table Card */
.table-card {
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.table-viewport {
  overflow-x: auto;
}

.sales-table {
  width: 100%;
  border-collapse: collapse;
}

.sales-table th {
  text-align: left;
  padding: 12px 24px;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.sales-table td {
  padding: 16px 24px;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.sale-row {
  transition: background var(--transition-fast);
}

.sale-row:hover {
  background: var(--color-surface);
}

.contact-name {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 2px;
}

.contact-phone {
  font-size: 12px;
  color: var(--color-text-muted);
}

.order-desc p {
  font-size: 14px;
  color: var(--color-dark);
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.payment-badge {
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.payment-badge.transfer {
  background: #EEF2FF;
  color: #4F46E5;
}

.payment-badge.cash {
  background: #F0FDF4;
  color: #166534;
}

.amount-text {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-dark);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
}

.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-pill.pending { background: #FFFBEB; color: var(--color-warning); }
.status-pill.confirmed { background: var(--color-success-10); color: var(--color-success); }
.status-pill.cancelled { background: var(--color-surface); color: var(--color-text-muted); }

.date-text {
  font-size: 13px;
  color: var(--color-text-muted);
}

.text-right {
  text-align: right;
}

.action-group {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition-fast);
  background: white;
  border: 1px solid var(--color-border);
}

.btn-confirm { color: var(--color-success); }
.btn-confirm:hover { background: var(--color-success-10); border-color: var(--color-success); }

.btn-cancel { color: var(--color-danger); }
.btn-cancel:hover { background: #FEF2F2; border-color: var(--color-danger); }

.status-text-muted {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-muted);
}

/* Empty State */
.empty-state {
  padding: 80px 32px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-isotype {
  width: 64px;
  height: 48px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 24px;
}

.isotype-bar {
  height: 10px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 2px 8px 8px 2px;
}

.bar-1 { width: 100%; border-color: var(--color-primary-10); }
.bar-2 { width: 80%; border-color: var(--color-primary-10); }
.bar-3 { width: 60%; border-color: var(--color-primary-10); }

.empty-state h3 {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 8px;
}

.empty-state p {
  color: var(--color-text-muted);
  max-width: 280px;
}

/* List Transitions */
.list-enter-active, .list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from, .list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
