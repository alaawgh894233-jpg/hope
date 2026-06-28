<?php

namespace App\Jobs;


use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateUserDataExport implements ShouldQueue
{
    public function __construct(public DataExportRequest $request) {}

    public function handle()
    {
        $user = $this->request->user;
        $data = [
            'profile'      => $user->profile,
            'skills'       => $user->skills,
            'experiences'  => $user->experiences,
            'educations'   => $user->educations,
            'certifications' => $user->certifications,
            'projects'     => $user->projects,
            'applications' => $user->jobApplications,
            'comments'     => $user->comments,
            'reactions'    => $user->reactions,
            'interests'    => $user->interests,
            'reviews'      => $user->reviews,
        ];

        $path = "exports/{$user->id}_" . now()->timestamp . ".json";
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT));

        $this->request->update([
            'status' => 'ready',
            'file_path' => $path,
            'expires_at' => now()->addDays(3),
        ]);

        Mail::to($user)->send(new DataExportReadyMail($this->request));
    }
}
