<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function build(): static
    {
        return $this->subject('Tu bot NETO vence en 2 días ⏳')
            ->text('emails.trial_expiring');
    }
}