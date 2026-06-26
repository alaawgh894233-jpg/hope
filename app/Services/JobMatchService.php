<?php

namespace App\Services;

class JobMatchService
{
    /**
     * ✅ مطابقة CV مع وصف وظيفة
     */
    public function match(array $cv, string $jobDescription): array
    {
        $jobText = mb_strtolower(trim($jobDescription));

        // ── 1) Skills Score ──────────────────────────────
        $skillScore = $this->calculateSkillScore($cv, $jobText);

        // ── 2) Experience Score ──────────────────────────
        $expScore = $this->calculateExperienceScore($cv, $jobText);

        // ── 3) Education Score ───────────────────────────
        $eduScore = $this->calculateEducationScore($cv, $jobText);

        // ── 4) Tools Score ───────────────────────────────
        $toolsScore = $this->calculateToolsScore($cv, $jobText);

        // ── Final Score ──────────────────────────────────
        $finalScore = (int) round(
            ($skillScore   * 0.40) +
            ($expScore     * 0.35) +
            ($eduScore     * 0.15) +
            ($toolsScore   * 0.10)
        );

        return [
            'match_score' => min(100, $finalScore),
            'breakdown'   => [
                'skills'     => $skillScore,
                'experience' => $expScore,
                'education'  => $eduScore,
                'tools'      => $toolsScore,
            ],
        ];
    }

    // ══════════════════════════════════════════════════
    //  SKILLS
    // ══════════════════════════════════════════════════

    private function calculateSkillScore(array $cv, string $jobText): int
    {
        // جمع كل المهارات من كل sub-keys
        $allSkills = array_values(array_unique(array_filter(array_merge(
            $cv['skills']['all']         ?? [],
            $cv['skills']['technical']   ?? [],
            $cv['skills']['tools']       ?? [],
            $cv['skills']['soft_skills'] ?? [],
            $cv['skills']['languages']   ?? [],
        ), fn($s) => mb_strlen(trim((string)$s)) > 1)));

        if (empty($allSkills)) return 0;

        $matched = 0;

        foreach ($allSkills as $skill) {
            if ($this->skillMatchesJob($skill, $jobText)) {
                $matched++;
            }
        }

        return (int) min(100, round(($matched / count($allSkills)) * 100 * 2.5));
    }

