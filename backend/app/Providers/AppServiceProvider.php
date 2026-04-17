<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schedule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        Schedule::command('neto:expire-trials')->dailyAt('00:00')
            ->timezone('America/Argentina/Buenos_Aires');
        Schedule::command('neto:check-subscription-expiration')->dailyAt('00:00')
            ->timezone('America/Argentina/Buenos_Aires');
        Schedule::command('neto:send-trial-emails')->dailyAt('09:00')
            ->timezone('America/Argentina/Buenos_Aires');
    }
}
