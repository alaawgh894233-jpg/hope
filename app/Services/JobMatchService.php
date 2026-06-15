<?php

namespace App\Services;

class JobMatchService
{
    public function match(array $cv, string $job): array
    {
        $job = strtolower($job);

        $skills = collect($cv['skills']['all'] ?? [])
            ->map(fn($s) => strtolower($s));

        // 🔥 تحسين matching (أقوى شوي)
        $matched = $skills->filter(function ($skill) use ($job) {
            return str_contains($job, $skill) ||
                str_contains($skill, $job); // reverse check
        });

        $skillsScore = $skills->count()
            ? ($matched->count() / max($skills->count(), 1)) * 100
            : 0;

        $experienceScore = !empty($cv['experience']) ? 80 : 30;
        $educationScore = !empty($cv['education']) ? 75 : 40;
        $toolsScore = !empty($cv['skills']['tools']) ? 70 : 20;

        $matchScore = round(
            ($skillsScore * 0.5) +
            ($experienceScore * 0.3) +
            ($educationScore * 0.1) +
            ($toolsScore * 0.1)
        );

        // 🔥 missing skills (بسيطة بدون hardcoding)
        $missing = $skills
            ->filter(fn($s) => !str_contains($job, $s))
            ->values();

        return [
            'match_score' => $matchScore,

            'breakdown' => [
                'skills' => round($skillsScore),
                'experience' => $experienceScore,
                'education' => $educationScore,
                'tools' => $toolsScore,
            ],

            'strengths' => $matched->values()->unique()->values()->toArray(),

            'missing_skills' => $missing,
        ];
    }
}
