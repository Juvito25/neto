<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'ilike', "%{$search}%")
            )
            ->when($request->has('active'), fn($q) => 
                $q->where('active', $request->boolean('active'))
            )
            ->orderBy('name')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        $tenantId = $request->user()->tenant->id;
        $validated['tenant_id'] = $tenantId;

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $tenantId = $request->user()->tenant->id;

        $results = DB::transaction(function () use ($file, $tenantId) {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($rows);

            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if (count($row) < 2) {
                    $errors[] = "Fila " . ($index + 2) . ": datos insuficientes";
                    continue;
                }

                try {
                    Product::create([
                        'tenant_id' => $tenantId,
                        'name' => $row[0],
                        'description' => $row[1] ?? null,
                        'price' => isset($row[2]) ? (float) $row[2] : 0,
                        'stock' => isset($row[3]) ? (int) $row[3] : null,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            return ['imported' => $imported, 'errors' => $errors];
        });

        return response()->json($results);
    }
}
