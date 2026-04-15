<template>
  <div class="welcome-overlay" v-if="show">
    <div class="welcome-modal">
      <div class="welcome-icon">
        <div class="bar bar-1"></div>
        <div class="bar bar-2"></div>
        <div class="bar bar-3"></div>
      </div>
      
      <h1 class="welcome-title">
        ¡Bienvenido a NETO, {{ businessName }}! 👋
      </h1>
      
      <p class="welcome-subtitle">
        Tu bot de WhatsApp está listo. En menos de 5 minutos vas a tener tu primer mensaje respondido automáticamente.
      </p>
      
      <button @click="startSetup" class="btn-primary">
        Empezar configuración →
      </button>
      
      <button @click="skipSetup" class="btn-secondary">
        Ir al dashboard primero
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const tenant = ref(null)
const show = ref(false)

const businessName = computed(() => {
  return tenant.value?.business_name || 'Negocio'
})

const checkOnboarding = async () => {
  try {
    const { data } = await axios.get('/tenant')
    tenant.value = data
    
    if (data.onboarding_completed === false) {
      show.value = true
    }
  } catch (e) {
    console.error('Error checking onboarding:', e)
  }
}

const startSetup = () => {
  show.value = false
  router.push('/settings')
}

const skipSetup = () => {
  show.value = false
  router.push('/')
}

onMounted(() => {
  checkOnboarding()
})
</script>

<style scoped>
.welcome-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.welcome-modal {
  background: white;
  border-radius: 16px;
  padding: 40px;
  max-width: 440px;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.welcome-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 24px;
  display: flex;
  align-items: flex-end;
  gap: 6px;
  justify-content: center;
}

.bar {
  width: 16px;
  background: var(--color-primary);
  border-radius: 4px;
  animation: grow 0.6s ease-out forwards;
  transform-origin: bottom;
}

.bar-1 { height: 32px; animation-delay: 0s; }
.bar-2 { height: 48px; animation-delay: 0.1s; }
.bar-3 { height: 24px; animation-delay: 0.2s; }

@keyframes grow {
  from {
    transform: scaleY(0);
    opacity: 0;
  }
  to {
    transform: scaleY(1);
    opacity: 1;
  }
}

.welcome-title {
  font-size: 24px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 12px;
  line-height: 1.3;
}

.welcome-subtitle {
  font-size: 15px;
  color: var(--color-text-muted);
  margin-bottom: 32px;
  line-height: 1.5;
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

.btn-secondary {
  display: block;
  width: 100%;
  padding: 12px;
  background: transparent;
  color: var(--color-text-muted);
  border: none;
  font-size: 14px;
  cursor: pointer;
}

.btn-secondary:hover {
  color: var(--color-primary);
}
</style>