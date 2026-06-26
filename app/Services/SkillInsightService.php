<?php

namespace App\Services;

class SkillInsightService
{
    /**
     * إثراء بيانات المهارات
     */
    public function enrich(array $skills, array $aiAnalysis = []): array
    {
        return [
            'skills' => $this->clean($skills),
            'domains' => $this->clean($aiAnalysis['skill_domains'] ?? []),
            'roles' => $this->clean($aiAnalysis['job_roles'] ?? []),
            'recommended_skills' => $this->clean(($aiAnalysis['skills'] ?? [])['recommended_skills'] ?? []),
            'missing_skills' => $this->clean(($aiAnalysis['skills'] ?? [])['missing_skills'] ?? []),
            'adjacent_skills' => $this->clean(($aiAnalysis['skills'] ?? [])['adjacent_skills'] ?? []),
        ];
    }

    /**
     * تنظيف وتوحيد البيانات
     */
    private function clean(array $items): array
    {
        $flat = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $flat = array_merge($flat, array_values(array_filter($item, fn($v) => !is_array($v))));
            } else {
                $flat[] = $item;
            }
        }

        $cleaned = array_map(function ($item) {
            if (is_array($item)) {
                return implode(' ', array_values(array_filter($item, fn($v) => is_scalar($v))));
            }
            return mb_strtolower(trim((string) $item));
        }, $flat);

        return array_values(array_unique(array_filter($cleaned)));
    }
}
