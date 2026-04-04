<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\TenantScope;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'password',
        'business_name',
        'rubro',
        'description',
        'business_hours',
        'faqs',
        'custom_prompt',
        'plan',
        'trial_ends_at',
        'messages_used',
        'messages_limit',
        'whatsapp_instance_id',
        'whatsapp_status',
        'stripe_customer_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'faqs' => 'array',
        'trial_ends_at' => 'datetime',
        'messages_used' => 'integer',
        'messages_limit' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function whatsappInstance(): HasMany
    {
        return $this->hasMany(WhatsappInstance::class);
    }

    public function isActive(): bool
    {
        return $this->plan !== 'starter' || 
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function hasReachedLimit(): bool
    {
        return $this->messages_used >= $this->messages_limit && $this->plan !== 'business';
    }

    public function daysRemainingInTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }
        return max(0, now()->diffInDays($this->trial_ends_at));
    }
}
