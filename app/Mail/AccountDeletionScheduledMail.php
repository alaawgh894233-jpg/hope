<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeletionScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $date
    ) {}

    public function build()
    {
        return $this
            ->subject('Account Scheduled for Deletion')
            ->view('emails.account_deletion_scheduled')
            ->with([
                'name' => $this->name,
                'date' => $this->date
            ]);
    }
}
