# NETO - ROADMAP ACTUALIZADO CON CATÁLOGO
## Plan de desarrollo 11 semanas + integración de Catálogo

---

## 🗺️ ROADMAP REVISADO (11 semanas)

### Fase 1: Dashboard Validation (Semana 1) ⚠️ ACTUAL
**Status:** 🔄 En progreso

- [x] Autenticación básica
- [x] Multi-tenancy validado
- [ ] Dashboard carga correctamente
- [ ] Documentación técnica inicial

**Salida:** Proyecto validado y documentado

---

### Fase 2: Configuración de WhatsApp (Semana 2-3) 🎯 PRÓXIMA
**Objetivo:** User puede conectar WhatsApp

**Features:**
- Generar QR code de Evolution API
- Validar conexión
- Guardar credenciales encriptadas
- Widget de estado en Dashboard
- Webhook configuration

**Deliverables:**
- ✅ WhatsApp conectado
- ✅ Recibir mensajes
- ✅ Dashboard muestra estado

---

### ⭐ Fase 2.5: Catálogo de Productos/Servicios (Semana 3.5-4) **NUEVO**
**Objetivo:** Usuario puede subir su catálogo para que el chatbot lo use

**Features:**
1. **Selección de tipo**
   - Productos físicos
   - Servicios
   - Ambos

2. **Upload de archivo**
   - Template CSV/JSON descargable
   - Drag & drop upload
   - Validación automática
   - Procesamiento en background

3. **Gestión de catálogo**
   - Vista de items
   - Edición individual
   - Sincronización de cambios

4. **Integración con chatbot**
   - Chatbot usa catálogo para responder
   - Contexto en las respuestas
   - Respuestas más relevantes

**Duración:** 1 semana
**Por qué aquí:**
- Después de WhatsApp (necesita conexión)
- Antes de Chatbot Editor (puede usarla)
- Corta implementación (1 semana)

**Tareas:**
1. Crear modelos: Catalog, CatalogItem
2. Endpoints de upload y validación
3. UI: Selector tipo + Upload
4. Procesamiento en background (Queue)
5. Integración ChatbotService
6. Testing

**Estimación de tiempo:**
- Backend: 3 días (modelos, controllers, jobs)
- Frontend: 2 días (componentes, upload)
- Testing: 1 día
- Total: 1 semana

---

### Fase 3: Editor de Chatbot (Semana 5-6)
**Objetivo:** User puede crear y entrenar su chatbot

**Features:**
1. **Crear Chatbot**
   - Nombre, descripción, avatar
   - Tono de voz (formal/casual/friendly)
   - Idioma

2. **Training Data**
   - Subir FAQ (CSV/JSON)
   - Editor de intenciones y respuestas
   - Testing interactivo

3. **Respuestas Inteligentes**
   - Usar Claude API para respuestas
   - Personalización de comportamiento
   - Fallback responses
   - Usar catálogo como contexto ← **INTEGRACIÓN**

**Nota:** Chatbot ahora está "consciente" del catálogo gracias a Fase 2.5

---

### Fase 4: Webhooks y Procesamiento (Semana 7-8)
**Objetivo:** Mensajes de WhatsApp fluyen hacia el chatbot

**Features:**
1. **Webhook Handler**
   - Recibir mensajes de Evolution API
   - Procesar y almacenar en BD
   - Responder con IA

2. **Conversación Manager**
   - Seguimiento de contexto
   - Historia de mensajes
   - Timeout de sesión

3. **Rate Limiting**
   - Límites por tenant
   - Alertas de exceso

---

### Fase 5: Analytics y Reporting (Semana 9-10)
**Objetivo:** User ve métricas de uso

**Features:**
1. **Dashboard Metrics**
   - Total conversaciones
   - Mensajes enviados/recibidos
   - Preguntas sobre productos ← **USA CATÁLOGO**
   - Satisfacción del usuario

2. **Reportes**
   - Exportar datos
   - Análisis de tendencias
   - Productos más consultados ← **USA CATÁLOGO**

3. **Logs Detallados**
   - Historial de conversaciones
   - Debug de respuestas

---

### Fase 6: Seguridad y Scaling (Semana 11)
**Objetivo:** Preparar para producción

**Features:**
1. **Seguridad**
   - 2FA para usuarios
   - API key management
   - Encryption de datos sensibles
   - Audit logs

