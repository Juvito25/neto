<template>
  <div class="welcome-overlay" v-if="show">
    <div class="welcome-modal">
      <div class="welcome-icon">
        <img src="@/assets/branding/neto-iso.svg" alt="NETO" class="iso-logo" />
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
  const alreadyWelcomed = localStorage.getItem('onboarding_welcomed')
  if (alreadyWelcomed) return
  
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

const skipSetup = () => {
  localStorage.setItem('onboarding_welcomed', 'true')
  show.value = false
  router.push('/')
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
  align-items: center;
  justify-content: center;
}

.iso-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
</style>