<?php

namespace App\Jobs;

use App\Mail\SendOtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Queueable, SerializesModels;
    public function __construct(
        public string $email,
        public string $name,
        public string $otp,
        public int $ttl
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(
            new SendOtpMail($this->name, $this->otp, $this->ttl)
        );
    }
}
