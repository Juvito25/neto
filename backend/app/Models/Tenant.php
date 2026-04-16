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
        'subscription_ends_at',
        'trial_remaining_days',
        'sales_count',
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
        'subscription_ends_at' => 'date',
        'trial_remaining_days' => 'integer',
        'sales_count' => 'integer',
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
        // Trial activo
        if ($this->subscription_status === 'trial') {
            return !$this->isTrialExpired();
        }
        // Suscripción activa
        if ($this->subscription_status === 'active') {
            return !$this->isSubscriptionExpired();
        }
        return false;
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_status === 'active'
            && $this->subscription_ends_at !== null
            && \Carbon\Carbon::parse($this->subscription_ends_at)->isPast();
    }

    public function daysRemainingInSubscription(): int
    {
        if (!$this->subscription_ends_at) {
            return 0;
        }
        $endsAt = \Carbon\Carbon::parse($this->subscription_ends_at);
        if ($endsAt->isPast()) {
            return 0;
        }
        return max(0, now()->diffInDays($endsAt));
    }

    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial'
            && ($this->trial_remaining_days <= 0 || ($this->trial_ends_at !== null && $this->trial_ends_at->isPast()));
    }

    public function canUseBot(): bool
    {
        // Trial activo
        if ($this->subscription_status === 'trial') {
            return !$this->isTrialExpired();
        }
        
        // Suscripción activa
        if ($this->subscription_status === 'active') {
            return !$this->isSubscriptionExpired();
        }
        
        // Expired o cualquier otro estado
        return false;
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
        if ($this->subscription_status !== 'trial') {
            return 0;
        }
        if ($this->trial_remaining_days !== null) {
            return max(0, (int) $this->trial_remaining_days);
        }
        if (!$this->trial_ends_at) {
            return 0;
        }
        return max(0, now()->diffInDays($this->trial_ends_at));
    }
}
