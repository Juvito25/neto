<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use App\Models\Scopes\TenantScope;

class Tenant extends Model implements Authenticatable
{
    use HasUuids, AuthenticatableTrait;
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
        'plan_id',
        'onboarding_step',
        'onboarding_completed',
        'trial_ends_at',
        'messages_used',
        'whatsapp_instance_id',
        'whatsapp_status',
        'stripe_customer_id',
        'payment_transfer_enabled',
        'payment_transfer_cbu',
        'payment_transfer_name',
        'payment_transfer_bank',
        'payment_cash_enabled',
        'payment_cash_note',
        'subscription_status',
        'mp_subscription_id',
        'mp_customer_id',
        'subscribed_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'faqs' => 'array',
        'trial_ends_at' => 'datetime',
        'messages_used' => 'integer',
        'onboarding_completed' => 'boolean',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        $plan = $this->plan;
        if (!$plan) {
            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }
        return $plan->name !== 'starter' ||
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function hasReachedLimit(): bool
    {
        $plan = $this->plan;
        if (!$plan) {
            return true;
        }
        return $this->messages_used >= $plan->messages_limit;
    }

    public function getCatalogItemsLimit(): int
    {
        return $this->plan?->catalog_items_limit ?? 500;
    }

    public function getMessagesLimit(): int
    {
        return $this->plan?->messages_limit ?? 1000;
    }

    public function daysRemainingInTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }
        return max(0, now()->diffInDays($this->trial_ends_at));
    }
}
