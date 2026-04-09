<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'display_name',
        'catalog_items_limit',
        'messages_limit',
        'price_cents',
        'currency',
        'trial_days',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'catalog_items_limit' => 'integer',
        'messages_limit' => 'integer',
        'trial_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }

    public function getFeaturesArray(): array
    {
        return $this->features ?? [];
    }
}
