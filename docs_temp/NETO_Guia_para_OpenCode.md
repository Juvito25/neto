# NETO - GUÍA PARA OPENCODE
## Qué enviar y cómo hacerlo

---

## 🎯 OBJETIVO

Que OpenCode valide el proyecto NETO ejecutando un checklist técnico completo para identificar problemas y confirmar que todo funciona correctamente.

---

## 📦 QUÉ ENVIAR A OPENCODE

### OPCIÓN A: Recomendado (Completo)

**Envía ESTOS 2 documentos:**

1. **NETO_PROMPT_INTEGRADO_PARA_IA.md** (Markdown completo)
2. **README.md** (Punto de entrada)

**Instrucción en email:**

```
Asunto: Validación de Proyecto NETO - Checklist Técnico

Hola OpenCode,

Adjunto está la especificación completa del proyecto NETO para validación.

DOCUMENTO PRINCIPAL: NETO_PROMPT_INTEGRADO_PARA_IA.md

Este documento contiene:
- Estado actual del proyecto
- Contexto técnico completo
- Checklist ejecutable con 6 verificaciones
- Comandos cURL copy-paste
- Criterios de éxito

TAREAS:
1. Leer el documento completo
2. Ejecutar cada verificación
3. Reportar resultados (✅ PASA / ❌ FALLA)
4. Identificar y documentar cualquier problema
5. Enviar reporte con hallazgos

DURACIÓN ESTIMADA: 3 horas (lectura + ejecución)

IMPORTANTE: Reportar especialmente sobre:
- ¿Dashboard carga correctamente?
- ¿Hay data-leaking entre tenants?
- ¿Sesiones persisten?
- ¿Errores se manejan bien?

Gracias,
[Tu nombre]
```

---

### OPCIÓN B: Ultra rápida (Solo prompt)

**Envía ESTO:**

1. **NETO_PROMPT_COPIAR_PEGAR.txt** (Texto plano simple)

**Instrucción:**

```
Asunto: Validar NETO - Checklist Técnico

Copia el siguiente texto en Claude/ChatGPT y ejecuta el checklist:

[PEGA TODO EL CONTENIDO DE NETO_PROMPT_COPIAR_PEGAR.txt]

Reporta qué pasa y qué falla.
```

---

## 📋 FORMATO DEL REPORTE ESPERADO

```
REPORTE DE VALIDACIÓN - NETO
============================

VERIFICACIÓN 1: FLUJO DE AUTENTICACIÓN
├─ Test 1.1 (Registrar usuario): ✅ PASA
├─ Test 1.2 (Login): ✅ PASA
├─ Test 1.3 (GET /api/user): ✅ PASA
└─ Test 1.4 (Frontend): ✅ PASA

VERIFICACIÓN 2: DASHBOARD CARGA
├─ Test 2.1 (Endpoint GET /api/dashboard): ❌ FALLA - 404 NOT FOUND
├─ Test 2.2 (DashboardController.php): ✅ EXISTE
├─ Test 2.3 (Ruta en routes/api.php): ✅ CONFIGURADA
└─ Test 2.4 (Frontend carga): ❌ FALLA - No recibe datos

VERIFICACIÓN 3: MULTI-TENANCY
├─ Test 3.1 (2 usuarios diferentes): ✅ PASA
├─ Test 3.2 (Aislamiento datos): ✅ PASA - User1 NO ve User2
├─ Test 3.3 (Modelo User): ✅ OK
├─ Test 3.4 (Modelo Tenant): ✅ OK
└─ Test 3.5 (Queries filtradas): ✅ OK

VERIFICACIÓN 4: SESIONES
├─ Test 4.1 (Archivo sesión): ✅ OK
├─ Test 4.2 (Persistencia): ✅ OK tras F5
└─ Test 4.3 (Config): ✅ OK

VERIFICACIÓN 5: MANEJO DE ERRORES
├─ Test 5.1 (Credenciales incorrectas): ✅ OK
├─ Test 5.2 (Email no existe): ✅ OK
└─ Test 5.3 (Errores en frontend): ✅ OK

VERIFICACIÓN 6: DOCUMENTACIÓN
├─ API docs: ✅ EXISTE
├─ Setup docs: ✅ EXISTE
└─ Troubleshooting: ⚠️ FALTA

RESUMEN EJECUTIVO
=================
Total Tests: 20
Pasados: 18 ✅
Fallidos: 2 ❌

PROBLEMAS IDENTIFICADOS:
1. Dashboard endpoint retorna 404 (no existe DashboardController)
2. Falta documentación de troubleshooting

RECOMENDACIONES:
1. Crear DashboardController.php con método index()
2. Registrar ruta GET /api/dashboard
3. Completar documentación

BLOQUEOS: SÍ - Fase 1 no está completa hasta arreglarlo
```

