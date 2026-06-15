<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $otp;
    public int $ttl;

    public function __construct(string $name, string $otp, int $ttl)
    {
        $this->name = $name;
        $this->otp = $otp;
        $this->ttl = $ttl;
    }

    public function build()
    {
        return $this->subject('Verify Your Account')
            ->view('emails.verify_otp')
            ->with([
                'name' => $this->name,
                'otp' => $this->otp,
                'ttl' => $this->ttl,
            ]);
    }
}
