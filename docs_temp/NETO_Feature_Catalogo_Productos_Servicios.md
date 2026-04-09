# NETO - Funcionalidad de Catálogo de Productos/Servicios
## Análisis, especificación y roadmap integrado

---

## 🎯 VISIÓN GENERAL

**Objetivo:** Permitir que usuarios carguen información sobre sus productos o servicios, para que el chatbot IA pueda usar esa información al responder mensajes de clientes.

**Caso de uso:**
- **Negocio de venta física:** Sube CSV/JSON con productos (nombre, precio, descripción, stock)
- **Negocio de servicios:** Sube CSV/JSON con servicios (nombre, precio, descripción, disponibilidad)
- **Chatbot:** Lee ese catálogo y puede responder preguntas del cliente en contexto

**Impacto:** De chatbot genérico → chatbot personalizado con conocimiento del negocio

---

## 📊 ANÁLISIS DE MERCADO

### ¿Por qué es crítico?

| Negocio | Necesidad | Ejemplo |
|---------|-----------|---------|
| **Ecommerce** | Saber qué vende | "¿Tienen tallas L?" → Bot consulta catálogo |
| **Prestador servicios** | Disponibilidad y precios | "¿Cuánto cuesta una clase?" → Bot responde con tarifa |
| **Restaurante** | Menú actualizado | "¿Qué pizzas tienen?" → Bot lista opciones |
| **SPA/Estética** | Servicios y duración | "¿Cuánto dura un masaje?" → Bot da detalles |

### Competencia hace esto:
- ✅ Intercom (Knowledge base)
- ✅ Tidio (Product catalog)
- ✅ ManyChat (Google Sheets integration)
- ✅ Chatfuel (Product feed)

**Conclusión:** Es feature estándar en el mercado. Implementarla rápido = ventaja competitiva.

---

## 💡 PROPUESTA DE FUNCIONALIDAD

### Flujo del Usuario

```
1. Usuario hace login
   ↓
2. Dashboard → "Configurar Catálogo"
   ↓
3. Popup: "¿Qué vendes?"
   ├─ [Productos Físicos]
   ├─ [Servicios]
   └─ [Ambos]
   ↓
4. Descarga template CSV/JSON
   ↓
5. Completa archivo localmente
   ↓
6. Sube archivo
   ↓
7. Sistema valida y procesa
   ↓
8. Catálogo activo → Chatbot puede usarlo
```

---

## 🏗️ ARQUITECTURA TÉCNICA

### Modelo de Datos

