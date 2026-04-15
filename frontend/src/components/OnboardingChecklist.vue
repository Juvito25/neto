<template>
  <div class="onboarding-checklist" :class="{ collapsed: isCollapsed }">
    <div class="checklist-header" @click="toggle">
      <div class="checklist-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span>Configuración inicial</span>
        <span class="checklist-progress">{{ completedCount }}/4</span>
      </div>
      <button class="collapse-btn" :class="{ rotated: !isCollapsed }">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
    </div>

    <transition name="expand">
      <div v-show="!isCollapsed" class="checklist-items">
        <div 
          v-for="(step, index) in steps" 
          :key="step.id" 
          class="checklist-item"
          :class="{ completed: step.completed }"
        >
          <div class="step-icon" :class="{ done: step.completed }">
            <svg v-if="step.completed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span v-else class="step-number">{{ index + 1 }}</span>
          </div>
          <div class="step-content">
            <p class="step-title">{{ step.title }}</p>
            <p class="step-desc">{{ step.description }}</p>
          </div>
          <button @click="goToStep(step)" class="step-cta">
            {{ step.completed ? 'Editar' : step.cta }}
          </button>
        </div>

        <div v-if="allCompleted" class="checklist-complete">
          <div class="confetti">
            <span>🎉</span>
            <span>✅</span>
            <span>🎊</span>
          </div>
          <p>¡Todo listo! Tu bot está trabajando</p>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const emit = defineEmits(['complete'])

const router = useRouter()

const tenant = ref(null)
const isCollapsed = ref(false)
const catalogItemsCount = ref(0)

const steps = computed(() => [
  {
    id: 'business',
    title: 'Configurá tu negocio',
    description: 'Agregá el nombre y descripción de tu negocio',
    completed: !!(tenant.value?.business_name && tenant.value?.description),
    cta: 'Configurar',
    route: '/settings',
    tab: 'negocio'
  },
  {
    id: 'catalog',
    title: 'Cargá tu catálogo',
    description: 'El bot usa tu catálogo para responder preguntas de productos y precios',
    completed: catalogItemsCount.value > 0,
    cta: 'Cargar catálogo',
    route: '/catalog'
  },
  {
    id: 'payments',
    title: 'Configurá cómo cobrás',
    description: 'Decile al bot cómo tus clientes pueden pagarte',
    completed: !!(tenant.value?.payment_transfer_enabled || tenant.value?.payment_cash_enabled),
    cta: 'Configurar pagos',
    route: '/settings',
    tab: 'pagos'
  },
  {
    id: 'whatsapp',
    title: 'Conectá WhatsApp',
    description: 'Escaneá el QR con tu teléfono para activar el bot',
    completed: tenant.value?.whatsapp_status === 'connected',
    cta: 'Conectar WhatsApp',
    route: '/settings',
    tab: 'whatsapp'
  }
])

const completedCount = computed(() => steps.value.filter(s => s.completed).length)
const allCompleted = computed(() => completedCount.value === 4)

const fetchData = async () => {
  try {
    const { data } = await axios.get('/tenant')
    tenant.value = data
    
    const { data: catalogData } = await axios.get('/catalog')
    catalogItemsCount.value = catalogData.total_items || 0

    if (allCompleted.value && !tenant.value.onboarding_completed) {
      await markOnboardingComplete()
    }
  } catch (e) {
    console.error('Error fetching onboarding data:', e)
  }
}

const markOnboardingComplete = async () => {
  try {
    await axios.patch('/tenant', { onboarding_completed: true })
    emit('complete')
  } catch (e) {
    console.error('Error marking onboarding complete:', e)
  }
}

const goToStep = (step) => {
  if (step.route === '/settings' && step.tab) {
    router.push({ path: step.route, query: { tab: step.tab } })
  } else {
    router.push(step.route)
  }
}

const toggle = () => {
  isCollapsed.value = !isCollapsed.value
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.onboarding-checklist {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  margin-bottom: 24px;
  overflow: hidden;
}

.checklist-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  background: #f8fafc;
}

.checklist-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: var(--color-dark);
}

.checklist-progress {
  background: var(--color-primary);
  color: white;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 600;
}

.collapse-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-muted);
  transition: transform 200ms ease;
}

.collapse-btn.rotated {
  transform: rotate(180deg);
}

.checklist-items {
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checklist-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  background: var(--color-surface);
}

.step-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.step-icon.done {
  background: var(--color-success);
  color: white;
}

.step-content {
  flex: 1;
}

.step-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 2px;
}

.step-desc {
  font-size: 12px;
  color: var(--color-text-muted);
}

.step-cta {
  font-size: 12px;
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: white;
  color: var(--color-primary);
  cursor: pointer;
  font-weight: 500;
}

.step-cta:hover {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.checklist-complete {
  text-align: center;
  padding: 20px;
}

.confetti {
  font-size: 24px;
  margin-bottom: 8px;
}

.confetti span {
  margin: 0 4px;
  animation: bounce 0.5s ease infinite;
}

.confetti span:nth-child(2) { animation-delay: 0.1s; }
.confetti span:nth-child(3) { animation-delay: 0.2s; }

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.checklist-complete p {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-success);
}

.expand-enter-active, .expand-leave-active {
  transition: all 200ms ease;
  overflow: hidden;
}
.expand-enter-from, .expand-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}
.expand-enter-to, .expand-leave-from {
  max-height: 500px;
}
</style>