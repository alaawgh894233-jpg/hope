<?php

namespace App\Services;

use App\Models\WorkflowRule;
use App\Models\JobApplication;

class RuleEngineService
{
    public function evaluate(JobApplication $application): array
    {
        $rules = WorkflowRule::where('workflow_id', $application->workflow_id)
            ->orderByDesc('priority')
            ->get();
        $totalScore = 0;
        $matchedRules = [];

        foreach ($rules as $rule) {

            if ($this->matchRule($application, $rule)) {

                $totalScore += $rule->score_weight ?? 0;

                $matchedRules[] = [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'weight' => $rule->score_weight ?? 0,
                    'action' => $rule->action
                ];
            }
        }

        return $this->decide($application, $totalScore, $matchedRules);
    }

    private function decide($application, int $score, array $rules): array
    {
        if ($score >= 70) {
            return [
                'decision' => 'accept',
                'score' => $score,
                'rules' => $rules
            ];
        }

        if ($score >= 40) {
            return [
                'decision' => 'move',
                'score' => $score,
                'rules' => $rules
            ];
        }

        return [
            'decision' => 'reject',
            'score' => $score,
            'rules' => $rules
        ];
    }

    private function matchRule($application, $rule): bool
    {
        $value = $this->getFieldValue($application, $rule->field);

        if ($value === null) {
            return false;
        }

        return $this->compare($value, $rule->operator, $rule->value);
    }

    private function getFieldValue($application, string $field)
    {
        return match ($field) {

            'experience_years' => $application->user->experience_years ?? 0,
            'skill_score' => $application->user->skill_score ?? 0,
            'education_level' => $application->user->education_level ?? 0,

            default => null,
        };
    }

    private function compare($left, string $operator, $right): bool
    {
        return match ($operator) {

            '>'  => $left > $right,
            '>=' => $left >= $right,
            '<'  => $left < $right,
            '<=' => $left <= $right,
            '==' => $left == $right,
            '!=' => $left != $right,

            default => false,
        };
    }
}
