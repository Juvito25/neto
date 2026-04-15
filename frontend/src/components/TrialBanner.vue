<template>
  <transition name="slide-down">
    <div v-if="showBanner" class="trial-banner" :class="bannerClass">
      <div class="banner-content">
        <span class="banner-icon">{{ bannerIcon }}</span>
        <span class="banner-text">{{ bannerText }}</span>
        <button v-if="showCta" @click="goToPlans" class="banner-cta">
          {{ ctaText }}
        </button>
        <button v-if="dismissable" @click="dismiss" class="dismiss-btn" title="Cerrar">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const tenant = ref(null)
const trialEndsAt = ref(null)
const subscriptionStatus = ref(null)
const dismissedToday = ref(false)

const fetchBillingStatus = async () => {
  try {
    const { data } = await axios.get('/billing/status')
    tenant.value = data.tenant
    trialEndsAt.value = data.tenant.trial_ends_at
    subscriptionStatus.value = data.tenant.subscription_status
  } catch (e) {
    console.error('Error fetching billing status:', e)
  }
}

const daysRemaining = computed(() => {
  if (!trialEndsAt.value) return 0
  const diff = new Date(trialEndsAt.value) - new Date()
  return Math.ceil(diff / 86400000)
})

const showBanner = computed(() => {
  if (subscriptionStatus.value === 'active') return false
  if (subscriptionStatus.value === 'expired') return true
  if (subscriptionStatus.value === 'trial' && daysRemaining.value > 0) return true
  if (dismissedToday.value) return false
  return false
})

const bannerClass = computed(() => {
  if (subscriptionStatus.value === 'expired') return 'danger'
  if (daysRemaining.value <= 3) return 'warning'
  return 'info'
})

const bannerIcon = computed(() => {
  if (subscriptionStatus.value === 'expired') return '🔴'
  if (daysRemaining.value <= 3) return '⚠️'
  return '🎁'
})

const bannerText = computed(() => {
  if (subscriptionStatus.value === 'expired') {
    return 'Tu período de prueba venció. El bot está pausado.'
  }
  if (daysRemaining.value <= 0) {
    return 'Tu prueba gratuita venció hoy.'
  }
  if (daysRemaining.value === 1) {
    return 'Tu prueba vence mañana.'
  }
  return `Te quedan ${daysRemaining.value} días de prueba gratuita`
})

const showCta = computed(() => {
  return subscriptionStatus.value === 'trial' || subscriptionStatus.value === 'expired'
})

const ctaText = computed(() => {
  if (subscriptionStatus.value === 'expired') return 'Reactivar por $19/mes'
  if (daysRemaining.value <= 3) return 'Activar ahora'
  return 'Activar plan'
})

const dismissable = computed(() => {
  return subscriptionStatus.value !== 'expired'
})

const dismiss = () => {
  dismissedToday.value = true
  const today = new Date().toDateString()
  localStorage.setItem('trial_banner_dismissed', today)
  showBanner.value = false
}

const goToPlans = () => {
  router.push('/plans')
}

onMounted(async () => {
  const dismissedDate = localStorage.getItem('trial_banner_dismissed')
  if (dismissedDate === new Date().toDateString()) {
    dismissedToday.value = true
  }
  await fetchBillingStatus()
})
</script>

<style scoped>
.trial-banner {
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.trial-banner.info {
  background: rgba(0, 82, 204, 0.08);
  color: var(--color-primary);
}

.trial-banner.warning {
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
}

.trial-banner.danger {
  background: #fef2f2;
  color: #dc2626;
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
}

.banner-icon {
  font-size: 14px;
}

.banner-text {
  flex: 1;
}

.banner-cta {
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 120ms ease;
}

.info .banner-cta {
  background: var(--color-primary);
  color: white;
}

.warning .banner-cta {
  background: #f59e0b;
  color: white;
}

.danger .banner-cta {
  background: #dc2626;
  color: white;
}

.banner-cta:hover {
  opacity: 0.9;
}

.dismiss-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  opacity: 0.6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dismiss-btn:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.05);
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: transform 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  transform: translateY(-100%);
}
</style>