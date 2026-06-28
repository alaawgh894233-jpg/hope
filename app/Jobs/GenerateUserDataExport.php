<?php

namespace App\Jobs;

use App\Mail\DataExportReadyMail;
use App\Models\DataExportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // ✅ هاد كان ناقص
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateUserDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels; // ✅ ضيفي Dispatchable هون

    public function __construct(public DataExportRequest $request) {}

    public function handle(): void
    {
        $user = $this->request->user;

        $data = [
            'profile'        => $user->profile,
            'skills'         => $user->skills,
            'experiences'    => $user->experiences,
            'educations'     => $user->educations,
            'certifications' => $user->certifications,
            'projects'       => $user->projects,
            'applications'   => $user->jobApplications,
            'comments'       => $user->comments,
            'reactions'      => $user->reactions,
            'interests'      => $user->interests,
            'reviews'        => $user->reviews,
        ];

        $path = "exports/{$user->id}_" . now()->timestamp . '.json';
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->request->update([
            'status'     => 'ready',
            'file_path'  => $path,
            'expires_at' => now()->addDays(3),
        ]);

        Mail::to($user)->send(new DataExportReadyMail($this->request));
    }
}
