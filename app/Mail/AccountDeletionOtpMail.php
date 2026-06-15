<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeletionOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $otp
    ) {}

    public function build()
    {
        return $this->subject('Account Deletion OTP')
            ->view('emails.account_deletion_otp')
            ->with([
                'name' => $this->name,
                'otp' => $this->otp
            ]);
    }
}
