# Implementación NETO - SaaS Chatbot WhatsApp

## Resumen de la implementación

Se completaron las siguientes funcionalidades para el desarrollo de NETO:

---

## 1. LÍMITES POR PLAN

### Base de datos

**Migración: `2026_04_09_000001_create_plans_table.php`**
- Tabla `plans` con campos:
  - `id` (UUID)
  - `name` (starter, pro, enterprise)
  - `display_name`
  - `catalog_items_limit` (starter=500, pro=2000, enterprise=10000)
  - `messages_limit` (starter=1000, pro=5000, enterprise=50000)
  - `price_cents` (starter=0, pro=2900, enterprise=9900)
  - `currency`, `trial_days`, `features` (JSON), `is_active`

**Migración: `2026_04_09_000002_add_plan_id_and_onboarding_to_tenants_table.php`**
- Agrega `plan_id` como foreign key a `plans`
- Agrega `onboarding_step` (string, default 'business')
- Agrega `onboarding_completed` (boolean, default false)
- Elimina columnas obsoletas `plan` y `messages_limit`

### Modelo Plan

**Archivo: `backend/app/Models/Plan.php`**
- Relación `hasMany` con Tenant
- Métodos: `isFree()`, `getFeaturesArray()`

### Modelo Tenant actualizado

**Archivo: `backend/app/Models/Tenant.php`**
- Nuevo campo `plan_id` en lugar de `plan`
- Relación `belongsTo(Plan::class)`
- Métodos actualizados:
  - `isActive()` - verifica plan o trial
  - `hasReachedLimit()` - usa límite del plan
  - `getCatalogItemsLimit()` - retorna límite del plan
  - `getMessagesLimit()` - retorna límite de mensajes

### Validación en CatalogController

**Archivo: `backend/app/Http/Controllers/Api/CatalogController.php`**
- Método `upload()` validado contra límite del plan
- Nuevo método `countFileItems()` para conteo preliminar
- Retorna error 422 si excede el límite

### Seeder

**Archivo: `backend/database/seeders/PlanSeeder.php`**
- Crea los 3 planes por defecto
- Asigna plan 'pro' a tenants existentes

---

## 2. WIZARD DE CONFIGURACIÓN (5 pasos)

### Endpoints API

**Archivo: `backend/routes/api.php`**
```php
GET  /api/plans                    - Lista planes disponibles
GET  /api/tenant/onboarding        - Estado del onboarding
PUT  /api/tenant/onboarding        - Actualiza paso del onboarding
```

### TenantController actualizado

**Archivo: `backend/app/Http/Controllers/Api/TenantController.php`**
- `plans()` - Retorna lista de planes activos
- `onboardingStatus()` - Retorna estado de los 5 pasos
- `updateOnboarding()` - Actualiza datos y paso actual

### Frontend - OnboardingWizard.vue

**Archivo: `frontend/src/views/OnboardingView.vue`**

**5 pasos implementados:**

1. **Información del negocio**
   - Rubro
   - Descripción
   - Horarios de atención (Lunes-Viernes, Sábados, Domingos)
   - FAQs (preguntas frecuentes dinámicas)

2. **Conectar WhatsApp**
   - Muestra código QR
   - Polling automático cada 5s para verificar estado
   - Indicador de estado (Desconectado/Conectando/Conectado)

3. **Subir catálogo**
   - Descarga de plantillas CSV (productos, servicios, ambos)
   - Drag & drop de archivos
   - Upload con barra de progreso
   - Validación de tamaño (5MB máx)

4. **Personalizar chatbot**
   - Instrucciones del chatbot (prompt personalizado)
   - Mensaje de saludo opcional

5. **Revisar y activar**
   - Resumen de todos los pasos
   - Checkbox de términos y condiciones
   - Botón de activación

### Router actualizado

**Archivo: `frontend/src/router/index.js`**
- Ruta `/onboarding` configurada
- Guard global que redirige al wizard si onboarding incompleto
- Verificación automática del estado al cargar rutas protegidas

---

## 3. AUTH CONTROLLER ACTUALIZADO

**Archivo: `backend/app/Http/Controllers/Api/AuthController.php`**
- Registro asigna plan 'starter' por defecto
- Asigna `onboarding_step = 'business'`
- Asigna `onboarding_completed = false`
- Carga relación `plan` en respuesta

---

## 4. MIGRACIONES

### Ejecutar migraciones:
```bash
cd backend
php artisan migrate
```

### Seed opcional (asigna planes a tenants existentes):
```bash
php artisan db:seed --class=PlanSeeder
```

---

## 5. TESTING

### Tests manuales recomendados:

1. **Límite de plan Starter (500 items):**
   - Subir CSV con 600 items → Debe retornar error 422
   - Mensaje: "El archivo excede el límite de tu plan"

2. **Wizard bloquea avanzar:**
   - Paso 1: Completar campos obligatorios → Continuar
   - Paso 2: WhatsApp debe estar "connected" para avanzar
   - Paso 3: Subir archivo válido para avanzar
   - Paso 4: Completar prompt obligatorio
   - Paso 5: Aceptar términos para activar

3. **Redirección automática:**
   - Usuario nuevo se registra → Redirigido a /onboarding
   - Usuario con onboarding incompleto → Redirigido a /onboarding
   - Usuario con onboarding completo → Accede a /dashboard

---

## 6. ARCHIVOS MODIFICADOS/CREADOS

### Backend:
- `app/Models/Plan.php` (nuevo)
- `app/Models/Tenant.php` (modificado)
- `app/Http/Controllers/Api/TenantController.php` (modificado)
- `app/Http/Controllers/Api/CatalogController.php` (modificado)
- `app/Http/Controllers/Api/AuthController.php` (modificado)
- `database/migrations/2026_04_09_000001_create_plans_table.php` (nuevo)
- `database/migrations/2026_04_09_000002_add_plan_id_and_onboarding_to_tenants_table.php` (nuevo)
- `database/seeders/PlanSeeder.php` (nuevo)
- `routes/api.php` (modificado)

### Frontend:
- `src/views/OnboardingView.vue` (reemplazado completamente)
- `src/router/index.js` (modificado)

---

## 7. PRÓXIMOS PASOS SUGERIDOS

1. Ejecutar migraciones en la base de datos
2. Testear flujo completo de registro → onboarding → dashboard
3. Implementar tests automatizados (PHPUnit/Pest)
4. Agregar validación de límites en tiempo real (contador de items)
5. Implementar upgrade de plan en SettingsView
