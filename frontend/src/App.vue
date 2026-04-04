<script setup>
</script>

<template>
  <router-view />
</template>

<style>
:root {
  --color-primary: #0052CC;
  --color-success: #008A45;
  --color-dark: #0F172A;
  --color-surface: #F8FAFC;
  --color-white: #FFFFFF;
  --color-border: #E2E8F0;
  --color-text-muted: #64748B;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--color-surface);
  color: var(--color-dark);
  line-height: 1.5;
}

a {
  color: var(--color-primary);
  text-decoration: none;
}

button {
  font-family: inherit;
}
</style>
