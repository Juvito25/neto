<script setup>
</script>

<template>
  <router-view />
</template>

<style>
@media (prefers-color-scheme: dark) {
  :root {
    color-scheme: light;
  }
  html {
    filter: none !important;
    }
  html:has(#-dark-reader) {
    filter: none !important;
  }
}

/* Disable Dark Reader extension */
html[data-darkreader-slot=""] {
  filter: none !important;
}
html #darkreader {
  display: none !important;
}
html[style*="DarkReader"] {
  filter: none !important;
}

:root {
  /* Brand Colors */
  --color-primary: #0052CC;
  --color-primary-10: #EEF4FF;
  --color-success: #008A45;
  --color-success-10: #EDFBF3;
  --color-dark: #0F172A;
  --color-surface: #F8FAFC;
  --color-white: #FFFFFF;
  --color-border: #E2E8F0;
  --color-text-muted: #64748B;
  --color-danger: #DC2626;
  --color-warning: #D97706;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.1);

  /* Radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-full: 9999px;

  /* Transitions */
  --transition-fast: 120ms ease;
  --transition-normal: 200ms ease;
  --transition-slow: 300ms ease;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  -webkit-tap-highlight-color: transparent;
}

body {
  font-family: 'Geist', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--color-surface);
  color: var(--color-dark);
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color-scheme: light;
}

a {
  color: var(--color-primary);
  text-decoration: none;
  transition: var(--transition-fast);
}

button, input, textarea, select {
  font-family: inherit;
  font-size: inherit;
}

/* Global Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes breathing {
  0% { opacity: 1; }
  50% { opacity: 0.7; }
  100% { opacity: 1; }
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

@keyframes pulse-success {
  0% { box-shadow: 0 0 0 0 rgba(0, 138, 69, 0.4); }
  70% { box-shadow: 0 0 0 6px rgba(0, 138, 69, 0); }
  100% { box-shadow: 0 0 0 0 rgba(0, 138, 69, 0); }
}

/* View Transitions */
.page-enter-active, .page-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
