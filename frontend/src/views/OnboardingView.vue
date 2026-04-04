<template>
  <div class="onboarding-view">
    <div class="progress-bar">
      <div class="step" :class="{ active: step >= 1, completed: step > 1 }">1</div>
      <div class="line" :class="{ active: step > 1 }"></div>
      <div class="step" :class="{ active: step >= 2, completed: step > 2 }">2</div>
      <div class="line" :class="{ active: step > 2 }"></div>
      <div class="step" :class="{ active: step >= 3 }">3</div>
    </div>
    
    <div v-if="step === 1" class="step-content">
      <h2>Tu Negocio</h2>
      <textarea v-model="form.description" placeholder="Descripción de tu negocio"></textarea>
      <button @click="nextStep">Continuar</button>
    </div>
    
    <div v-else-if="step === 2" class="step-content">
      <h2>Tus Productos</h2>
      <p>Podés cargar productos después</p>
      <button @click="nextStep">Omitir</button>
    </div>
    
    <div v-else class="step-content">
      <h2>Conectar WhatsApp</h2>
      <p>Escaneá el código QR con tu WhatsApp</p>
      <button @click="connectWhatsApp">Conectar</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const step = ref(1)
const form = ref({
  description: '',
  business_hours: {},
  faqs: [],
})

const nextStep = () => {
  step.value++
}

const connectWhatsApp = () => {
  // TODO: Connect to WhatsApp
}
</script>

<style scoped>
.onboarding-view {
  min-height: 100vh;
  padding: 2rem;
  background: var(--color-surface);
}

.progress-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 3rem;
}

.step {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: var(--color-text-muted);
}

.step.active {
  background: var(--color-primary);
  color: white;
}

.step.completed {
  background: var(--color-success);
  color: white;
}

.line {
  width: 60px;
  height: 2px;
  background: var(--color-border);
}

.line.active {
  background: var(--color-success);
}

.step-content {
  max-width: 500px;
  margin: 0 auto;
  background: var(--color-white);
  padding: 2rem;
  border-radius: 8px;
  border: 1px solid var(--color-border);
}

h2 {
  margin-bottom: 1.5rem;
}

textarea {
  width: 100%;
  min-height: 120px;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  margin-bottom: 1.5rem;
  font-family: inherit;
}

button {
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

button:hover {
  opacity: 0.9;
}
</style>
