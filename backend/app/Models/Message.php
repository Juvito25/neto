<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class Message extends Model
{
    use HasUuids;
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'direction',
        'body',
        'media_url',
        'tokens_used',
    ];

    protected $casts = [
        'direction' => 'string',
        'tokens_used' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
