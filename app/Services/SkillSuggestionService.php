<?php

namespace App\Services;

use App\Models\SkillSuggestion;
use App\Models\User;

class SkillSuggestionService
{
    public function __construct(
        protected AIService $ai
    ) {}

    public function suggest(User $user, ?string $jobTitle = null, ?string $jobDescription = null)
    {
        $userSkills = $user->skills->pluck('name')->toArray();

        $payload = [
            'current_skills' => $userSkills,
            'job_title' => $jobTitle,
            'job_description' => $jobDescription
        ];

        $prompt = $this->buildPrompt($payload);

        $response = $this->ai->ask($prompt);

        $json = $this->safeJson($response);

        // حفظ الاقتراحات بالداتا
        foreach (($json['suggestions'] ?? []) as $s) {
            SkillSuggestion::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $s['name']
                ],
                [
                    'type' => $s['type'] ?? 'technical',
                    'source' => 'ai',
                    'reason' => $s['reason'] ?? null,
                    'priority' => $s['priority'] ?? 50,
                    'status' => 'pending'
                ]
            );
        }

        return $json;
    }

    private function buildPrompt(array $data): string
    {
        return "
You are an expert ATS Skill Recommendation Engine.

TASK:
Analyze user's skills and job context and suggest missing skills ONLY.

INPUT:
" . json_encode($data) . "

RULES:
- DO NOT repeat existing skills
- DO NOT hallucinate experience
- Suggest only realistic backend-related skills
- Rank by importance

OUTPUT JSON ONLY:
{
  \"suggestions\": [
    {
      \"name\": \"Docker\",
      \"type\": \"tool\",
      \"reason\": \"Used in backend deployment\",
      \"priority\": 90
    }
  ]
}
";
    }

    private function safeJson($text)
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        return json_decode(substr($text, $start, $end - $start + 1), true) ?? [];
    }
}
