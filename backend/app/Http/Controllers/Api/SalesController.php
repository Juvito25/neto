<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant->id;
        $sales = Sale::with('contact')
            ->where('tenant_id', $tenantId)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($sales);
    }

    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->tenant_id !== $request->user()->tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled'
        ]);

        $sale->update(['status' => $validated['status']]);
        $sale->load('contact');

        return response()->json($sale);
    }
}
