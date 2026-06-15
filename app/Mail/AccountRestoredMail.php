<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountRestoredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name
    ) {}

    public function build()
    {
        return $this->subject('Account Restored')
            ->view('emails.account_restored')
            ->with([
                'name' => $this->name
            ]);
    }
}
