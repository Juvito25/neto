<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import logoVertical from '@/assets/branding/media__1776087843732.png'

const router = useRouter()
const route = useRoute()

const isLogin = computed(() => route.path === '/login')
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

const form = ref({
  name: '',
  business_name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    const endpoint = isLogin.value ? '/auth/login' : '/auth/register'
    const payload = isLogin.value 
      ? { email: form.value.email, password: form.value.password }
      : form.value

    const { data } = await axios.post(endpoint, payload)

    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))

    router.push('/')
  } catch (err) {
    const errorData = err.response?.data
    if (errorData?.errors) {
      const firstError = Object.values(errorData.errors)[0][0]
      error.value = firstError
    } else {
      error.value = errorData?.message || errorData?.error || 'Error en la solicitud'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <!-- Decorative Blurs -->
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>

    <div class="auth-card">
      <header class="auth-header">
        <div class="logo-group">
          <img :src="logoVertical" alt="NETO" class="auth-logo">
        </div>
        <p class="auth-subtitle">
          {{ isLogin ? 'Bienvenido de vuelta.' : 'Comenzá a automatizar tu negocio.' }}
        </p>
      </header>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <transition name="fade">
          <p v-if="error" class="error-msg">{{ error }}</p>
        </transition>

        <div class="form-scroll-area">
          <transition-group name="list">
            <div v-if="!isLogin" class="form-group" key="name">
              <label for="name">Tu nombre</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                placeholder="Juan Pérez"
              />
            </div>

            <div v-if="!isLogin" class="form-group" key="business">
              <label for="business_name">Nombre del negocio</label>
              <input
                id="business_name"
                v-model="form.business_name"
                type="text"
                required
                placeholder="Panadería Central"
              />
            </div>

            <div class="form-group" key="email">
              <label for="email">Email</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="nombre@ejemplo.com"
              />
            </div>

            <div class="form-group" key="password">
              <label for="password">Contraseña</label>
              <div class="password-input-wrapper">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                />
                <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                  <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div v-if="!isLogin" class="form-group" key="confirm">
              <label for="password_confirmation">Confirmar contraseña</label>
              <div class="password-input-wrapper">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                />
              </div>
            </div>
          </transition-group>
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="!loading">{{ isLogin ? 'Iniciar sesión' : 'Crear Cuenta' }}</span>
          <span v-else class="loader"></span>
        </button>

        <footer class="auth-footer">
          <p>
            {{ isLogin ? '¿No tenés cuenta?' : '¿Ya tenés cuenta?' }}
            <router-link :to="isLogin ? '/register' : '/login'" class="link">
              {{ isLogin ? 'Registrate gratis' : 'Iniciá sesión' }}
            </router-link>
          </p>
        </footer>
      </form>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-surface);
  position: relative;
  overflow: hidden;
  padding: 24px;
}

/* Background Blurs */
.bg-blur {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  opacity: 0.1;
}
.blur-1 { top: -100px; right: -100px; background: var(--color-primary); }
.blur-2 { bottom: -100px; left: -100px; background: var(--color-success); }

.auth-card {
  background: white;
  width: 100%;
  max-width: 440px;
  padding: 48px;
  border-radius: 24px;
  border: 1px solid var(--color-border);
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
  position: relative;
  z-index: 10;
}

.auth-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 12px;
}

.auth-logo {
  height: 80px;
  object-fit: contain;
}

.auth-subtitle {
  font-size: 15px;
  color: var(--color-text-muted);
}

.auth-form {
  display: flex;
  flex-direction: column;
}

.error-msg {
  background: #FEF2F2;
  color: var(--color-danger);
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 24px;
  border: 1px solid rgba(220, 38, 38, 0.1);
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  font-size: 14px;
  transition: all var(--transition-fast);
}

.form-group input:focus {
  outline: none;
  background: white;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px var(--color-primary-10);
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: var(--color-primary);
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 12px;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px var(--color-primary-10);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.loader {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.auth-footer {
  margin-top: 24px;
  text-align: center;
}

.auth-footer p {
  font-size: 14px;
  color: var(--color-text-muted);
}

.link {
  color: var(--color-primary);
  font-weight: 700;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

/* Transitions */
.list-enter-active, .list-leave-active { transition: all 0.3s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateY(-10px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>