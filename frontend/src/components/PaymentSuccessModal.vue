<template>
  <div v-if="show" class="payment-modal-overlay" @click.self="close">
    <div class="payment-modal">
      <div class="modal-icon success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      
      <h2 class="modal-title">¡Suscripción activada! 🎉</h2>
      
      <p class="modal-text">
        Ya sos parte de NETO. Tu bot está listo para trabajar.
      </p>
      
      <p v-if="showTrialNote" class="trial-note">
        Recordá que tenés {{ trialDaysRemaining }} días de prueba. Si ya pagaste, tu suscripción arranca cuando venza el trial.
      </p>
      
      <button @click="connectWhatsApp" class="btn-primary">
        Conectar WhatsApp ahora
      </button>
      
      <button @click="goToDashboard" class="btn-link">
        Ir al dashboard
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const show = ref(false)
const subscriptionStatus = ref(null)
const trialEndsAt = ref(null)

const trialDaysRemaining = computed(() => {
  if (!trialEndsAt.value) return 0
  const diff = new Date(trialEndsAt.value) - new Date()
  return Math.ceil(diff / 86400000)
})

const showTrialNote = computed(() => {
  return subscriptionStatus.value === 'trial' && trialDaysRemaining.value > 0
})

const fetchStatus = async () => {
  try {
    const { data } = await axios.get('/billing/status')
    subscriptionStatus.value = data.tenant.subscription_status
    trialEndsAt.value = data.tenant.trial_ends_at
  } catch (e) {
    console.error('Error fetching status:', e)
  }
}

const close = () => {
  show.value = false
}

const connectWhatsApp = () => {
  show.value = false
  router.push({ path: '/settings', query: { tab: 'whatsapp' } })
}

const goToDashboard = () => {
  show.value = false
  router.push('/')
}

onMounted(async () => {
  await fetchStatus()
  
  if (route.query.payment === 'success') {
    show.value = true
    router.replace({ path: '/settings', query: {} })
  }
})
</script>

<style scoped>
.payment-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.payment-modal {
  background: white;
  border-radius: 16px;
  padding: 40px;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.success-icon {
  background: rgba(0, 138, 69, 0.1);
  color: var(--color-success);
}

.modal-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-dark);
  margin-bottom: 12px;
}

.modal-text {
  font-size: 15px;
  color: var(--color-text-muted);
  margin-bottom: 16px;
  line-height: 1.5;
}

.trial-note {
  font-size: 13px;
  color: var(--color-primary);
  background: rgba(0, 82, 204, 0.08);
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 24px;
}

.btn-primary {
  display: block;
  width: 100%;
  padding: 14px 24px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 12px;
  transition: all 120ms ease;
}

.btn-primary:hover {
  background: #0047b3;
}

.btn-link {
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: 14px;
  cursor: pointer;
  padding: 8px;
}

.btn-link:hover {
  color: var(--color-primary);
}
</style>