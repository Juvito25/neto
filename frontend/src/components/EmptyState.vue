<template>
  <div class="empty-state">
    <div class="empty-isotype">
      <div class="isotype-bar bar-1"></div>
      <div class="isotype-bar bar-2"></div>
      <div class="isotype-bar bar-3"></div>
    </div>
    <slot />
  </div>
</template>

<script setup>
</script>

<style scoped>
.empty-state {
  padding: 80px 32px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-isotype {
  width: 80px;
  height: 60px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 32px;
}

.isotype-bar {
  height: 12px;
  background: white;
  border: 2px solid var(--color-border);
  border-radius: 12px;
}

.bar-1 { width: 100%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0s; }
.bar-2 { width: 100%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0.2s; }
.bar-3 { width: 100%; border-color: var(--color-primary-10); animation: staggerBar 1.5s infinite 0.4s; }

@keyframes staggerBar {
  0% { transform: scaleX(1); opacity: 0.5; }
  50% { transform: scaleX(0.9); opacity: 1; }
  100% { transform: scaleX(1); opacity: 0.5; }
}
</style>