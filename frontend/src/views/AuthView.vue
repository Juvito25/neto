<template>
  <div class="auth-view">
    <div class="auth-card">
      <h1>NETO</h1>
      <p>Automatiza tu WhatsApp con IA</p>
      <router-view />
    </div>
  </div>
</template>

<style scoped>
.auth-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-surface);
}

.auth-card {
  background: var(--color-white);
  padding: 2rem;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  width: 100%;
  max-width: 400px;
  text-align: center;
}

h1 {
  font-size: 2rem;
  color: var(--color-primary);
  margin-bottom: 0.5rem;
}

p {
  color: var(--color-text-muted);
  margin-bottom: 2rem;
}
</style>
