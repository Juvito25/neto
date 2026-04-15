<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function build(): static
    {
        return $this->subject('¿Qué frenó tu decisión? (te pregunta el fundador)')
            ->text('emails.trial_followup');
    }
}