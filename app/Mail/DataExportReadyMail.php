<?php

namespace App\Mail;

use App\Models\DataExportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DataExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DataExportRequest $exportRequest) {}

    public function build()
    {
        return $this->subject('بياناتك جاهزة للتحميل')
            ->markdown('emails.data-export-ready', [
                'user' => $this->exportRequest->user,
                'downloadUrl' => config('app.frontend_url') . '/account/export/' . $this->exportRequest->id . '/download',
                'expiresAt' => $this->exportRequest->expires_at->format('Y-m-d H:i'),
            ]);
    }
}