#### Tabla: `catalogs` (nueva)
```sql
CREATE TABLE catalogs (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    type ENUM('products', 'services', 'both'),
    name VARCHAR(255),
    description TEXT,
    file_url VARCHAR(255),
    file_size INT,
    status ENUM('draft', 'processing', 'active', 'error'),
    error_message TEXT,
    total_items INT,
    last_sync TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

#### Tabla: `catalog_items` (nueva)
```sql
CREATE TABLE catalog_items (
    id UUID PRIMARY KEY,
    catalog_id UUID NOT NULL,
    tenant_id UUID NOT NULL,
    
    -- Campos comunes
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(100),
    
    -- Solo productos
    price DECIMAL(10,2),
    quantity INT,
    category VARCHAR(100),
    
    -- Solo servicios
    duration_minutes INT,
    availability_json JSON, -- {"monday":"09:00-17:00","tuesday":...}
    
    -- Metadata
    image_url VARCHAR(255),
    metadata_json JSON, -- datos adicionales
    
    created_at TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

#### Tabla: `catalog_syncs` (para auditoría)
```sql
CREATE TABLE catalog_syncs (
    id UUID PRIMARY KEY,
    catalog_id UUID NOT NULL,
    action VARCHAR(50), -- 'upload', 'delete', 'update'
    items_processed INT,
    items_failed INT,
    error_details TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id)
);
```

---

## 📋 ESPECIFICACIÓN DE FEATURES

### Feature 1: Wizard de Selección de Tipo

**Componente:** `CatalogTypeSelector.vue`

```vue
<template>
  <div class="catalog-type-selector">
    <h2>¿Qué tipo de negocio tienes?</h2>
    
    <div class="options">
      <button 
        @click="selectType('products')"
        class="option-card"
      >
        <icon name="box" />
        <h3>Productos Físicos</h3>
        <p>Vendes cosas: ropa, electrónica, etc.</p>
      </button>
      
      <button 
        @click="selectType('services')"
        class="option-card"
      >
        <icon name="briefcase" />
        <h3>Servicios</h3>
        <p>Ofreces servicios: clases, consultoría, etc.</p>
      </button>
      
      <button 
        @click="selectType('both')"
        class="option-card"
      >
        <icon name="layers" />
        <h3>Ambos</h3>
        <p>Vendes productos Y servicios</p>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    selectType(type) {
      this.$emit('type-selected', type);
    }
  }
}
</script>
```

---

### Feature 2: Template Download

**Endpoint:** `GET /api/catalog/template?type=products|services|both`

**Backend:**
```php
// app/Http/Controllers/Api/CatalogController.php
public function getTemplate(Request $request)
{
    $type = $request->query('type');
    
    if ($type === 'products') {
        return $this->generateProductsTemplate();
    } elseif ($type === 'services') {
        return $this->generateServicesTemplate();
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Invalid type'
    ], 422);
}

private function generateProductsTemplate()
{
    // Generar CSV con headers
    $csv = "nombre,descripcion,sku,precio,cantidad,categoria\n";
    $csv .= "Ejemplo Producto,Descripción del producto,SKU-001,99.99,10,Categoría\n";
    
    return response()->stream(function() use ($csv) {
        echo $csv;
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="productos_template.csv"'
    ]);
}

private function generateServicesTemplate()
{
    $csv = "nombre,descripcion,precio,duracion_minutos,disponibilidad\n";
    $csv .= "Ejemplo Servicio,Descripción,99.99,60,\"lunes-viernes: 09:00-17:00\"\n";
    
    return response()->stream(function() use ($csv) {
        echo $csv;
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="servicios_template.csv"'
    ]);
}
```

**Frontend:**
```javascript
// src/services/catalogService.js
export async function downloadTemplate(type) {
  const response = await fetch(`/api/catalog/template?type=${type}`)
  const blob = await response.blob()
  
  // Crear descarga
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${type}_template.csv`
  document.body.appendChild(a)
  a.click()
  window.URL.revokeObjectURL(url)
}
```

---

### Feature 3: Upload de Archivo

**Componente:** `CatalogUpload.vue`

```vue
<template>
  <div class="catalog-upload">
    <h2>Sube tu catálogo</h2>
    
    <div class="upload-area"
      @drop="handleDrop"
      @dragover.prevent
      @dragenter.prevent
    >
      <input 
        ref="fileInput"
        type="file"
        accept=".csv,.json"
        @change="handleFileSelect"
      />
      
      <div v-if="!file" class="placeholder">
        <icon name="upload" />
        <p>Arrastra aquí o haz click para seleccionar</p>
      </div>
      
      <div v-else class="file-preview">
        <icon name="check" />
        <p>{{ file.name }} ({{ formatFileSize(file.size) }})</p>
        <button @click="uploadFile">Subir</button>
      </div>
    </div>
    
    <div v-if="uploading" class="progress">
      <progress :value="uploadProgress" max="100"></progress>
      <p>{{ uploadProgress }}%</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      file: null,
      uploading: false,
      uploadProgress: 0
    }
  },
  methods: {
    handleFileSelect(event) {
      this.file = event.target.files[0]
    },
    handleDrop(event) {
      event.preventDefault()
      this.file = event.dataTransfer.files[0]
    },
    async uploadFile() {
      const formData = new FormData()
      formData.append('file', this.file)
      formData.append('type', this.type) // products|services|both
      
      this.uploading = true
      
      try {
        const response = await fetch('/api/catalog/upload', {
          method: 'POST',
          body: formData,
          credentials: 'include'
        })
        
        const data = await response.json()
        
        if (data.success) {
          this.$emit('upload-success', data.data)
        } else {
          this.$emit('upload-error', data.message)
        }
      } catch (error) {
        this.$emit('upload-error', error.message)
      } finally {
        this.uploading = false
      }
    },
    formatFileSize(bytes) {
      return (bytes / 1024).toFixed(2) + ' KB'
    }
  }
}
</script>
```

**Endpoint:** `POST /api/catalog/upload`

```php
// app/Http/Controllers/Api/CatalogController.php
public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,json|max:5120', // 5MB max
        'type' => 'required|in:products,services,both'
    ]);
    
    $user = $request->user();
    $tenantId = $user->tenant_id;
    
    try {
        // 1. Guardar archivo temporalmente
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = storage_path('catalogs/' . $tenantId . '/' . $filename);
        $file->move(storage_path('catalogs/' . $tenantId), $filename);
        
        // 2. Crear registro de catálogo
        $catalog = Catalog::create([
            'tenant_id' => $tenantId,
            'type' => $request->input('type'),
            'file_url' => $path,
            'file_size' => $file->getSize(),
            'status' => 'processing'
        ]);
        
        // 3. Procesar archivo en background (queue)
        ProcessCatalogFile::dispatch($catalog);
        
        return response()->json([
            'success' => true,
            'message' => 'Archivo en proceso',
            'data' => [
                'catalog_id' => $catalog->id,
                'status' => 'processing'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar archivo: ' . $e->getMessage()
        ], 500);
    }
}
```

---

### Feature 4: Procesamiento en Background

**Job:** `ProcessCatalogFile.php`

```php
// app/Jobs/ProcessCatalogFile.php
namespace App\Jobs;

