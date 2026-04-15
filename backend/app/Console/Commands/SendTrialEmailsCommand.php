<?php

namespace App\Console\Commands;

use App\Mail\TrialExpiringSoonMail;
use App\Mail\TrialExpiredMail;
use App\Mail\TrialFollowUpMail;
use App\Mail\TrialLastChanceMail;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrialEmailsCommand extends Command
{
    protected $signature = 'neto:send-trial-emails';
    protected $description = 'Envía emails de recordatorio a usuarios en trial';

    public function handle(): int
    {
        $plansUrl = config('app.url') . '/plans';
        $now = now();
        
        // 1. Trial expirando en 2-3 días (les quedan 2 días)
        $expiringSoon = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [
                $now->copy()->addDays(2)->startOfDay(),
                $now->copy()->addDays(3)->endOfDay()
            ])
            ->get();

        foreach ($expiringSoon as $tenant) {
            try {
                Mail::to($tenant->email)->queue(new TrialExpiringSoonMail($tenant));
                Log::info('TrialExpiringSoonMail enviado', ['tenant_id' => $tenant->id]);
            } catch (\Exception $e) {
                Log::error('Error enviando TrialExpiringSoonMail', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        // 2. Trial vencido hace 2-3 días - Follow up
        $followUp = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'expired')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [
                $now->copy()->subDays(3)->startOfDay(),
                $now->copy()->subDays(2)->endOfDay()
            ])
            ->whereNever('subscription_status', 'active') // Solo los que nunca pagaron
            ->get();

        foreach ($followUp as $tenant) {
            try {
                Mail::to($tenant->email)->queue(new TrialFollowUpMail($tenant));
                Log::info('TrialFollowUpMail enviado', ['tenant_id' => $tenant->id]);
            } catch (\Exception $e) {
                Log::error('Error enviando TrialFollowUpMail', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        // 3. Trial vencido hace 14 días - Último intento
        $lastChance = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('subscription_status', 'expired')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now->copy()->subDays(14))
            ->whereNever('subscription_status', 'active')
            ->get();

        foreach ($lastChance as $tenant) {
            try {
                Mail::to($tenant->email)->queue(new TrialLastChanceMail($tenant));
                Log::info('TrialLastChanceMail enviado', ['tenant_id' => $tenant->id]);
            } catch (\Exception $e) {
                Log::error('Error enviando TrialLastChanceMail', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info('Emails de trial enviados.');
        return Command::SUCCESS;
    }
}