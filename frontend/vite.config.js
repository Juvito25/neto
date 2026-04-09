import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    allowedHosts: ['frontend', 'localhost', 'nginx'],
    proxy: {
      '/api': {
        target: 'http://app:9000',
        changeOrigin: true,
      },
    },
  },
})