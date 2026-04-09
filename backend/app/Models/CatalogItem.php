<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'catalog_id',
        'tenant_id',
        'name',
        'description',
        'sku',
        'price',
        'quantity',
        'category',
        'duration_minutes',
        'availability_json',
        'image_url',
        'metadata_json',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'duration_minutes' => 'integer',
        'availability_json' => 'array',
        'metadata_json' => 'array',
    ];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}