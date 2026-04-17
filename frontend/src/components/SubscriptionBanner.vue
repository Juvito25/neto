<template>
  <transition name="slide-down">
    <div v-if="showBanner" class="subscription-banner" :class="bannerClass">
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const subscriptionEndsAt = ref(null)
const subscriptionStatus = ref(null)
const dismissedToday = ref(false)
const showBanner = ref(true)

const fetchBillingStatus = async () => {
  try {
    const { data } = await axios.get('/billing/status')
    subscriptionEndsAt.value = data.tenant.subscription_ends_at
    subscriptionStatus.value = data.tenant.subscription_status

    const dismissedDate = localStorage.getItem('subscription_banner_dismissed')
    const today = new Date().toDateString()
    if (dismissedDate === today) {
      dismissedToday.value = true
    }
  } catch (e) {
    console.error('Error fetching billing status:', e)
  }
}

const daysRemaining = computed(() => {
  if (!subscriptionEndsAt.value) return 0
  const diff = new Date(subscriptionEndsAt.value) - new Date()
  return Math.ceil(diff / 86400000)
})

const checkBannerVisibility = () => {
  // Only show if subscription is active
  if (subscriptionStatus.value !== 'active') {
    showBanner.value = false
    return
  }
  // Show if expired or within 7 days
  if (daysRemaining.value > 7) {
    showBanner.value = false
  } else if (dismissedToday.value) {
    showBanner.value = false
  } else {
    showBanner.value = true
  }
}

const showCta = computed(() => {
  return daysRemaining.value <= 7
})

const bannerClass = computed(() => {
  if (daysRemaining.value <= 0) return 'danger'
  if (daysRemaining.value <= 3) return 'warning'
  return 'info'
})

const bannerIcon = computed(() => {
  if (daysRemaining.value <= 0) return '🔴'
  if (daysRemaining.value <= 3) return '⚠️'
  return '📅'
})

const bannerText = computed(() => {
  if (daysRemaining.value <= 0) {
    return 'Tu suscripción venció. El bot está pausado.'
  }
  if (daysRemaining.value === 1) {
    return 'Tu plan se renueva mañana.'
  }
  if (daysRemaining.value <= 7) {
    return `Tu plan se renueva en ${daysRemaining.value} días.`
  }
  return `Tu suscripción activa`
})

const ctaText = computed(() => {
  if (daysRemaining.value <= 0) return 'Renovar ahora'
  if (daysRemaining.value <= 3) return 'Renovar ahora'
  return 'Ver planes'
})

const dismissable = computed(() => {
  return daysRemaining.value > 0
})

const dismiss = () => {
  dismissedToday.value = true
  showBanner.value = false
  const today = new Date().toDateString()
  localStorage.setItem('subscription_banner_dismissed', today)
}

const goToPlans = () => {
  router.push('/plans')
}

onMounted(async () => {
  await fetchBillingStatus()
  checkBannerVisibility()
})
</script>

<style scoped>
.subscription-banner {
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.subscription-banner.info {
  background: rgba(0, 138, 69, 0.08);
  color: var(--color-success);
}

.subscription-banner.warning {
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
}

.subscription-banner.danger {
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
  background: var(--color-success);
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
