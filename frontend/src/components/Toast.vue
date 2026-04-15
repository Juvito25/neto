<template>
  <div class="toast" :class="type" :style="style">
    <span class="toast-icon">
      <svg v-if="type === 'success'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <svg v-else-if="type === 'error'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    </span>
    <span class="toast-message">{{ message }}</span>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  message: { type: String, required: true },
  type: { type: String, default: 'info' },
  duration: { type: Number, default: 3000 }
})

const emit = defineEmits(['close'])

const style = ref({})
const visible = ref(true)

watch(() => props.message, (newVal) => {
  if (newVal) {
    visible.value = true
    setTimeout(() => {
      visible.value = false
      emit('close')
    }, props.duration)
  }
}, { immediate: true })
</script>

<style scoped>
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 9999;
  animation: slideIn 0.3s ease;
}

.toast.success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.toast.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.toast.info {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>