---

## 💻 CÓMO ENVIAR ESPECÍFICAMENTE

### Por Email:

```
Para: opencode@example.com
Asunto: Validación Técnica - Proyecto NETO

Hola OpenCode,

Necesito que validen el estado actual de nuestro proyecto NETO.

Adjunto: NETO_PROMPT_INTEGRADO_PARA_IA.md

El documento contiene contexto completo y un checklist ejecutable.

Pueden:
1. Leerlo directamente y ejecutar las verificaciones
2. O enviarlo a Claude/ChatGPT si les resulta más fácil

Por favor reportar con:
✅ Qué funciona
❌ Qué falla
⚠️ Problemas encontrados

Duración estimada: 3 horas

Gracias!
[Tu nombre]
```

### Por Slack:

```
Hola OpenCode,

Necesito validación del proyecto NETO. Les paso documento con checklist:

[ADJUNTA ARCHIVO NETO_PROMPT_INTEGRADO_PARA_IA.md]

O si prefieren, pueden copiar el contenido a Claude directamente.

¿Cuándo pueden hacerlo? Sería ideal para esta semana.

Gracias!
```

### Por Discord/Teams:

```
📋 Validación NETO - Checklist Técnico

Archivo: NETO_PROMPT_INTEGRADO_PARA_IA.md

Tareas:
1. ✅ Leer documento
2. ✅ Ejecutar verificaciones (6 tests)
3. ✅ Reportar resultados

Tiempo: ~3 horas

Critical: Necesitamos saber si Dashboard funciona correctamente.
```

---

## 🤖 ALTERNATIVA: ENVIAR A IA DIRECTAMENTE

Si OpenCode va a usar Claude/ChatGPT:

1. **Opción A (Recomendado):** 
   - Copia completo: **NETO_PROMPT_COPIAR_PEGAR.txt**
   - Pega en Claude
   - Ejecuta (Claude ejecutará comandos conceptualmente)

2. **Opción B (Alternativa):**
   - Copia completo: **NETO_PROMPT_INTEGRADO_PARA_IA.md**
   - Pega en Claude
   - Ejecuta

**Instrucción en el prompt:**

```
IMPORTANTE: Ejecuta CADA test en orden.

Para cada test:
1. Lee la instrucción
2. Si es comando cURL, explica qué debería pasar
3. Reporta si PASA o FALLA

Al final, da reporte ejecutivo de todo.
```

---

## ⏰ TIMELINE ESPERADO

**Día 1:** OpenCode recibe y lee documento (1 hora)
**Día 2-3:** OpenCode ejecuta validación (2 horas)
**Día 4:** OpenCode envía reporte

---

## 📊 QYÉS REPORTAR OPENCODE

### Debe reportar DEFINITIVAMENTE:

- ✅ Autenticación funcionando
- ✅ Login redirecciona a dashboard
- ✅ Dashboard carga datos
- ✅ Sesión persiste (F5)
- ✅ No hay data-leaking entre tenants
- ✅ Errores manejados correctamente
- ✅ Documentación disponible

### Si algo falla, debe reportar:

- ❌ Qué específicamente falla
- ❌ Errores exactos (stack trace si hay)
- ❌ En qué archivo/línea está el problema
- ❌ Qué necesita arreglarse

---

## 📋 CHECKLIST ANTES DE ENVIAR

