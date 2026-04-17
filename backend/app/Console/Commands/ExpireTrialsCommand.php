<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTrialsCommand extends Command
{
    protected $signature = 'neto:expire-trials';
    protected $description = 'Decrementa los días de trial y marca como vencidos los trials que superaron su período';

    public function handle(): int
    {
        // 1. Decrementar días de trial restantes
        $trialTenants = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'trial')
            ->where('trial_remaining_days', '>', 0)
            ->get();

        $decremented = 0;
        foreach ($trialTenants as $tenant) {
            $tenant->decrement('trial_remaining_days');
            $decremented++;
            
            Log::debug('Días de trial decrementados', [
                'tenant_id' => $tenant->id,
                'trial_remaining_days' => $tenant->trial_remaining_days,
            ]);
        }

        // 2. Marcar como expired los trials que llegaron a 0
        $expiredTenants = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'trial')
            ->where('trial_remaining_days', '<=', 0)
            ->get();

        $expired = 0;
        foreach ($expiredTenants as $tenant) {
            $tenant->update(['subscription_status' => 'expired']);
            Log::info('Trial vencido marcado como expired', [
                'tenant_id' => $tenant->id,
                'business_name' => $tenant->business_name,
            ]);
            $expired++;
        }

        $this->info("Se decrementaron {$decremented} trials y se marcaron {$expired} como vencidos.");
        return Command::SUCCESS;
    }
}