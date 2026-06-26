<?php

namespace App\Services;

use App\Models\SkillSuggestion;
use App\Models\User;

class SkillSuggestionService
{
    public function __construct(
        protected AIService $ai
    ) {}

    public function suggest(
        User    $user,
        ?string $jobTitle = null,
        ?string $jobDescription = null
    ): array {
        $userSkills = $user->skills->pluck('name')->toArray();

        $payload = [
            'current_skills'  => $userSkills,
            'job_title'       => $jobTitle,
            'job_description' => $jobDescription,
        ];

        $prompt   = $this->buildPrompt($payload);
        $response = $this->ai->ask($prompt);   // ✅ الرد الحقيقي من الـ AI
        $json     = $this->safeJson($response);

        $suggestions = $json['suggestions'] ?? [];

        // ✅ حفظ في الـ DB
        foreach ($suggestions as $s) {
            if (empty($s['name'])) continue;

            SkillSuggestion::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name'    => $s['name'],
                ],
                [
                    'type'      => $s['type']     ?? 'technical',
                    'source'    => 'ai',
                    'reason'    => $s['reason']   ?? null,
                    'job_title' => $jobTitle,
                    'priority'  => $s['priority'] ?? 50,
                    'status'    => 'pending',
                ]
            );
        }

        return [
            'detected_field'  => $json['detected_field'] ?? null,
            'suggestions'     => $suggestions,
            'total'           => count($suggestions),
            'raw_ai_response' => $response,
        ];
    }

    // ══════════════════════════════════════════════════
    //  ✅ Prompt محسّن — يقترح حسب أي مجال
    // ══════════════════════════════════════════════════
    private function buildPrompt(array $data): string
    {
        $skills   = !empty($data['current_skills']) ? implode(', ', $data['current_skills']) : 'none';
        $jobTitle = $data['job_title']       ?? 'not specified';
        $jobDesc  = $data['job_description'] ?? 'not specified';

        return <<<PROMPT
You are an expert Career Skill Recommendation Engine.

═══════════════════════════════════════════
TASK
═══════════════════════════════════════════
Step 1: Identify the user's PRIMARY FIELD based on their existing skills.
Step 2: Suggest skills ONLY within that same field.

═══════════════════════════════════════════
USER CONTEXT
═══════════════════════════════════════════
- Current Skills: {$skills}
- Target Job Title: {$jobTitle}
- Job Description: {$jobDesc}

═══════════════════════════════════════════
FIELD DETECTION EXAMPLES
═══════════════════════════════════════════
- "Photoshop" / "Illustrator" / "Figma"  → field = Graphic/UI Design
- "Laravel" / "Django" / "Node.js"       → field = Backend Development
- "React" / "Vue" / "Angular"            → field = Frontend Development
- "Excel" / "Power BI" / "Tableau"       → field = Data Analysis
- "Premiere" / "After Effects"           → field = Video Editing
- "SEO" / "Google Ads"                   → field = Digital Marketing
- "AutoCAD" / "Revit"                    → field = Architecture
- "Cisco" / "Linux" / "AWS"              → field = DevOps/Networking

═══════════════════════════════════════════
STRICT RULES
═══════════════════════════════════════════
1. ❌ NEVER suggest programming skills to a designer.
2. ❌ NEVER suggest design skills to a programmer.
3. ❌ NEVER mix unrelated fields (e.g. no SQL for Photoshop user).
4. ✅ ALL suggestions must belong to the SAME field as the user's skills.
5. ❌ DO NOT repeat existing skills.
6. ✅ Suggest 5-10 closely related skills only.
7. ✅ Rank by relevance (priority 1-100).

═══════════════════════════════════════════
EXAMPLE 1 — User knows: ["Photoshop"]
═══════════════════════════════════════════
✅ CORRECT suggestions:
   Illustrator, InDesign, Figma, Adobe XD, Sketch,
   UI/UX Design, Color Theory, Typography, Branding

❌ WRONG suggestions (DO NOT DO THIS):
   JavaScript, TypeScript, SQL, Power BI, Pivot Tables

═══════════════════════════════════════════
EXAMPLE 2 — User knows: ["Laravel"]
═══════════════════════════════════════════
✅ CORRECT suggestions:
   PHP, MySQL, Redis, Docker, PHPUnit, Livewire, Eloquent

❌ WRONG suggestions:
   Photoshop, Figma, After Effects

═══════════════════════════════════════════
EXAMPLE 3 — User knows: ["Excel"]
═══════════════════════════════════════════
✅ CORRECT suggestions:
   Power BI, Tableau, SQL, Pivot Tables, VBA, Google Sheets, Data Analysis

❌ WRONG suggestions:
   Laravel, Photoshop, React

═══════════════════════════════════════════
OUTPUT (JSON ONLY — no markdown, no comments)
═══════════════════════════════════════════
{
  "detected_field": "Graphic/UI Design",
  "suggestions": [
    {
      "name": "Illustrator",
      "type": "tool",
      "reason": "Industry-standard vector graphics tool used alongside Photoshop",
      "priority": 95
    }
  ]
}

Valid "type" values: technical, tool, language, soft_skill
PROMPT;

    }

    // ══════════════════════════════════════════════════
    //  ✅ استخراج JSON بأمان
    // ══════════════════════════════════════════════════
    private function safeJson(string $text): array
    {
        // إزالة markdown code fences
        $text = preg_replace('/```json|```/', '', $text);

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false) {
            return [];
        }

        $json = substr($text, $start, $end - $start + 1);
        return json_decode($json, true) ?? [];
    }
}
