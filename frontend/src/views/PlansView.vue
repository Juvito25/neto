<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const plans = ref([])
const loading = ref(true)
const selecting = ref(false)
const selectedPlan = ref(null)

onMounted(async () => {
  try {
    const { data } = await axios.get('/plans')
    plans.value = data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})

async function selectPlan(plan) {
  selecting.value = true
  selectedPlan.value = plan.id
  
  try {
    const token = localStorage.getItem('token')
    await axios.put('/tenant', { plan_id: plan.id }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    if (plan.price_cents > 0) {
      const { data } = await axios.post('/billing/subscription', {}, {
        headers: { Authorization: `Bearer ${token}` }
      })
      if (data.init_point) {
        window.location.href = data.init_point
        return
      }
    }

    router.push('/onboarding')
  } catch (e) {
    console.error(e)
    alert('Error al procesar el plan')
  } finally {
    selecting.value = false
  }
}

function formatPrice(cents) {
  return (cents / 100).toFixed(0)
}
</script>

<template>
  <div class="plans-page">
    <div class="plans-container">
      <div class="plans-header">
        <h1>Elegí tu plan</h1>
        <p>Seleccioná el plan que mejor se adapte a tu negocio</p>
      </div>
      
      <div v-if="loading" class="loading">Cargando planes...</div>
      
      <div v-else class="plans-grid">
        <div 
          v-for="(plan, index) in plans" 
          :key="plan.id" 
          class="plan-card"
          :class="{ featured: index === 1, selected: selectedPlan === plan.id }"
        >
          <div class="plan-badge" v-if="index === 1">Más popular</div>
          
          <h2>{{ plan.display_name }}</h2>
          
          <div class="plan-price">
            <span class="currency">$</span>
            <span class="amount">{{ formatPrice(plan.price_cents) }}</span>
            <span class="period">/mes</span>
          </div>
          
          <ul class="plan-features">
            <li>
              <span class="feature-icon">📦</span>
              <span class="feature-text">{{ plan.catalog_items_limit }} productos</span>
            </li>
            <li>
              <span class="feature-icon">💬</span>
              <span class="feature-text">{{ plan.messages_limit }} mensajes/mes</span>
            </li>
            <li v-for="(feature, fIndex) in JSON.parse(plan.features)" :key="fIndex">
              <span class="feature-icon">✓</span>
              <span class="feature-text">{{ feature }}</span>
            </li>
          </ul>
          
          <button 
            @click="selectPlan(plan)" 
            class="plan-button"
            :class="{ featured: index === 1 }"
            :disabled="selecting"
          >
            {{ selecting ? 'Seleccionando...' : 'Elegir plan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plans-page {
  min-height: 100vh;
  background: var(--color-surface);
  padding: 3rem 1rem;
}

.plans-container {
  max-width: 1100px;
  margin: 0 auto;
}

.plans-header {
  text-align: center;
  margin-bottom: 3rem;
}

.plans-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 0.5rem;
}

.plans-header p {
  font-size: 1.125rem;
  color: var(--color-text-muted);
}

.loading {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 900px) {
  .plans-grid {
    grid-template-columns: 1fr;
    max-width: 400px;
    margin: 0 auto;
  }
}

.plan-card {
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 2rem;
  position: relative;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.plan-card:hover {
  transform: translateY(-4px);
  border-color: var(--color-primary);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.plan-card.featured {
  border-color: var(--color-primary);
  border-width: 2px;
  transform: scale(1.05);
  z-index: 1;
}

.plan-card.featured:hover {
  transform: scale(1.05) translateY(-4px);
}

.plan-card.selected {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.15);
}

.plan-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-primary);
  color: white;
  padding: 0.375rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.plan-card h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 1.5rem;
  text-align: center;
}

.plan-price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  margin-bottom: 2rem;
}

.plan-price .currency {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-dark);
}

.plan-price .amount {
  font-size: 3rem;
  font-weight: 700;
  color: var(--color-dark);
  line-height: 1;
}

.plan-price .period {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin-left: 0.25rem;
}

.plan-features {
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
  flex-grow: 1;
}

.plan-features li {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--color-border);
  color: var(--color-text-muted);
}

.plan-features li:last-child {
  border-bottom: none;
}

.feature-icon {
  font-size: 1rem;
  flex-shrink: 0;
}

.feature-text {
  font-size: 0.9375rem;
}

.plan-button {
  width: 100%;
  padding: 1rem;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.plan-button:hover {
  background: #0044b3;
}

.plan-button.featured {
  background: var(--color-primary);
}

.plan-button.featured:hover {
  background: #0044b3;
}

.plan-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