use App\Models\Catalog;
use App\Models\CatalogItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCatalogFile implements ShouldQueue
{
    use Queueable;
    
    public function __construct(private Catalog $catalog) {}
    
    public function handle()
    {
        try {
            $items = $this->parseFile($this->catalog->file_url);
            
            // Validar items
            $validated = $this->validateItems($items, $this->catalog->type);
            
            if (count($validated['errors']) > 0) {
                $this->catalog->update([
                    'status' => 'error',
                    'error_message' => json_encode($validated['errors'])
                ]);
                return;
            }
            
            // Limpiar catálogo anterior
            CatalogItem::where('catalog_id', $this->catalog->id)->delete();
            
            // Insertar items nuevos
            foreach ($validated['items'] as $item) {
                CatalogItem::create(array_merge($item, [
                    'catalog_id' => $this->catalog->id,
                    'tenant_id' => $this->catalog->tenant_id
                ]));
            }
            
            // Marcar como activo
            $this->catalog->update([
                'status' => 'active',
                'total_items' => count($validated['items']),
                'last_sync' => now()
            ]);
            
            // Log
            CatalogSync::create([
                'catalog_id' => $this->catalog->id,
                'action' => 'upload',
                'items_processed' => count($validated['items']),
                'items_failed' => count($validated['errors'])
            ]);
            
        } catch (\Exception $e) {
            $this->catalog->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
        }
    }
    
    private function parseFile($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            return $this->parseCSV($path);
        } else {
            return json_decode(file_get_contents($path), true);
        }
    }
    
    private function parseCSV($path)
    {
        $items = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $items[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        return $items;
    }
    
    private function validateItems($items, $type)
    {
        $validated = [];
        $errors = [];
        
        foreach ($items as $index => $item) {
            try {
                if ($type === 'products') {
                    $this->validateProductItem($item);
                } else {
                    $this->validateServiceItem($item);
                }
                $validated[] = $item;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return compact('validated', 'errors');
    }
    
    private function validateProductItem($item)
    {
        if (empty($item['nombre'])) {
            throw new \Exception('Campo "nombre" requerido');
        }
        if (empty($item['precio']) || !is_numeric($item['precio'])) {
            throw new \Exception('Campo "precio" requerido y debe ser numérico');
        }
    }
    
    private function validateServiceItem($item)
    {
        if (empty($item['nombre'])) {
            throw new \Exception('Campo "nombre" requerido');
        }
        if (empty($item['precio']) || !is_numeric($item['precio'])) {
            throw new \Exception('Campo "precio" requerido y debe ser numérico');
        }
        if (empty($item['duracion_minutos']) || !is_numeric($item['duracion_minutos'])) {
            throw new \Exception('Campo "duracion_minutos" requerido');
        }
    }
}
```

---

### Feature 5: Vista del Catálogo

**Componente:** `CatalogView.vue`

```vue
<template>
  <div class="catalog-view">
    <header>
      <h1>Mi Catálogo</h1>
      <button @click="openUploadDialog" class="btn-primary">
        Actualizar catálogo
      </button>
    </header>
    
    <div v-if="!catalog" class="empty-state">
      <p>No tienes catálogo activo</p>
      <button @click="initializeCatalog">Crear catálogo</button>
    </div>
    
    <div v-else>
      <div class="status-card">
        <p><strong>Estado:</strong> {{ catalog.status }}</p>
        <p><strong>Tipo:</strong> {{ catalog.type }}</p>
        <p><strong>Items:</strong> {{ catalog.total_items }}</p>
        <p><strong>Última actualización:</strong> {{ formatDate(catalog.last_sync) }}</p>
      </div>
      
      <div v-if="catalog.status === 'error'" class="error-alert">
        <p>{{ catalog.error_message }}</p>
      </div>
      
      <div class="items-grid">
        <div v-for="item in items" :key="item.id" class="item-card">
          <h3>{{ item.name }}</h3>
          <p>{{ item.description }}</p>
          
          <div v-if="catalog.type === 'products'" class="item-details">
            <span class="price">${{ item.price }}</span>
            <span class="stock">Stock: {{ item.quantity }}</span>
          </div>
          
          <div v-else class="item-details">
            <span class="price">${{ item.price }}</span>
            <span class="duration">{{ item.duration_minutes }} min</span>
          </div>
          
          <button @click="editItem(item)" class="btn-small">Editar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      catalog: null,
      items: []
    }
  },
  async mounted() {
    await this.loadCatalog()
  },
  methods: {
    async loadCatalog() {
      const response = await fetch('/api/catalog', {
        credentials: 'include'
      })
      const data = await response.json()
      
      if (data.success && data.data) {
        this.catalog = data.data
        await this.loadItems()
      }
    },
    async loadItems() {
      const response = await fetch(`/api/catalog/${this.catalog.id}/items`, {
        credentials: 'include'
      })
      const data = await response.json()
      this.items = data.data
    }
  }
}
</script>
```

**Endpoint:** `GET /api/catalog` (obtener catálogo actual)

```php
public function getCurrent(Request $request)
{
    $catalog = Catalog::where('tenant_id', $request->user()->tenant_id)
        ->where('status', 'active')
        ->latest()
        ->first();
    
    return response()->json([
        'success' => true,
        'data' => $catalog
    ]);
}
```

**Endpoint:** `GET /api/catalog/{id}/items` (obtener items)

```php
public function getItems($catalogId, Request $request)
{
    $items = CatalogItem::where('catalog_id', $catalogId)
        ->where('tenant_id', $request->user()->tenant_id)
        ->paginate(20);
    
    return response()->json([
        'success' => true,
        'data' => $items
    ]);
}
```

---

### Feature 6: Integración con Chatbot

**Cómo el chatbot usa el catálogo:**

```php
// app/Services/ChatbotService.php
public function generateResponse($userMessage, $tenantId, $conversationContext)
{
    // 1. Obtener catálogo activo
    $catalog = Catalog::where('tenant_id', $tenantId)
        ->where('status', 'active')
        ->first();
    
    // 2. Preparer contexto
    $systemPrompt = $this->buildSystemPrompt($catalog);
    
    // 3. Llamar a Claude API con contexto
    $response = Anthropic::create([
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 1024,
        'system' => $systemPrompt,
        'messages' => [
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ]
    ]);
    
    return $response->content[0]->text;
}

