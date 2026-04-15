<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTrialsCommand extends Command
{
    protected $signature = 'neto:expire-trials';
    protected $description = 'Marca como vencidos los trials que superaron su fecha de expiración';

    public function handle(): int
    {
        $expiredTenants = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredTenants as $tenant) {
            $tenant->update(['subscription_status' => 'expired']);
            Log::info('Trial vencido marcado como expired', [
                'tenant_id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'trial_ends_at' => $tenant->trial_ends_at,
            ]);
            $count++;
        }

        $this->info("Se procesaron {$count} trials vencidos.");
        return Command::SUCCESS;
    }
}