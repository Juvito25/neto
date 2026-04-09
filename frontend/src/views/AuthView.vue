<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const isLogin = computed(() => route.path === '/login')
const loading = ref(false)
const error = ref('')

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
    const { data } = await axios.post(endpoint, form.value)

    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))

    router.push('/')
  } catch (err) {
    const errorData = err.response?.data
    error.value = errorData?.message || errorData?.error || 'Error en la solicitud'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <div class="auth-card">
      <h1>NETO</h1>
      <p>Automatiza tu WhatsApp con IA</p>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <p v-if="error" class="error">{{ error }}</p>

        <div v-if="!isLogin" class="form-group">
          <label for="name">Nombre</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            placeholder="Tu nombre"
          />
        </div>

        <div v-if="!isLogin" class="form-group">
          <label for="business_name">Nombre del negocio</label>
          <input
            id="business_name"
            v-model="form.business_name"
            type="text"
            required
            placeholder="Mi Empresa"
          />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="tu@email.com"
          />
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
          />
        </div>

        <div v-if="!isLogin" class="form-group">
          <label for="password_confirmation">Confirmar contraseña</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            placeholder="••••••••"
          />
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ isLogin ? 'Iniciar sesión' : 'Crear cuenta' }}
        </button>

        <p class="switch-link">
          {{ isLogin ? '¿No tienes cuenta?' : '¿Ya tienes cuenta?' }}
          <router-link :to="isLogin ? '/register' : '/login'">
            {{ isLogin ? 'Regístrate' : 'Inicia sesión' }}
          </router-link>
        </p>
      </form>
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

.auth-form {
  text-align: left;
}

.form-group {
  margin-bottom: 1rem;
}

label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: var(--color-dark);
}

input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.btn-primary {
  width: 100%;
  padding: 0.75rem;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  margin-top: 1rem;
}

.btn-primary:hover {
  background: #0047b3;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.switch-link {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.875rem;
}

.error {
  background: #fee2e2;
  color: #dc2626;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}
</style>