private function buildSystemPrompt($catalog)
{
    $catalogJson = '';
    
    if ($catalog) {
        $items = CatalogItem::where('catalog_id', $catalog->id)
            ->limit(100) // Limitar para no exceeder contexto
            ->get();
        
        $catalogJson = "\n\n## CATÁLOGO DE " . strtoupper($catalog->type) . "\n";
        $catalogJson .= json_encode($items, JSON_PRETTY_PRINT);
    }
    
    return <<<PROMPT
Eres un asistente de ventas amigable para el negocio del cliente.
Responde preguntas sobre productos/servicios usando SOLO la información del catálogo.
Si no sabes la respuesta, ofrece conectar con un humano.
Sé breve (máximo 3 líneas), amigable y profesional.

{$catalogJson}

Responde siempre en español.
PROMPT;
}
```

---

## 📅 INTEGRACIÓN AL ROADMAP

### Fase 2.5: Catálogo de Productos/Servicios (Semana 3.5-4)

**Duración:** 1 semana (entre WhatsApp Setup y Chatbot Editor)

**Por qué después de WhatsApp:**
- WhatsApp setup es bloqueador (sin WhatsApp conectado no hay chatbot)
- Catálogo es importante pero no bloqueador
- Permite empezar a integrar con Chatbot

**Tareas:**
1. Crear modelos Catalog y CatalogItem
2. Implementar upload y validación
3. Crear UI de selección y upload
4. Integrar con ChatbotService
5. Testing

**Deliverables:**
- ✅ Usuario puede subir productos/servicios
- ✅ Chatbot usa catálogo para responder
- ✅ Validación y manejo de errores

---

## 🎨 UI/UX FLOW

```
Dashboard
  ↓