2. **Scaling**
   - Implementar Redis para sesiones
   - Cache de respuestas
   - Queue para webhooks
   - Load balancing

3. **Monitoreo**
   - Application monitoring
   - Error tracking (Sentry)
   - Performance monitoring

---

## 📊 TIMELINE ACTUALIZADO

| Fase | Duración | Inicio | Fin | Estado |
|------|----------|--------|-----|--------|
| 1. Dashboard Validation | 1 semana | Semana 1 | Semana 1 | 🔄 Activo |
| 2. WhatsApp Setup | 2 semanas | Semana 2 | Semana 3 | ⏳ Siguiente |
| **2.5. Catálogo** | **1 semana** | **Semana 3.5** | **Semana 4** | **⭐ NUEVO** |
| 3. Chatbot Editor | 2 semanas | Semana 5 | Semana 6 | ⏳ Luego |
| 4. Webhooks/Processing | 2 semanas | Semana 7 | Semana 8 | ⏳ Luego |
| 5. Analytics | 2 semanas | Semana 9 | Semana 10 | ⏳ Luego |
| 6. Security/Scaling | 1 semana | Semana 11 | Semana 11 | ⏳ Luego |
| **TOTAL HASTA MVP** | **11 semanas** | | | |

---

## 🔄 DEPENDENCIAS Y RELACIONES

```
Fase 1: Dashboard ✅
    ↓
Fase 2: WhatsApp Setup 🎯
    ↓
Fase 2.5: Catálogo ⭐ (NUEVO)
    ├─ Usa: División de datos por tenant
    ├─ Usada por: Fase 3 (Chatbot Editor)
    └─ Usa: Queue para procesamiento
    ↓
Fase 3: Chatbot Editor
    ├─ Lee: Catálogo
    ├─ Lee: WhatsApp config
    └─ Crea: Reglas de respuesta
    ↓
Fase 4: Webhooks
    ├─ Lee: Catálogo
    ├─ Lee: Chatbot rules
    └─ Usa: WhatsApp conexión
    ↓
Fase 5: Analytics
    ├─ Lee: Catálogo
    ├─ Lee: Conversaciones
    └─ Reporta: Datos de interés
    ↓
Fase 6: Security/Scaling
    └─ Asegura: Todo lo anterior
```

---

## 🎯 IMPACTO DE CATÁLOGO EN OTRAS FASES

### En Fase 3 (Chatbot Editor):
```
Antes (sin catálogo):
- Chatbot solo responde reglas manual configuradas
- Respuestas genéricas
- No sabe qué vende la tienda

Después (con catálogo):
- Chatbot accede al catálogo automáticamente
- Respuestas contextualizadas
- Puede decir "tenemos ese producto" o "no disponible"
```

### En Fase 4 (Webhooks):
```
Antes:
- Mensaje: "¿Qué servicios ofrecen?"
- Chatbot: Respuesta genérica

Después:
- Mensaje: "¿Qué servicios ofrecen?"
- Chatbot: Lee catálogo, enumera servicios con precios
- Cliente: Satisfecho, convierte rápido
```

### En Fase 5 (Analytics):
```
Analytics ahora puede mostrar:
- Productos más preguntados
- Servicios con más interés
- Tasa de conversión por producto
- Preguntas que no tenían respuesta
```

---

## 💡 VENTAJAS COMPETITIVAS

**Con Catálogo:**
✅ Chatbot más inteligente
✅ Respuestas contextualizadas
✅ Mejor UX para clientes
✅ Métricas de interés del cliente
✅ Fácil para usuario (solo sube CSV)

**Sin Catálogo:**
❌ Chatbot genérico
❌ User debe configurar manualmente
❌ Respuestas out-of-context
❌ Difícil escalar

---

## 📋 CHECKLIST POR FASE

### Fase 2.5 - Catálogo:

**Backend:**
- [ ] Migración: crear tabla catalogs
- [ ] Migración: crear tabla catalog_items
- [ ] Migración: crear tabla catalog_syncs
- [ ] Modelo Catalog
- [ ] Modelo CatalogItem
- [ ] CatalogController (upload, getCurrent, getItems)
- [ ] Job ProcessCatalogFile
- [ ] Validación CSV/JSON
- [ ] Tests unitarios
- [ ] Tests integración

