<?php

namespace App\Services;

use JetBrains\PhpStorm\ArrayShape;

/**
 * ✅ حساب ATS Score عام لكل المهن (بدون تقنيات ثابتة)
 */
class GenericAtsScoreService
{
    public function __construct(
        protected JobKeywordExtractorService $keywordExtractor
    ) {}

    /**
     * حساب ATS Score
     */
    #[ArrayShape([
        'score' => 'int',
        'breakdown' => 'array',
        'matched_keywords' => 'array',
        'missing_keywords' => 'array'
    ])]
    public function calculate(array $cv, string $jobTitle = '', string $jobDescription = ''): array
    {
        $score = 0;
        $breakdown = [
            'keyword_match' => 0,
            'experience_depth' => 0,
            'structure_completeness' => 0,
            'summary_quality' => 0,
            'practical_evidence' => 0,
        ];

        // جمع كل مصطلحات CV
        $cvTerms = $this->collectCvTerms($cv);

        // استخراج keywords من الوظيفة
        $jobKeywords = $this->keywordExtractor->extract($jobTitle, $jobDescription);

        $matchResult = !empty($jobKeywords)
            ? $this->keywordExtractor->matchAgainstCv($jobKeywords, $cvTerms)
            : $this->fallbackKeywordDensity($cv);

        // 1️⃣ تطابق الكلمات المفتاحية (40%)
        $keywordScore = (float) ($matchResult['score'] ?? 0);
        $score += $keywordScore * 0.40;
        $breakdown['keyword_match'] = (int) round($keywordScore);

        // 2️⃣ عمق الخبرة (20%)
        $expCount = count($cv['experience'] ?? []);
        $experienceScore = match (true) {
            $expCount >= 6 => 100,
            $expCount >= 4 => 80,
            $expCount >= 2 => 60,
            $expCount >= 1 => 35,
            default => 0,
        };
        $score += $experienceScore * 0.20;
        $breakdown['experience_depth'] = $experienceScore;

        // 3️⃣ اكتمال البنية (20%)
        $structureScore = $this->structureCompleteness($cv);
        $score += $structureScore * 0.20;
        $breakdown['structure_completeness'] = (int) round($structureScore);

        // 4️⃣ جودة الملخص (10%)
        $summaryScore = $this->summaryQuality($cv['summary'] ?? '');
        $score += $summaryScore * 0.10;
        $breakdown['summary_quality'] = (int) round($summaryScore);

        // 5️⃣ الأدلة العملية (10%)
        $evidenceScore = $this->practicalEvidence($cv);
        $score += $evidenceScore * 0.10;
        $breakdown['practical_evidence'] = (int) round($evidenceScore);

        $finalScore = (int) round(max(0, min(100, $score)));

        return [
            'score' => $finalScore,
            'breakdown' => $breakdown,
            'matched_keywords' => $matchResult['matched'] ?? [],
            'missing_keywords' => $matchResult['missing'] ?? [],
        ];
    }

    /**
     * جمع كل المصطلحات من CV
     */
    private function collectCvTerms(array $cv): array
    {
        // ✅ ابدأ مباشرة بالمهارات
        $terms = $cv['skills']['all'] ?? [];

        // الخبرات
        foreach (($cv['experience'] ?? []) as $exp) {
            if (!empty($exp['title'])) {
                $terms[] = $exp['title'];
            }
            if (!empty($exp['company'])) {
                $terms[] = $exp['company'];
            }

            foreach (($exp['highlights'] ?? []) as $highlight) {
                $words = preg_split('/\s+/u', mb_strtolower((string) $highlight));
                if ($words !== false) {
                    $terms = array_merge($terms, $words);
                }
            }

            if (!empty($exp['technologies'])) {
                $terms = array_merge($terms, $exp['technologies']);
            }
        }

        // المشاريع
        foreach (($cv['projects'] ?? []) as $project) {
            if (!empty($project['title'])) {
                $terms[] = $project['title'];
            }
            if (!empty($project['technologies'])) {
                $terms = array_merge($terms, $project['technologies']);
            }
        }

        // الشهادات
        foreach (($cv['certifications'] ?? []) as $cert) {
            if (!empty($cert['name'])) {
                $terms[] = $cert['name'];
            }
            if (!empty($cert['issuer'])) {
                $terms[] = $cert['issuer'];
            }
        }

        // تنظيف وتوحيد
        return array_values(array_unique(array_filter(
            array_map(fn($t) => mb_strtolower(trim((string) $t)), $terms),
            fn($t) => mb_strlen($t) > 2
        )));
    }

    /**
     * في حال عدم وجود job description
     */
    #[ArrayShape(['matched' => 'array', 'missing' => 'array', 'score' => 'int'])]
    private function fallbackKeywordDensity(array $cv): array
    {
        $skillsCount = count($cv['skills']['all'] ?? []);
        $score = min(100, $skillsCount * 12);

        return [
            'matched' => [],
            'missing' => [],
            'score' => $score
        ];
    }

    /**
     * اكتمال البنية
     */
    private function structureCompleteness(array $cv): float
    {
        $checks = [
            !empty(trim($cv['summary'] ?? '')),
            !empty($cv['skills']['all'] ?? []),
            !empty($cv['experience'] ?? []),
            !empty($cv['education'] ?? []),
            !empty(trim($cv['header']['contact']['email'] ?? '')),
            !empty(trim($cv['header']['contact']['phone'] ?? '')),
        ];

        $present = count(array_filter($checks));
        $total = count($checks);

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }

    /**
     * جودة الملخص
     */
    private function summaryQuality(string $summary): float
    {
        $len = mb_strlen(trim($summary));

        return match (true) {
            $len >= 250 => 100.0,
            $len >= 120 => 75.0,
            $len >= 60 => 45.0,
            $len > 0 => 20.0,
            default => 0.0,
        };
    }

    /**
     * الأدلة العملية (مشاريع + شهادات)
     */
    private function practicalEvidence(array $cv): float
    {
        $projects = count($cv['projects'] ?? []);
        $certifications = count($cv['certifications'] ?? []);

        $projectScore = min(60, $projects * 20);
        $certScore = min(40, $certifications * 20);

        return min(100.0, $projectScore + $certScore);
    }
}
