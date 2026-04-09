# PROMPT: Continuar desarrollo NETO - Límites por Plan + Wizard de Configuración

---

## 📋 CONTEXTO DEL PROYECTO

**Proyecto:** NETO - SaaS B2B argentino para conectar chatbot IA a WhatsApp  
**Stack:** Laravel 11 (backend) + Vue.js 3 (frontend) + MySQL  
**Estado actual:** 
- ✅ Autenticación funcionando (Sanctum tokens)
- ✅ Dashboard cargando datos del tenant
- ✅ Catálogo de productos/servicios implementado
- ⏳ Límites por plan + Wizard de configuración

---

## ✅ LO IMPLEMENTADO HASTA AHORA

### 1. Catálogo de Productos/Servicios
- **Backend:**
  - Tablas: `catalogs`, `catalog_items` (migraciones)
  - Modelos: `Catalog.php`, `CatalogItem.php`
  - Controller: `CatalogController.php` con endpoints:
    - `GET /api/catalog` - obtener catálogo actual
    - `GET /api/catalog/template?type=` - descargar plantilla CSV
    - `POST /api/catalog/upload` - subir archivo CSV/JSON
    - `GET /api/catalog/{id}/items` - obtener items
    - `DELETE /api/catalog/{id}` - eliminar catálogo
  - Integración con chatbot: `ProcessIncomingMessageJob.php` usa el catálogo para responder preguntas

- **Frontend:**
  - Vista: `CatalogView.vue` con wizard completo:
    - Selector de tipo (Productos/Servicios/Ambos)
    - Descarga de plantilla
    - Upload de archivo
    - Vista de catálogo con items
  - Ruta: `/catalog` en el router
  - Menú sidebar agregado

### 2. Autenticación
- Login/Registro funcionan correctamente
- Tokens Sanctum
- Multi-tenancy verificado

---

## 🎯 TAREA: Implementar límites por plan + Wizard de configuración

### 1. Límites por Plan

Cada plan tiene un límite de productos/servicios en el catálogo:

| Plan | Límite de items |
|------|-----------------|
| Starter | 500 |
| Pro | 2,000 |
| Enterprise | 10,000 |

**Implementar:**
- Campo `max_catalog_items` en la tabla `tenants` o en `plans` table
- Validación al subir catálogo: si excede el límite, mostrar error claro
- El mensaje de error debe indicar: "Tu plan permite X items.升级á tu plan para subir más."

### 2. Wizard de Configuración (Onboarding)

El usuario debe seguir pasos guiados para dejar funcionando el chatbot:

```
Paso 1: Información del Negocio
  - Nombre del negocio ✓ (ya existe en tenant)
  - Rubro (categoría)
  - Descripción
  - Horarios de atención
  - FAQs (preguntas frecuentes)

Paso 2: Conectar WhatsApp
  - Escaneo de QR ✓ (ya existe en WhatsAppController)
  - Verificar conexión

Paso 3: Configurar Catálogo
  - Elegir tipo (productos/servicios/ambos) ✓ (ya implementado)
  - Subir archivo ✓ (ya implementado)

Paso 4: Personalizar Chatbot
  - Instrucciones adicionales (custom_prompt)
  - Saludo inicial
  - Respuestas automáticas

Paso 5: Activar
  - Confirmar configuración
  - Activar chatbot
```

**Estados del onboarding:**
- `step_1_done` - Info del negocio completada
- `step_2_done` - WhatsApp conectado
- `step_3_done` - Catálogo configurado
- `step_4_done` - Chatbot personalizado
- `complete` - Todo listo

---

## 📦 ARCHIVOS A MODIFICAR/CREAR

### Backend:
1. **Nueva migración** - agregar campos de límites y wizard:
   - `tenants.max_catalog_items` (integer)
   - `tenants.onboarding_step` (enum/string)
   - `tenants.onboarding_completed` (boolean)
   - `plans` table (id, name, max_catalog_items, price, features)

2. **Actualizar TenantController** - endpoints para:
   - `PUT /api/tenant/onboarding` - guardar progreso del wizard
   - `GET /api/tenant/onboarding-status` - ya existe, agregar más campos
   - `GET /api/plans` - listar planes disponibles

3. **Validar límite al subir catálogo** - en `CatalogController::upload()`

4. **Actualizar Tenant model** - agregar relación con Plan

### Frontend:
1. **Nuevo componente: OnboardingWizard.vue**
   - Pasos guiados con progress bar
   - Validación en cada paso
   - Botones siguiente/anterior
   - Estado persistido

2. **Actualizar SettingsView.vue** - integrar el wizard

3. **Actualizar router** - proteger pasos del wizard

---

## 🔧 DETALLES DE IMPLEMENTACIÓN

### Validación de límite de catálogo:

```php
// En CatalogController::upload()
$tenant = $request->user()->tenant;
$maxItems = $tenant->getMaxCatalogItems();

if (count($items) > $maxItems) {
    return response()->json([
        'success' => false,
        'message' => "Tu plan permite máximo {$maxItems} items. 
            Upgrade tu plan para subir más productos."
    ], 422);
}
```

### Modelo Plan (sugerido):

```php
// app/Models/Plan.php
class Plan extends Model {
    protected $fillable = ['name', 'max_catalog_items', 'max_conversations', 'price', 'features'];
    
    public function tenants() {
        return $this->hasMany(Tenant::class);
    }
}
```

### Estados del onboarding:

```php
// En Tenant model
const ONBOARDING_STEPS = [
    'business_info',
    'whatsapp_connection', 
    'catalog_setup',
    'chatbot_customization',
    'activation'
];

public function canProceedToStep(string $step): bool {
    $currentStepIndex = array_search($this->onboarding_step, self::ONBOARDING_STEPS);
    $targetStepIndex = array_search($step, self::ONBOARDING_STEPS);
    return $targetStepIndex <= $currentStepIndex + 1;
}
```

---

## 🧪 TESTS A VERIFICAR

1. Subir catálogo con 600 items en plan Starter → debe dar error
2. Subir catálogo con 400 items en plan Starter → debe funcionar
3. Wizard avanza solo si el paso anterior está completo
4. Usuario no puede saltar pasos
5. Al completar wizard, chatbot queda listo para responder

---

## 📝 NOTAS IMPORTANTES

- Mantener consistencia con el código existente
- Usar el mismo formato de respuestas JSON (`success`, `message`, `data`)
- El wizard debe ser intuitivo y amigable
- Mostrar claramente los límites del plan actual

---

## 🎯 PRÓXIMOS PASOS DESPUÉS DE ESTO

1. Testing completo del flujo
2. Integración con panel de pagos (Stripe)
3. Métricas y analytics
4. Despliegue a producción

---

**Estado del proyecto:** Listo para continuar  
**Prioridad:** Alta - Funcionalidad crítica para el MVP  