- [ ] Descargué NETO_PROMPT_INTEGRADO_PARA_IA.md (o txt)
- [ ] Escribí email/mensaje claro
- [ ] Incluí instrucciones de qué hacer
- [ ] Mencioné duración estimada (3 horas)
- [ ] Expliqué por qué es importante
- [ ] Solicité reporte con formato específico
- [ ] Dije cuándo lo necesito

---

## 🎯 RESULTADO ESPERADO

En 3-4 días tendrás:

✅ Validación completa del proyecto  
✅ Lista de problemas encontrados  
✅ Recomendaciones de qué arreglar  
✅ Bloqueadores identificados  
✅ Estado claro para Fase 2

**Con esto podrás:**
- Desbloquear desarrollo
- Asignar tareas de fixes
- Planificar Fase 2 (WhatsApp)
- Tomar decisión sobre catálogo

---

## 💡 SI OPENCODE NECESITA MÁS INFO

**Además del prompt, puedes enviar:**

1. **NETO_Estado_Actual_CTO.md** - Contexto general
2. **NETO_OnePager.md** - Resumen visual
3. **NETO_Resumen_Ejecutivo_Catalogo.md** - Feature nueva
4. **README.md** - Índice de navegación

Pero el documento principal es el **PROMPT**.

---

## 🚨 PUNTOS CRÍTICOS A VALIDAR

OpenCode **DEBE ESPECIALMENTE** revisar:

1. **Dashboard endpoint:**
   - ¿Existe `/api/dashboard`?
   - ¿Retorna 200 o 404/401?

2. **Multi-tenancy:**
   - ¿User1 ve datos de User2?
   - CRÍTICO para seguridad

3. **Sesiones:**
   - ¿Se guardan en file storage?
   - ¿Persisten tras F5?

4. **Errores:**
   - ¿Son claros y legibles?
   - ¿O hay stack traces técnicos?

Si alguno de estos falla, es **BLOQUEADOR**.

---

## 📞 PREGUNTAS QUE OPENCODE PODRÍA HACER

**P: ¿Necesito acceso a la BD?**  
R: Sí, para ejecutar algunos tests. Cuéntales las credenciales.

**P: ¿Necesito entorno local?**  
R: Sí, clonar repo y `php artisan serve` + `npm run dev`

**P: ¿Cuánto tiempo realmente?**  
R: Lectura 30 min + ejecución 2-3 hrs = total 3 horas

**P: ¿Qué hago si algo falla?**  
R: Reporta exactamente qué, dónde, y por qué (error message)

---

## ✅ ENVÍO FINAL - CHECKLIST

- [ ] Tengo documento NETO_PROMPT_INTEGRADO_PARA_IA.md
- [ ] Tengo email/mensaje listo
- [ ] Menciono duración (3 horas)
- [ ] Pido reporte con formato específico
- [ ] Indico deadline (cuándo lo necesito)
- [ ] Incluyo contexto del proyecto
- [ ] Explico por qué es importante
- [ ] Listo para enviar!

---

## 🎯 TEMPLATE DE EMAIL FINAL

```
Asunto: Validación Técnica - Proyecto NETO [IMPORTANTE]

Hola OpenCode,

Necesito su ayuda para validar nuestro proyecto NETO.

Estamos en fase de desarrollo y necesitamos confirmar que algunos componentes 
críticos funcionan correctamente antes de continuar con la fase siguiente.

DOCUMENTO: Adjunto NETO_PROMPT_INTEGRADO_PARA_IA.md

TAREAS:
1. Leer el documento completo (contexto del proyecto)
2. Ejecutar cada verificación en orden (6 tests)
3. Reportar resultados en formato: ✅ PASA / ❌ FALLA
4. Si algo falla, reportar detalles exactos

PUNTOS CRÍTICOS:
- ¿Dashboard carga correctamente?
- ¿Hay aislamiento entre tenants (seguridad)?
- ¿Errores manejados correctamente?

DURACIÓN: ~3 horas

DEADLINE: [día/hora que necesites]

Agradezco el tiempo!

[Tu nombre]
```

---

**Documento:** Guía NETO para OpenCode  
**Versión:** 1.0  
**Status:** 🟢 LISTO PARA USAR  
**Próximo paso:** Envía a OpenCode hoy mismo
