<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialLastChanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function build(): static
    {
        return $this->subject('Última oportunidad — oferta de reactivación')
            ->text('emails.trial_lastchance');
    }
}