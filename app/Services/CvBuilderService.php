<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class CvBuilderService
{
    /**
     * ✅ بناء CV كامل موحد لجميع المجالات (برمجة، محاسبة، طب، هندسة، إلخ)
     */
    public function build(User $user): array
    {
        $profile = $user->profile;

        // جمع المهارات (جميع الأنواع)
        $allSkills = $user->skills
            ->pluck('name')
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $technical = $user->skills
            ->where('type', 'technical')
            ->pluck('name')
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->toArray();

        $tools = $user->skills
            ->where('type', 'tool')
            ->pluck('name')
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->toArray();

        $languages = $user->skills
            ->where('type', 'language')
            ->pluck('name')
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->toArray();

        $softSkills = $user->skills
            ->where('type', 'soft_skill')
            ->pluck('name')
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->toArray();

        return [
            'header' => [
                'name' => $profile?->full_name ?? $user->name,
                'title' => $profile?->headline ?? '',
                'contact' => [
                    'email' => $user->email,
                    'phone' => $profile?->phone ?? '',
                    'location' => trim(
                        ($profile?->city ?? '') . ($profile?->country ? ', ' . $profile->country : ''),
                        ', '
                    ),
                    'linkedin' => $profile?->linkedin ?? '',
                    'github' => $profile?->github ?? '',
                    'portfolio' => $profile?->portfolio ?? '',
                ],
            ],
            'summary' => $profile?->summary ?? '',
            'skills' => [
                'all' => $allSkills,
                'technical' => $technical,
                'tools' => $tools,
                'languages' => $languages,
                'soft_skills' => $softSkills,
            ],
            'experience' => $user->experiences->map(fn($exp) => [
                'id' => $exp->id,
                'title' => $exp->position,
                'company' => $exp->company,
                'start_date' => $this->formatDate($exp->start_date),
                'end_date' => $this->formatDate($exp->end_date),
                'current' => (bool) ($exp->is_current ?? false),
                'highlights' => $exp->description
                    ? array_values(array_filter(
                        explode("\n", $exp->description),
                        fn($line) => trim($line)
                    ))
                    : [],
                'technologies' => $exp->technologies_used ?? [],
            ])->values()->toArray(),
            'education' => $user->educations->map(fn($edu) => [
                'id' => $edu->id,
                'institution' => $edu->institution,
                'degree' => $edu->degree,
                'field_of_study' => $edu->field_of_study ?? '',
                'start_date' => $this->formatDate($edu->start_date),
                'end_date' => $this->formatDate($edu->end_date),
                'grade' => $edu->grade,
            ])->values()->toArray(),
            'projects' => $user->projects->map(fn($proj) => [
                'id' => $proj->id,
                'title' => $proj->title,
                'description' => $proj->description ?? '',
                'link' => $proj->link ?? '',
                'technologies' => $proj->technologies ?? [],
            ])->values()->toArray(),
            'certifications' => $user->certifications->map(fn($cert) => [
                'id' => $cert->id,
                'name' => $cert->name,
                'issuer' => $cert->issuer ?? '',
                'issued_at' => $this->formatDate($cert->issued_at),
                'expires_at' => $this->formatDate($cert->expires_at),
                'credential_id' => $cert->credential_id ?? '',
            ])->values()->toArray(),
            'trainings' => $user->trainings->map(fn($training) => [
                'id' => $training->id,
                'title' => $training->title,
                'provider' => $training->provider ?? '',
                'start_date' => $this->formatDate($training->start_date),
                'end_date' => $this->formatDate($training->end_date),
                'is_completed' => (bool) ($training->is_completed ?? false),
                'description' => $training->description ?? '',
                'technologies' => $training->technologies ?? [],
            ])->values()->toArray(),
            'interests' => $user->interests->map(fn($interest) => [
                'name' => $interest->name,
                'category' => $interest->category ?? 'other',
                'level' => $interest->level ?? 1,
                'description' => $interest->description ?? '',
            ])->values()->toArray(),
        ];
    }

    /**
     * ✅ دالة آمنة لتنسيق التواريخ لجميع الحقول
     */
    private function formatDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof \Carbon\Carbon || $date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }
}