[Widget: "Configurar Catálogo"] (si no existe)
  ↓
Popup: "¿Qué tipo de negocio?"
  ├─ Productos Físicos
  ├─ Servicios
  └─ Ambos
  ↓
Descarga Template
  ↓
Usuario llena CSV/JSON
  ↓
Sube archivo
  ↓
Sistema procesa en background
  ↓
Widget actualiza con:
  - Estado (activo/procesando/error)
  - Total de items
  - Última actualización
  - Botón "Editar"
```

---

## 🔒 CONSIDERACIONES DE SEGURIDAD

1. **Validación de archivos:**
   - ✅ Max 5MB por archivo
   - ✅ Solo CSV/JSON
   - ✅ Validar estructura
   - ✅ Limpiar datos maliciosos

2. **Aislamiento multi-tenant:**
   - ✅ Filtrar catálogos por tenant_id
   - ✅ User no puede ver catálogo de otro tenant
   - ✅ Items filtrados por tenant_id

3. **Rate limiting:**
   - ✅ Max 1 upload por 5 minutos
   - ✅ Max 10,000 items por catálogo

4. **Almacenamiento:**
   - ✅ Archivos en `storage/catalogs/{tenant_id}/`
   - ✅ Acceso protegido (no public)
   - ✅ Borrar archivos después de procesar (opcional guardar)

---

## 💾 ESTRUCTURA DE DATOS (Ejemplo)

### CSV de Productos:
```csv
nombre,descripcion,sku,precio,cantidad,categoria,imagen_url
iPhone 15,Smartphone Apple última generación,IPHONE15,999.99,5,Electrónica,https://...
MacBook Pro,Laptop profesional Apple,MACBOOKPRO,1999.99,2,Electrónica,https://...
AirPods Pro,Auriculares inalámbricos,AIRPODSPRO,249.99,10,Accesorios,https://...
```

### JSON de Servicios:
```json
[
  {
    "nombre": "Consulta de Nutrición",
    "descripcion": "Sesión personalizada con nutricionista",
    "precio": 80.00,
    "duracion_minutos": 60,
    "disponibilidad": "lunes-viernes: 09:00-17:00"
  },
  {
    "nombre": "Plan Mensual",
    "descripcion": "Seguimiento completo durante un mes",
    "precio": 250.00,
    "duracion_minutos": 4320,
    "disponibilidad": "todo el mes"
  }
]
```

---

## 📊 QUERIES DE EJEMPLO

**¿Cuántos productos tenemos?**
```php
$count = CatalogItem::where('catalog_id', $catalogId)
    ->where('tenant_id', $tenantId)
    ->count();
```

**¿Qué servicios tienen disponibilidad el lunes?**
```php
$services = CatalogItem::where('catalog_id', $catalogId)
    ->whereJsonContains('metadata_json->availability', 'monday')
    ->get();
```

**¿Productos con stock bajo?**
```php
$lowStock = CatalogItem::where('catalog_id', $catalogId)
    ->where('quantity', '<', 5)
    ->get();
```

---

## 🚀 NEXT STEPS

1. **Validar con stakeholders:**
   - ¿CSV o JSON o ambos?
   - ¿Campos adicionales necesarios?
   - ¿Integración con sistemas externos (Shopify, etc)?

2. **Definir límites:**
   - Max items: 10,000
   - Max tamaño archivo: 5MB
   - Max campos custom: 10

3. **Considerar futuro:**
   - Integración Shopify/WooCommerce
   - Sincronización automática
   - Actualización en tiempo real (webhooks)

---

## 📈 MÉTRICAS DE ÉXITO

Por usuario:
- [ ] 80% de usuarios sube catálogo en primer mes
- [ ] Chatbot responde preguntas de producto con 90%+ accuracy
- [ ] Conversiones aumentan 30% con catálogo activo

---

**Documento:** Especificación NETO Catálogo de Productos/Servicios  
**Versión:** 1.0  
**Estado:** 🟡 PROPUESTO - PENDIENTE APROBACIÓN  
**Fecha:** Abril 2026
