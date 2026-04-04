# AGENTS.md — Guía para Agentes de IA

Este documento proporciona instrucciones específicas para agentes de IA que trabajen en el proyecto NETO.

## Paleta de Colores

Usar SIEMPRE estos colores en todos los componentes:

```css
--color-primary: #0052CC;    /* Azul principal — CTAs, links */
--color-success: #008A45;     /* Verde — estados activos */
--color-dark: #0F172A;      /* Casi negro — textos principales */
--color-surface: #F8FAFC;    /* Gris muy claro — fondo de página */
--color-white: #FFFFFF;       /* Blanco — fondo de cards */
--color-border: #E2E8F0;     /* Borde sutil */
--color-text-muted: #64748B; /* Texto secundario */
```

**Reglas:**
- Fondo general: `--color-surface`
- Cards: `--color-white` con border `--color-border`
- Botón primario: `--color-primary` fondo, texto blanco
- Botón conectado: `--color-success`
- Sin gradientes. Sin sombras pesadas.

## Tipografía

- **Headings**: Geist (Google Fonts)
- **Body**: Geist o Inter
- Pesos: 400 (body), 500 (labels), 600 (headings)
- Sin serif

## Estilo de Código

### Vue 3

```vue
<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const data = ref(null)

onMounted(async () => {
  const { data: response } = await axios.get('/api/endpoint')
  data.value = response
})
</script>

<template>
  <div class="component-name">
    <!-- Markup here -->
  </div>
</template>

<style scoped>
.component-name {
  /* Use CSS variables */
  background: var(--color-white);
  border: 1px solid var(--color-border);
}
</style>
```

### PHP (Laravel)

- Usar `withoutGlobalScope` cuando se acceda a modelos desde outside del tenant actual
- Prefijos de namespace: `App\Models\`, `App\Http\Controllers\Api\`
- Jobs van en `App\Jobs`
- Actions van en `App\Actions`
- Observers van en `App\Observers`

### Rutas

- API: `routes/api.php`
- Web: `routes/web.php`
- Auth middleware: `auth:sanctum`

## Archivos Críticos

### Backend

- `app/Jobs/ProcessIncomingMessageJob.php`: Procesa mensajes entrantes
- `app/Http/Controllers/Api/WebhookController.php`: Receives WhatsApp webhooks
- `app/Actions/SearchProductAction.php`: Búsqueda vectorial de productos

### Frontend

- `src/router/index.js`: Definición de rutas
- `src/stores/`: Pinia stores (crear si es necesario)
- `src/views/`: Vistas de la aplicación

## Multitenancy

**Regla clave**: Nunca hacer query sin `tenant_id`.

```php
// ✅ Correcto
$products = Product::withoutGlobalScope(TenantScope::class)
    ->where('tenant_id', $tenantId)
    ->get();

// ❌ Incorrecto (usa el scope global)
$products = Product::where('name', 'like', '%test%')->get();
```

## Docker

- Frontend: Puerto 5173
- Backend: Puerto 9000 (PHP-FPM)
- Nginx: Puertos 80, 443
- PostgreSQL: Puerto 5432
- Redis: Puerto 6379
- Evolution API: Puerto 8080

## Testing

```bash
# Laravel
docker-compose exec app php artisan test

# Vue (agregar si es necesario)
docker-compose exec frontend npm run test
```

## Dependencias

### Agregar dependencia PHP

```bash
docker-compose exec app composer require vendor/package
```

### Agregar dependencia Vue

```bash
docker-compose exec frontend npm install package
```
