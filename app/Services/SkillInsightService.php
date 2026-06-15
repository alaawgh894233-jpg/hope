<?php

namespace App\Services;

class SkillInsightService
{
    public function enrich(array $skills, array $aiAnalysis = []): array
    {
        return [
            'skills' => $this->clean($skills),

            'domains' => $this->clean(
                $aiAnalysis['skill_domains'] ?? []
            ),

            'roles' => $this->clean(
                $aiAnalysis['job_roles'] ?? []
            ),

            'recommended_skills' => $this->clean(
                $aiAnalysis['skills']['recommended_skills'] ?? []
            ),

            'missing_skills' => $this->clean(
                $aiAnalysis['skills']['missing_skills'] ?? []
            ),

            'adjacent_skills' => $this->clean(
                $aiAnalysis['skills']['adjacent_skills'] ?? []
            ),
        ];
    }

    private function clean(array $items): array
    {
        $flat = collect($items)->flatMap(function ($i) {
            if (is_array($i)) {
                return array_values(array_filter($i, fn($v) => !is_array($v)));
            }

            return [$i];
        });

        return $flat
            ->map(function ($i) {
                if (is_array($i)) {
                    return implode(' ', array_values(array_filter($i, fn($v) => is_scalar($v))));
                }

                return strtolower(trim((string) $i));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