    /**
     * ✅ تحقق إن مهارة تطابق نص الوظيفة — بطرق متعددة
     */
    private function skillMatchesJob(string $skill, string $jobText): bool
    {
        $skillLower = mb_strtolower(trim($skill));

        // 1) مطابقة مباشرة
        if (str_contains($jobText, $skillLower)) {
            return true;
        }

        // 2) مطابقة جزئية — كلمات المهارة في نص الوظيفة
        $skillWords = array_filter(
            preg_split('/[\s\(\)&\/\-,]+/', $skillLower),
            fn($w) => mb_strlen(trim($w)) > 2
        );

        foreach ($skillWords as $word) {
            if (mb_strlen($word) >= 3 && str_contains($jobText, $word)) {
                return true;
            }
        }

        // 3) مرادفات شائعة
        $synonyms = $this->getSynonyms($skillLower);
        foreach ($synonyms as $synonym) {
            if (str_contains($jobText, $synonym)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ مرادفات المهارات الشائعة
     */
    private function getSynonyms(string $skill): array
    {
        $synonymMap = [
            // PHP / Laravel
            'php'            => ['php', 'laravel', 'php developer'],
            'php (laravel)'  => ['laravel', 'php', 'backend'],
            'laravel'        => ['laravel', 'php', 'backend framework'],
            'back-end development (laravel)' => ['backend', 'back-end', 'laravel', 'php'],

            // Database
            'database design & mysql' => ['mysql', 'database', 'sql', 'db design'],
            'eloquent orm'            => ['orm', 'eloquent', 'database', 'mysql'],

            // Architecture
            'mvc architecture'        => ['mvc', 'architecture', 'design pattern'],
            'service layer architecture' => ['service layer', 'architecture', 'design'],
            'restful apis development' => ['rest', 'api', 'restful', 'rest api', 'apis'],

            // Code Quality
            'clean code principles'   => ['clean code', 'code quality', 'best practices'],
            'solid principles'        => ['solid', 'design principles', 'oop'],
            'separation of concerns'  => ['solid', 'clean code', 'architecture'],
            'code optimization & refactoring' => ['optimization', 'refactoring', 'clean code'],
            'object-oriented programming (oop)' => ['oop', 'object oriented', 'object-oriented'],

            // Auth
            'authentication & authorization' => ['auth', 'authentication', 'authorization', 'jwt', 'sanctum'],

            // Tools
            'git & github(basic)'     => ['git', 'github', 'version control'],
            'git & github'            => ['git', 'github', 'version control'],

            // Soft Skills
            'problem solving'         => ['problem solving', 'analytical', 'troubleshooting'],
            'fast learning'           => ['fast learner', 'quick learner', 'adaptable'],

            // Languages
            'english (good)'          => ['english', 'communication'],
            'arabic (native)'         => ['arabic'],
        ];

        $skillLower = mb_strtolower(trim($skill));

        return $synonymMap[$skillLower] ?? [];
    }

    // ══════════════════════════════════════════════════
    //  EXPERIENCE
    // ══════════════════════════════════════════════════

    private function calculateExperienceScore(array $cv, string $jobText): int
    {
        $experiences = $cv['experience'] ?? [];

        if (empty($experiences)) return 0;

        $totalScore = 0;
        $count      = 0;

        foreach ($experiences as $exp) {
            $score = 0;

            // عنوان الوظيفة
            $title = mb_strtolower($exp['title'] ?? $exp['position'] ?? '');
            if (!empty($title) && $this->textMatchesJob($title, $jobText)) {
                $score += 40;
            }

            // الـ highlights
            $highlights = $exp['highlights'] ?? [];
            if (!empty($highlights)) {
                $highlightText = mb_strtolower(implode(' ', $highlights));
                $matchRatio    = $this->calculateTextMatchRatio($highlightText, $jobText);
                $score        += (int) ($matchRatio * 40);
            }

            // التقنيات المستخدمة
            $techs = $exp['technologies'] ?? [];
            if (is_array($techs) && !empty($techs)) {
                $techText = mb_strtolower(implode(' ', $techs));
                if ($this->textMatchesJob($techText, $jobText)) {
                    $score += 20;
                }
            }

            $totalScore += min(100, $score);
            $count++;
        }

        return $count > 0 ? (int) round($totalScore / $count) : 0;
    }

    // ══════════════════════════════════════════════════
    //  EDUCATION
    // ══════════════════════════════════════════════════

    private function calculateEducationScore(array $cv, string $jobText): int
    {
        $educations = $cv['education'] ?? [];

        if (empty($educations)) return 30; // افتراضي لو ما في تعليم مذكور

        $score = 0;

        foreach ($educations as $edu) {
            $eduText = mb_strtolower(
                ($edu['degree'] ?? '') . ' ' .
                ($edu['field_of_study'] ?? '') . ' ' .
                ($edu['institution'] ?? '')
            );

            // وجود درجة علمية = نقطة إيجابية
            if (!empty($edu['degree'])) {
                $score += 30;
            }

            // الحقل الدراسي يطابق الوظيفة
            if (!empty($edu['field_of_study'])) {
                $field = mb_strtolower($edu['field_of_study']);
                if ($this->textMatchesJob($field, $jobText)) {
                    $score += 40;
                }
            }

            // Software Engineering / Computer Science = إيجابي لأي وظيفة تقنية
            $techFields = ['software', 'computer', 'information', 'engineering', 'technology'];
            foreach ($techFields as $field) {
                if (str_contains($eduText, $field)) {
                    $score += 20;
                    break;
                }
            }
        }

        return min(100, $score);
    }

    // ══════════════════════════════════════════════════
    //  TOOLS
    // ══════════════════════════════════════════════════

    private function calculateToolsScore(array $cv, string $jobText): int
    {
        $tools = array_merge(
            $cv['skills']['tools'] ?? [],
            // استخرج tools من all
            array_filter(
                $cv['skills']['all'] ?? [],
                fn($s) => $this->isToolSkill(mb_strtolower((string)$s))
            )
        );

        if (empty($tools)) return 0;

        $matched = 0;
        foreach ($tools as $tool) {
            if ($this->skillMatchesJob($tool, $jobText)) {
                $matched++;
            }
        }

        // لو ما في tools مطلوبة في الـ job description → نعطي نقطة محايدة
        $jobMentionsTools = $this->jobMentionsTools($jobText);
        if (!$jobMentionsTools) return 50;

        return (int) min(100, round(($matched / count($tools)) * 100 * 2));
    }

    private function isToolSkill(string $skill): bool
    {
        $toolKeywords = [
            'git', 'github', 'postman', 'composer', 'docker',
            'vs code', 'visual studio', 'phpstorm', 'mysql workbench',
            'jira', 'slack', 'trello', 'linux', 'nginx', 'apache',
        ];

        foreach ($toolKeywords as $kw) {
            if (str_contains($skill, $kw)) return true;
        }

        return false;
    }

    private function jobMentionsTools(string $jobText): bool
    {
        $toolKeywords = [
            'git', 'github', 'postman', 'docker', 'jira',
            'linux', 'nginx', 'apache', 'composer',
        ];

        foreach ($toolKeywords as $kw) {
            if (str_contains($jobText, $kw)) return true;
        }

        return false;
    }

    // ══════════════════════════════════════════════════
    //  SHARED HELPERS
    // ══════════════════════════════════════════════════

    private function textMatchesJob(string $text, string $jobText): bool
    {
        $words = array_filter(
            preg_split('/[\s\(\)&\/\-,\.]+/', mb_strtolower($text)),
            fn($w) => mb_strlen(trim($w)) > 2
        );

        foreach ($words as $word) {
            if (str_contains($jobText, $word)) return true;
        }

        return false;
    }

    private function calculateTextMatchRatio(string $text, string $jobText): float
    {
        $words = array_filter(
            preg_split('/[\s\(\)&\/\-,\.]+/', mb_strtolower($text)),
            fn($w) => mb_strlen(trim($w)) > 3
        );

        if (empty($words)) return 0;

        $matched = 0;
        foreach ($words as $word) {
            if (str_contains($jobText, $word)) $matched++;
        }

        return $matched / count($words);
    }
}
