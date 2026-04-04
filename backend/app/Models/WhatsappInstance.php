<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class WhatsappInstance extends Model
{
    protected $fillable = [
        'tenant_id',
        'instance_name',
        'status',
        'qr_code',
        'connected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
