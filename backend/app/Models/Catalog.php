<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Catalog extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'description',
        'file_url',
        'file_size',
        'status',
        'error_message',
        'total_items',
        'last_sync',
    ];

    protected $casts = [
        'last_sync' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class, 'catalog_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}