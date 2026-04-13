<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\CatalogItem;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function getCurrent(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $catalog = Catalog::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $catalog
        ]);
    }

    public function getTemplate(Request $request)
    {
        $type = $request->query('type', 'products');

        if ($type === 'products') {
            $csv = "nombre,descripcion,sku,precio,cantidad,categoria,imagen_url\n";
            $csv .= "Ejemplo Producto,Descripción del producto,EJEMPLO-001,99.99,10,Electrónica,\n";

            return response()->stream(function() use ($csv) {
                echo $csv;
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="productos_template.csv"'
            ]);
        }

        if ($type === 'services') {
            $csv = "nombre,descripcion,precio,duracion_minutos,disponibilidad\n";
            $csv .= "Ejemplo Servicio,Descripción del servicio,99.99,60,lunes-viernes: 09:00-17:00\n";

            return response()->stream(function() use ($csv) {
                echo $csv;
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="servicios_template.csv"'
            ]);
        }

        if ($type === 'both') {
            $csv = "tipo,nombre,descripcion,sku,precio,cantidad,categoria,duracion_minutos,disponibilidad\n";
            $csv .= "producto,Producto Ejemplo,Descripción,EJEMPLO-001,99.99,10,Electrónica,,\n";
            $csv .= "servicio,Servicio Ejemplo,Descripción,,99.99,,,60,lunes-viernes: 09:00-17:00\n";

            return response()->stream(function() use ($csv) {
                echo $csv;
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="catalogo_template.csv"'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tipo inválido'
        ], 422);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json|max:5120',
            'type' => 'required|in:products,services,both'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        $tenant = Tenant::find($tenantId);

        // Validar límite de items según el plan
        $catalogItemsCount = CatalogItem::where('tenant_id', $tenantId)->count();
        $catalogLimit = $tenant->getCatalogItemsLimit();

        // Contar items preliminares del archivo
        $file = $request->file('file');
        $preliminaryCount = $this->countFileItems($file);

        if ($catalogItemsCount + $preliminaryCount > $catalogLimit) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo excede el límite de tu plan. Tu plan permite ' . $catalogLimit . ' items. Intenta subir ' . ($catalogLimit - $catalogItemsCount) . ' items o menos.'
            ], 422);
        }

        try {
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

            $directory = 'catalogs/' . $tenantId;
            $path = $file->storeAs($directory, $filename);

            $catalog = Catalog::create([
                'tenant_id' => $tenantId,
                'type' => $request->input('type'),
                'name' => $request->input('name', 'Mi Catálogo'),
                'file_url' => $path,
                'file_size' => $file->getSize(),
                'status' => 'processing'
            ]);

            $this->processCatalogFile($catalog);

            return response()->json([
                'success' => true,
                'message' => 'Catálogo procesado exitosamente',
                'data' => [
                    'catalog_id' => $catalog->id,
                    'status' => $catalog->status,
                    'total_items' => $catalog->total_items
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processCatalogFile(Catalog $catalog): void
    {
        try {
            $path = storage_path('app/' . $catalog->file_url);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if ($extension === 'csv') {
                $items = $this->parseCSV($path);
            } else {
                $items = json_decode(file_get_contents($path), true) ?? [];
            }

            CatalogItem::where('catalog_id', $catalog->id)->delete();

            $processed = 0;
            foreach ($items as $item) {
                $this->processItem($catalog, $item);
                $processed++;
            }

            $catalog->update([
                'status' => 'active',
                'total_items' => $processed,
                'last_sync' => now()
            ]);
        } catch (\Exception $e) {
            $catalog->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
        }
    }

    private function countFileItems($file): int
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        if ($extension === 'csv') {
            $count = 0;
            if (($handle = fopen($path, 'r')) !== false) {
                fgetcsv($handle); // Saltar headers
                while (($row = fgetcsv($handle)) !== false) {
                    if (!empty($row[0])) { // Verificar que la fila tenga datos
                        $count++;
                    }
                }
                fclose($handle);
            }
            return $count;
        }

        // JSON
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        return is_array($data) ? count($data) : 0;
    }

    private function parseCSV(string $path): array
    {
        $items = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $items[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $items;
    }

    private function processItem(Catalog $catalog, array $item): void
    {
        $data = [
            'catalog_id' => $catalog->id,
            'tenant_id' => $catalog->tenant_id,
            'name' => $item['nombre'] ?? $item['name'] ?? null,
            'description' => $item['descripcion'] ?? $item['description'] ?? null,
            'sku' => $item['sku'] ?? null,
            'price' => isset($item['precio']) ? (float) $item['precio'] : (isset($item['price']) ? (float) $item['price'] : null),
            'quantity' => isset($item['cantidad']) ? (int) $item['cantidad'] : (isset($item['quantity']) ? (int) $item['quantity'] : null),
            'category' => $item['categoria'] ?? $item['category'] ?? null,
            'duration_minutes' => isset($item['duracion_minutos']) ? (int) $item['duracion_minutos'] : (isset($item['duration_minutes']) ? (int) $item['duration_minutes'] : null),
            'image_url' => $item['imagen_url'] ?? $item['image_url'] ?? null,
        ];

        if (isset($item['disponibilidad']) || isset($item['availability'])) {
            $data['availability_json'] = json_encode($item['disponibilidad'] ?? $item['availability']);
        }

        if (!empty($data['name'])) {
            CatalogItem::create($data);
        }
    }

    public function getItems(Request $request, string $catalogId)
    {
        $tenantId = $request->user()->tenant_id;

        $items = CatalogItem::where('catalog_id', $catalogId)
            ->where('tenant_id', $tenantId)
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function storeItem(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        // Ensure the tenant has an active catalog, or create one.
        $catalog = Catalog::firstOrCreate(
            ['tenant_id' => $tenantId, 'status' => 'active'],
            ['type' => 'both', 'name' => 'Catálogo Principal']
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric',
        ]);

        $item = CatalogItem::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'catalog_id' => $catalog->id,
        ]));

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function updateItem(Request $request, string $itemId)
    {
        $tenantId = $request->user()->tenant_id;
        $item = CatalogItem::where('id', $itemId)->where('tenant_id', $tenantId)->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item no encontrado'], 404);
        }
        
        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|nullable',
        ]);

        $item->update($validated);
        
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function destroy(Request $request, string $catalogId)
    {
        $tenantId = $request->user()->tenant_id;

        $catalog = Catalog::where('id', $catalogId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$catalog) {
            return response()->json([
                'success' => false,
                'message' => 'Catálogo no encontrado'
            ], 404);
        }

        CatalogItem::where('catalog_id', $catalogId)->delete();

        if ($catalog->file_url) {
            Storage::delete($catalog->file_url);
        }

        $catalog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catálogo eliminado'
        ]);
    }
}