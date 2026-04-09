<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="logo">NETO</div>
      <nav>
        <router-link to="/">Dashboard</router-link>
        <router-link to="/conversations">Conversaciones</router-link>
        <router-link to="/inventory">Inventario</router-link>
        <router-link to="/catalog">Catálogo</router-link>
        <router-link to="/settings">Configuración</router-link>
      </nav>
    </aside>
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
</script>

<style scoped>
.app-layout { display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #0f172a; color: white; padding: 1.5rem; }
.logo { font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; color: #0052cc; }
.sidebar nav { display: flex; flex-direction: column; gap: 0.5rem; }
.sidebar a { padding: 0.75rem 1rem; border-radius: 4px; color: #94a3b8; text-decoration: none; }
.sidebar a:hover, .sidebar a.router-link-active { background: #1e293b; color: white; }
.main-content { flex: 1; background: #f8fafc; overflow-y: auto; }
</style>