**Frontend:**
- [ ] Componente CatalogTypeSelector
- [ ] Componente CatalogUpload
- [ ] Componente CatalogView
- [ ] Service catalogService.js
- [ ] Integración en Dashboard
- [ ] Estados loading/error/success
- [ ] Tests componentes
- [ ] Manejo de errores

**Integración:**
- [ ] ChatbotService leer catálogo
- [ ] System prompt incluir catálogo
- [ ] Testing: respuestas con contexto
- [ ] Performance: catálogo grande (10k items)

**Documentation:**
- [ ] README para usuarios (cómo subir CSV)
- [ ] API docs
- [ ] Troubleshooting

---

## 🚀 EJECUCIÓN SEMANA 3.5-4

### Semana 3 (final - 2-3 días):
1. Lunes: Code review de WhatsApp
2. Martes-Miércoles: Inicial de Catálogo
   - Meetings de requerimientos
   - Diseño de modelos
   - Diseño de UI

### Semana 4 (completa - 5 días):
1. Lunes: Backend completado (modelos, controllers, jobs)
2. Martes: Frontend componentes
3. Miércoles: Integración con ChatbotService
4. Jueves: Testing y fixes
5. Viernes: Deploy + documentation

---

## 📈 MÉTRICAS DE ÉXITO (Fase 2.5)

**Funcionalidad:**
- ✅ User puede subir catálogo
- ✅ Validación funciona
- ✅ Chatbot usa catálogo
- ✅ 0 data-leaks entre tenants

**Performance:**
- ✅ Upload < 5 segundos (archivos hasta 5MB)
- ✅ Procesamiento < 30 segundos (10k items)
- ✅ Consulta de catálogo < 200ms

**UX:**
- ✅ No errores técnicos visibles
- ✅ Feedback claro (processing, success, error)
- ✅ Templates fáciles de llenar

---

## 🔧 CONFIGURACIÓN RECOMENDADA

```php
// config/catalog.php
return [
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'max_items' => 10000,
    'chunk_processing' => 100, // Procesar de a 100
    'allowed_fields_products' => [
        'nombre', 'descripcion', 'sku', 'precio', 'cantidad', 'categoria'
    ],
    'allowed_fields_services' => [
        'nombre', 'descripcion', 'precio', 'duracion_minutos', 'disponibilidad'
    ],
];
```

---

## 📚 DOCUMENTACIÓN PARA USUARIOS

### "Cómo subir tu catálogo"

**¿Qué necesitas?**
- Un archivo CSV o JSON con tus productos/servicios
- Tu navegador

**Paso 1: Descarga el template**
1. Ve a Dashboard → Catálogo
2. Haz click en "Crear catálogo"
3. Elige si vendes productos o servicios
4. Haz click en "Descargar template"

**Paso 2: Completa el archivo**
- Abre el archivo en Excel/Google Sheets
- Llena los datos de tus productos
- Guarda como CSV

**Paso 3: Sube el archivo**
1. Regresa a Dashboard → Catálogo
2. Arrastra el archivo o haz click para seleccionar
3. Haz click en "Subir"

**Listo!**
Tu catálogo está activo. El chatbot ahora sabe qué vendes.

---

## ⚠️ CONSIDERACIONES TÉCNICAS

### Rate Limiting:
```php
// 1 upload cada 5 minutos
RateLimiter::for('catalog', function (Request $request) {
    return Limit::perMinutes(5, 1);
});
```

### Almacenamiento de archivos:
```php
// storage/catalogs/{tenant_id}/
// No servir directamente (usar descarga)
```

### Limpieza de archivos:
```php
// Opción 1: Borrar después de procesar
// Opción 2: Guardar para auditoría
// Recomendación: Guardar con rotación (30 días)
```

---

## 🎓 LEARNING PATH PARA DEV TEAM

1. **Día 1:** Entender multi-tenancy y aislamiento
2. **Día 2:** Procesar CSV con Laravel
3. **Día 3:** Queue jobs en Laravel
4. **Día 4:** Integrar contexto en Claude API
5. **Día 5:** Vue components para upload

---

**Documento:** Roadmap NETO v2 - Con Catálogo integrado  
**Versión:** 2.0  
**Estado:** 🟡 PROPUESTO - LISTA PARA REVISAR  
**Fecha:** Abril 2026
