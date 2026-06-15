<?php

namespace App\Services;

use App\Models\User;

class CvBuilderService
{
    public function build(User $user): array
    {
        $user->load([
            'profile',
            'skills',
            'experiences',
            'educations',
            'projects',
            'certifications',
            'trainings',
            'interests'
        ]);

        return [
            'header' => $this->header($user),
            'summary' => $this->summary($user),
            'skills' => $this->skills($user),
            'experience' => $this->experience($user),
            'education' => $user->educations->toArray(),
            'projects' => $user->projects->toArray(),
            'certifications' => $user->certifications->toArray(),
            'trainings' => $this->trainings($user),
            'interests' => $this->interests($user),
        ];
    }

    private function header(User $user): array
    {
        return [
            'name' => $user->profile?->full_name ?? $user->name,
            'title' => $user->profile?->headline ?? '',
            'contact' => [
                'email' => $user->email,
                'phone' => $user->profile?->phone ?? '',
                'location' => trim(
                    ($user->profile?->city ?? '') . ', ' . ($user->profile?->country ?? '')
                ),
                'linkedin' => $user->profile?->linkedin ?? '',
                'github' => $user->profile?->github ?? '',
            ],
        ];
    }

    private function summary(User $user): string
    {
        $summary = trim($user->profile?->summary ?? '');

        return $summary !== '' ? $summary : '';
    }

    private function skills(User $user): array
    {
        return [
            'all' => $this->normalize($user->skills->pluck('name')->toArray()),

            'technical' => $this->normalize(
                $user->skills->where('type', 'technical')->pluck('name')->toArray()
            ),

            'tools' => $this->normalize(
                $user->skills->where('type', 'tool')->pluck('name')->toArray()
            ),

            'languages' => $this->normalize(
                $user->skills->where('type', 'language')->pluck('name')->toArray()
            ),

            'soft_skills' => $this->normalize(
                $user->skills->where('type', 'soft_skill')->pluck('name')->toArray()
            ),
        ];
    }

    private function experience(User $user): array
    {
        return $user->experiences->map(function ($e) {
            return [
                'title' => $e->position ?? '',
                'company' => $e->company ?? '',
                'highlights' => $this->normalize([
                    $e->description ?? '',
                ]),
            ];
        })->toArray();
    }

    private function trainings(User $user): array
    {
        return $user->trainings
            ->unique(fn($t) => strtolower($t->title . '_' . $t->provider))
            ->map(fn($t) => [
                'title' => $t->title,
                'provider' => $t->provider,
                'start_date' => $t->start_date,
                'end_date' => $t->end_date,
                'is_completed' => (bool) $t->is_completed,
            ])
            ->values()
            ->toArray();
    }

    private function interests(User $user): array
    {
        $grouped = [];

        foreach ($user->interests as $i) {
            $name = strtolower(trim($i->name));

            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'category' => $i->category,
                    'level' => $i->level,
                    'description' => $i->description ?? '',
                ];
            }  else {
        $grouped[$name]['category'] ??= $i->category;
        $grouped[$name]['level'] = max($grouped[$name]['level'], $i->level);

        if (empty($grouped[$name]['description'])) {
            $grouped[$name]['description'] = $i->description ?? '';
        }
    }
        }

        return array_values($grouped);
    }

    private function normalize(array $items): array
    {
        $clean = array_map(function ($i) {
            return strtolower(trim((string)$i));
        }, $items);

        $clean = array_filter($clean, fn($i) => $i !== '');

        return array_values(array_unique($clean));

    }
}
