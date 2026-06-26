<?php

namespace App\Services;

use App\Models\CvAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CvAnalysisService
{
    public function __construct(
        protected AIService $ai,
        protected JobMatchService $matcher,
        protected CvBuilderService $cvBuilder,
        protected SkillInsightService $skillInsight,
        protected CvIntegrityService $integrity,
        protected GenericAtsScoreService $genericAts,
    ) {}

    /**
     * ✅ التحليل الكامل للـ CV - عام لجميع المهن
     */
    public function analyze(
        User $user,
        ?string $jobTitle = null,
        ?string $jobDescription = null,
        ?string $company = 'general',
        ?array $extractedCv = null,
        bool $mergeWithProfile = false,
    ): array {
        // 1️⃣ بناء CV حسب المصدر
        $cv = $this->cvBuilder->build($user);

        // ✅ normalize skills.all من كل sub-keys للملفات المرفوعة
        if ($extractedCv !== null) {
            $cv['skills']['all'] = array_values(array_unique(array_filter(array_merge(
                $cv['skills']['all']         ?? [],
                $cv['skills']['technical']   ?? [],
                $cv['skills']['tools']       ?? [],
                $cv['skills']['soft_skills'] ?? [],
                $cv['skills']['languages']   ?? [],
            ))));
            if ($mergeWithProfile) {
                $cv = $this->mergeWithProfile($cv, $extractedCv);
            } else {
                $cv = $extractedCv;
            }
        }

        // 2️⃣ بناء الـ prompt مع قواعد صارمة
        $prompt = $this->buildPrompt($cv, $jobTitle, $jobDescription);
        $aiResponse = $this->ai->ask($prompt);
        $ai = $this->safeJson($aiResponse);

        $aiAnalysis = $ai['analysis'] ?? [];
        $aiCv       = $ai['cv'] ?? [];

        // ✅ جمع كل المصطلحات الحقيقية من CV
        $realSkills = collect($cv['skills']['all'] ?? [])
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $allEvidencedTerms = collect($realSkills);
        foreach ($cv['experience'] ?? [] as $exp) {
            foreach (($exp['technologies'] ?? []) as $tech) {
                $allEvidencedTerms->push(mb_strtolower(trim((string) $tech)));
            }
            $allEvidencedTerms->push(mb_strtolower($exp['title'] ?? ''));
        }
        foreach ($cv['projects'] ?? [] as $proj) {
            foreach (($proj['technologies'] ?? []) as $tech) {
                $allEvidencedTerms->push(mb_strtolower(trim((string) $tech)));
            }
        }
        $allEvidencedTerms = $allEvidencedTerms
            ->filter(fn($t) => mb_strlen($t) > 2)
            ->unique()
            ->values()
            ->toArray();

        // ✅ فلترة job_roles
        $aiAnalysis['job_roles'] = $this->filterJobRolesByActualTerms(
            $aiAnalysis['job_roles'] ?? [],
            $allEvidencedTerms
        );

        // 3️⃣ دمج تحسينات الـ summary
        $finalCv    = $cv;
        $aiSummary  = trim($aiCv['summary'] ?? '');
        if (mb_strlen($aiSummary) >= 30 && !$this->containsUnfoundedNumbers($aiSummary, $cv)) {
            $finalCv['summary'] = $aiSummary;
        }

        // 4️⃣ معالجة المهارات
        $aiRecommended = collect(($aiAnalysis['skills'] ?? [])['recommended_skills'] ?? [])
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $skillInsights = $this->skillInsight->enrich($realSkills, $aiAnalysis);

        $recommended = collect(array_merge($aiRecommended, $skillInsights['recommended_skills'] ?? []))
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->reject(fn($skill) => in_array($skill, $realSkills))
            ->reject(fn($skill) => in_array($skill, array_map('mb_strtolower', $skillInsights['missing_skills'] ?? [])))
            ->values()
            ->toArray();

        if (empty($recommended)) {
            $recommended = $this->getUniversalRecommendations($realSkills);
        }

        $finalCv['skills']['all']         = $realSkills;
        $finalCv['skills']['recommended'] = $recommended;

        // 5️⃣ حساب ATS Score
        $atsResult = $this->genericAts->calculate(
            $finalCv,
            $jobTitle ?? '',
            $jobDescription ?? ''
        );
        $atsScore = $atsResult['score'];

        // 6️⃣ حساب Match Score
        $match      = $this->matcher->match($finalCv, $jobDescription ?? '');
        $matchScore = (int) ($match['match_score'] ?? 0);

        // 7️⃣ حساب Semantic Score
        $semanticScore = $this->calculateSemanticScore(
            $finalCv,
            $jobDescription ?? '',
            $matchScore,
            $atsScore
        );

        // 8️⃣ حساب سنوات الخبرة الكلية
        $totalYears      = $this->calculateTotalExperienceYears($cv);
        $experienceCount = count($cv['experience'] ?? []);
        $projectCount    = count($cv['projects'] ?? []);
        $certCount       = count($cv['certifications'] ?? []);

        // 9️⃣ حساب seniority
        $calculatedSeniority = $this->calculateSeniorityFromEvidence($totalYears, $experienceCount, $projectCount);

        // 🔟 Job Readiness
        $aiJobReadiness      = (int) ($aiAnalysis['job_readiness_score'] ?? 0);
        $maxPossibleReadiness = (int) min(100,
            (count($realSkills) * 4) +
            ($experienceCount * 15) +
            ($projectCount * 8) +
            ($certCount * 8) +
            min(25, $totalYears * 5)
        );
        $jobReadiness = min($aiJobReadiness, $maxPossibleReadiness);
        $jobReadiness = max(0, $jobReadiness);

        // 1️⃣1️⃣ Market Fit
        $marketFitScore = ($atsScore * 0.4) + ($semanticScore * 0.4) + ($jobReadiness * 0.2);
        $marketFit = match (true) {
            $marketFitScore >= 80 => 'high',
            $marketFitScore >= 55 => 'medium',
            default               => 'low',
        };

        // 1️⃣2️⃣ Final Score
        $finalScore = round(
            ($atsScore * 0.4) + ($semanticScore * 0.4) + ($matchScore * 0.2),
            2
        );

        // ✅ FIX: استخراج missing_market_skills بأمان مع fallback فارغ
        $missingMarketSkills = is_array($aiAnalysis['missing_market_skills'] ?? null)
            ? $aiAnalysis['missing_market_skills']
            : [];

        // 1️⃣3️⃣ نقاط الضعف
        $realWeaknesses = collect(array_merge(
            $aiAnalysis['weaknesses'] ?? [],
            $missingMarketSkills,
            $skillInsights['missing_skills'] ?? []
        ))
            ->map(fn($s) => mb_strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // 1️⃣4️⃣ التحسينات المقترحة
        $improvements = array_values(array_unique(array_merge(
            $aiAnalysis['improvements'] ?? [],
            [
                'Improve ATS keyword optimization for target roles',
                'Add quantifiable achievements and metrics to experience',
                'Strengthen alignment with target job requirements',
            ]
        )));

        // 1️⃣5️⃣ Career paths
        $careerPaths = $this->buildUniversalCareerPaths(
            $aiAnalysis['career_paths'] ?? [],
            $allEvidencedTerms,
            $calculatedSeniority,
            $cv['header']['title'] ?? ''
        );

        // 1️⃣6️⃣ Strengths
        $strengths = $this->buildEvidenceBasedStrengths($aiAnalysis['strengths'] ?? [], $cv);

        // 1️⃣7️⃣ البناء النهائي للتحليل
        $analysis = [
            'ats_score'              => $atsScore,
            'match_score'            => $matchScore,
            'semantic_score'         => round($semanticScore, 2),
            'final_score'            => $finalScore,
            'job_readiness_score'    => $jobReadiness,
            'total_experience_years' => round($totalYears, 1),
            'market_fit'             => $marketFit,
            'seniority_level'        => $calculatedSeniority,
            'career_paths'           => $careerPaths,
            'strengths'              => $strengths,
            'weaknesses'             => $realWeaknesses,
            'improvements'           => $improvements,
            'job_roles'              => $aiAnalysis['job_roles'] ?? [],
            'market_intelligence'    => is_array($aiAnalysis['market_intelligence'] ?? null)
                ? $aiAnalysis['market_intelligence']
                : [],
            'skills' => [
                'all'                => $realSkills,
                'domains'            => $skillInsights['domains'] ?? [],
                'roles'              => $skillInsights['roles'] ?? [],
                'recommended_skills' => $recommended,
                'matched_keywords'   => $atsResult['matched_keywords'] ?? [],
                'missing_keywords'   => $atsResult['missing_keywords'] ?? [],
                'adjacent_skills'    => $skillInsights['adjacent_skills'] ?? [],
            ],
            'match_breakdown' => $match['breakdown'] ?? [],
        ];

        // 1️⃣8️⃣ حفظ التحليل في قاعدة البيانات
        try {
            CvAnalysis::create([
                'user_id'         => $user->id,
                'type'            => 'cv_analysis',
                'cv_snapshot'     => $cv,
                'cv_final'        => $finalCv,
                'ats_score'       => $analysis['ats_score'],
                'match_score'     => $analysis['match_score'],
                'final_score'     => $analysis['final_score'],
                'job_title'       => $jobTitle,
                'job_description' => $jobDescription,
                'company'         => $company,
                'strengths'       => $strengths,
                'weaknesses'      => $realWeaknesses,
                'suggestions'     => $improvements,
                'source'          => 'hybrid_ai_system_universal',
                'model'           => 'llama-3.1-8b-instant',
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to save CV analysis: ' . $e->getMessage());
        }

        return [
            'optimized_cv' => $finalCv,
            'analysis'     => $analysis,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * ✅ دمج بيانات الملف مع البروفايل المحفوظ
     */
    private function mergeWithProfile(array $profile, array $extracted): array
    {
        // ✅ جمع كل مهارات الملف من كل الـ sub-keys الممكنة
        $extractedAllSkills = array_values(array_unique(array_filter(array_merge(
            $extracted['skills']['all']        ?? [],
            $extracted['skills']['technical']  ?? [],
            $extracted['skills']['tools']      ?? [],
            $extracted['skills']['soft_skills'] ?? [],
            $extracted['skills']['languages']  ?? [],
        ))));

        $profileAllSkills = $profile['skills']['all'] ?? [];

        return [
            'header'         => !empty($extracted['header']) ? $extracted['header'] : ($profile['header'] ?? []),
            'summary'        => !empty($extracted['summary']) ? $extracted['summary'] : ($profile['summary'] ?? ''),
            'skills'         => [
                'all' => array_values(array_unique(array_merge(
                    $profileAllSkills,
                    $extractedAllSkills,
                ))),
            ],
            'experience'     => array_merge($profile['experience'] ?? [], $extracted['experience'] ?? []),
            'education'      => array_merge($profile['education'] ?? [], $extracted['education'] ?? []),
            'projects'       => array_merge($profile['projects'] ?? [], $extracted['projects'] ?? []),
            'certifications' => array_merge($profile['certifications'] ?? [], $extracted['certifications'] ?? []),
            'languages'      => array_merge($profile['languages'] ?? [], $extracted['languages'] ?? []),
        ];
    }
    /**
     * ✅ فلترة job_roles بناءً على المصطلحات الحقيقية فقط
     */
    private function filterJobRolesByActualTerms(array $jobRoles, array $realTerms): array
    {
        return array_values(array_filter($jobRoles, function ($role) use ($realTerms) {
            $roleText = mb_strtolower(
                ($role['title'] ?? '') . ' ' .
                ($role['description'] ?? '') . ' ' .
                ($role['industry'] ?? '')
            );
            foreach ($realTerms as $term) {
                if (mb_strlen($term) >= 3 && str_contains($roleText, $term)) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * ✅ حساب السنيوريتي بناء على الأدلة فقط
     */
    private function calculateSeniorityFromEvidence(float $totalYears, int $expCount, int $projectCount): string
    {
        if ($totalYears >= 5 || ($expCount >= 3 && $projectCount >= 3)) {
            return 'senior';
        }
        if ($totalYears >= 2 || ($expCount >= 1 && $projectCount >= 1)) {
            return 'mid';
        }
        return 'junior';
    }

    /**
     * ✅ حساب مجموع سنوات الخبرة
     */
    private function calculateTotalExperienceYears(array $cv): float
    {
        $total = 0;
        foreach ($cv['experience'] ?? [] as $exp) {
            if (empty($exp['start_date'])) continue;
            try {
                $start  = new \DateTime($exp['start_date']);
                $end    = !empty($exp['end_date']) ? new \DateTime($exp['end_date']) : new \DateTime();
                $total += ($end->getTimestamp() - $start->getTimestamp()) / (365.25 * 24 * 60 * 60);
            } catch (\Exception) {
                continue;
            }
        }
        return max(0, round($total, 1));
    }

    /**
     * ✅ التحقق من عدم وجود أرقام مخترعة في النص
     */
    private function containsUnfoundedNumbers(string $candidateText, array $originalCv): bool
    {
        preg_match_all('/\d+/', $candidateText, $candidateNums);
        $candidateNums = array_unique($candidateNums[0] ?? []);

        if (empty($candidateNums)) return false;

        preg_match_all('/\d+/', json_encode($originalCv), $originalNums);
        $originalNums = array_unique($originalNums[0] ?? []);

        foreach ($candidateNums as $num) {
            if ((int) $num <= 10) continue;
            if (!in_array($num, $originalNums, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * ✅ نقاط قوة مبنية على أدلة حقيقية
     */
    private function buildEvidenceBasedStrengths(array $aiStrengths, array $cv): array
    {
        $strengths = [];

        if (count($cv['experience'] ?? []) > 0) {
            $strengths[] = 'Has professional work experience in the field';
        }
        if (count($cv['education'] ?? []) > 0) {
            $strengths[] = 'Relevant educational background';
        }
        if (count($cv['certifications'] ?? []) > 0) {
            $strengths[] = 'Holds professional certifications';
        }
        if (count($cv['projects'] ?? []) > 0) {
            $strengths[] = 'Practical project experience';
        }
        if (count($cv['skills']['all'] ?? []) >= 5) {
            $strengths[] = 'Diverse skill set';
        }

        foreach ($aiStrengths as $aiStrength) {
            $text = mb_strtolower((string) $aiStrength);
            foreach ($cv['skills']['all'] ?? [] as $skill) {
                if (str_contains($text, mb_strtolower((string) $skill))) {
                    $strengths[] = $aiStrength;
                    break;
                }
            }
        }

        return array_values(array_unique($strengths));
    }

    /**
     * ✅ مسارات وظيفية عامة
     */
    private function buildUniversalCareerPaths(
        array $aiCareerPaths,
        array $realTerms,
        string $seniority,
        string $currentTitle
    ): array {
        $filtered = [];
        foreach ($aiCareerPaths as $path) {
            $pathText = is_string($path) ? mb_strtolower($path) : mb_strtolower(json_encode($path));
            foreach ($realTerms as $term) {
                if (mb_strlen($term) >= 3 && str_contains($pathText, $term)) {
                    $filtered[] = $path;
                    break;
                }
            }
        }

        if (empty($filtered)) {
            $title  = $currentTitle ?: 'Professional';
            $levels = [
                'junior' => ['Entry-level ' . $title, 'Mid-level ' . $title, 'Senior ' . $title],
                'mid'    => ['Mid-level ' . $title, 'Senior ' . $title, 'Lead ' . $title],
                'senior' => ['Senior ' . $title, 'Lead ' . $title, 'Subject Matter Expert'],
            ];
            $filtered = $levels[$seniority] ?? [
                    'Entry-level Professional',
                    'Mid-level Professional',
                    'Senior Professional',
                ];
        }

        return array_slice($filtered, 0, 3);
    }

    /**
     * ✅ توصيات عامة لكل المهن
     */
    private function getUniversalRecommendations(array $existingSkills): array
    {
        $existingLower = array_map('mb_strtolower', $existingSkills);

        $universalRecommendations = [
            'quantifiable achievements in experience descriptions',
            'professional certifications in your field',
            'industry-standard software proficiency',
            'clear professional summary tailored to target roles',
            'time management and organization skills',
            'team collaboration experience',
            'ongoing professional development',
            'improvement of technical writing skills',
            'project documentation skills',
            'client communication skills',
            'leadership examples for senior roles',
        ];

        $result = [];
        foreach ($universalRecommendations as $rec) {
            if (!in_array($rec, $existingLower)) {
                $result[] = $rec;
                if (count($result) >= 5) break;
            }
        }

        return $result;
    }

    /**
     * ✅ حساب Semantic Score عام
     */
    private function calculateSemanticScore(
        array $cv,
        string $jobDescription,
        float $matchScore,
        float $atsScore
    ): float {
        if (empty(trim($jobDescription))) {
            return min(100, ($matchScore * 0.6) + ($atsScore * 0.4));
        }

        $cvParts = [
            ...($cv['skills']['all'] ?? []),
            $cv['summary'] ?? '',
        ];

        foreach ($cv['experience'] ?? [] as $exp) {
            $cvParts[] = $exp['title'] ?? '';
            $cvParts[] = $exp['company'] ?? '';
            $cvParts[] = implode(' ', $exp['highlights'] ?? []);
            $cvParts[] = implode(' ', $exp['technologies'] ?? []);
        }

        foreach ($cv['projects'] ?? [] as $proj) {
            $cvParts[] = $proj['title'] ?? '';
            $cvParts[] = $proj['description'] ?? '';
            $cvParts[] = implode(' ', $proj['technologies'] ?? []);
        }

        foreach ($cv['certifications'] ?? [] as $cert) {
            $cvParts[] = $cert['name'] ?? '';
        }

        $cvText  = mb_strtolower(implode(' ', array_filter($cvParts)));
        $jobText = mb_strtolower($jobDescription);

        $stopWords = ['and','or','the','with','for','of','in','to','a','an','is','are','will',
            'have','has','we','you','our','your','years','experience','able','must',
            'should','looking','need','required'];

        $jobWords = array_values(array_unique(array_filter(
            preg_split('/[\s,.!?;:()\[\]{}"\'\-]+/u', $jobText),
            fn($w) => mb_strlen($w) > 3 && !in_array($w, $stopWords)
        )));

        if (empty($jobWords)) return 0;

        $matchedCount = 0;
        foreach ($jobWords as $word) {
            if (str_contains($cvText, $word)) {
                $matchedCount++;
            }
        }

        return min(100, max(0, ($matchedCount / count($jobWords)) * 100));
    }

    /**
     * ✅ بناء Prompt عام لكل المهن
     */
    private function buildPrompt(array $cv, ?string $jobTitle, ?string $jobDescription): string
    {
        $compactCv = [
            'name'          => $cv['header']['name'] ?? '',
            'current_title' => $cv['header']['title'] ?? '',
            'summary'       => $cv['summary'] ?? '',
            'skills_all'    => $cv['skills']['all'] ?? [],
            'experience'    => array_map(fn($e) => [
                'title'        => $e['title'] ?? '',
                'company'      => $e['company'] ?? '',
                'years'        => $this->calculateYearsDiff($e['start_date'] ?? null, $e['end_date'] ?? null),
                'highlights'   => $e['highlights'] ?? [],
                'technologies' => $e['technologies'] ?? [],
            ], $cv['experience'] ?? []),
            'education'      => $cv['education'] ?? [],
            'projects'       => $cv['projects'] ?? [],
            'certifications' => $cv['certifications'] ?? [],
        ];

        $skillsList = implode(', ', $cv['skills']['all'] ?? []);

        return "You are a universal ATS CV analyzer for ALL PROFESSIONS (works for engineering, accounting, medicine, business, education, trades, design, technology, etc.)

━━━━━━━━━━━━━━━━━━
CRITICAL NON-NEGOTIABLE RULES (THESE OVERRIDE ALL OTHER INSTRUCTIONS):
━━━━━━━━━━━━━━━━━━
1. NEVER INVENT ANY FACTS, SKILLS, OR EXPERIENCE THAT DO NOT EXPLICITLY EXIST IN THE CV.
2. THE CV HAS THESE SKILLS ONLY: [{$skillsList}]
   → You MUST NOT suggest or mention ANY skills/tools/technologies NOT in this list.
   → Example: If the list does NOT contain 'Java', 'React', 'Python', 'machine learning', 'data science', you MUST NEVER MENTION THEM.
3. SENIORITY MUST BE BASED ONLY ON EVIDENCE:
   - junior: less than 2 years experience OR fewer than 2 projects
   - mid: 2-5 years OR multiple real projects
   - senior: 5+ years OR clear leadership/architecture experience
   → DEFAULT TO JUNIOR IN CASE OF DOUBT. NEVER UPGRADE WITHOUT CLEAR EVIDENCE.
4. JOB ROLES MUST MATCH THE CV'S DOMAIN EXACTLY.
5. RECOMMENDED SKILLS MUST BE DIRECTLY RELATED TO THE EXISTING SKILLS.
6. ALL SCORES MUST BE REALISTIC AND CONSERVATIVE. DO NOT INFLATE SCORES.
7. RETURN ONLY VALID JSON. NO MARKDOWN, NO EXPLANATIONS, NO COMMENTS.

CV DATA (DO NOT ADD ANY FACTS TO THIS):
" . json_encode($compactCv, JSON_UNESCAPED_UNICODE) . "

TARGET JOB TITLE: " . ($jobTitle ?: 'General CV improvement') . "
TARGET JOB DESCRIPTION: " . ($jobDescription ?: 'General professional CV improvement across all fields') . "

━━━━━━━━━━━━━━━━━━
RETURN THIS EXACT JSON STRUCTURE:
━━━━━━━━━━━━━━━━━━
{
  \"cv\": {
    \"summary\": \"Professional summary (improve wording only, keep all facts, do not add numbers or facts not present)\",
    \"skills\": { \"all\": [], \"recommended\": [] }
  },
  \"analysis\": {
    \"ats_score\": 0,
    \"match_score\": 0,
    \"job_readiness_score\": 0,
    \"market_fit\": \"low|medium|high\",
    \"seniority_level\": \"junior|mid|senior\",
    \"strengths\": [],
    \"weaknesses\": [],
    \"improvements\": [],
    \"missing_market_skills\": [],
    \"job_roles\": [{ \"title\": \"\", \"industry\": \"\", \"seniority\": \"\", \"description\": \"\" }],
    \"career_paths\": [],
    \"skills\": {
      \"strong_skills\": [],
      \"missing_skills\": [],
      \"recommended_skills\": [],
      \"adjacent_skills\": []
    },
    \"market_intelligence\": {
      \"industry_fit\": \"\",
      \"top_matching_domains\": [],
      \"market_competitiveness\": \"low|medium|high\",
      \"salary_level\": \"low|medium|high\",
      \"learning_priority\": [],
      \"top_company_fit\": []
    }
  }
}

JSON ONLY - NO OTHER TEXT.
";
    }

    private function calculateYearsDiff(?string $start, ?string $end): float
    {
        if (!$start) return 0;
        try {
            $startTs = strtotime($start);
            $endTs   = $end ? strtotime($end) : time();
            return max(0, round(($endTs - $startTs) / (365.25 * 24 * 60 * 60), 1));
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * ✅ فك JSON بأمان
     */
    private function safeJson(string $text): array
    {
        $text  = trim($text);
        $text  = preg_replace('/```json|```/', '', $text);
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            Log::warning('AI response had no valid JSON structure');
            return $this->fallbackJson();
        }

        $jsonStr = substr($text, $start, $end - $start + 1);

        try {
            $decoded = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                return $this->fallbackJson();
            }
            return $this->validateAiSchema($decoded);
        } catch (\JsonException $e) {
            Log::warning('JSON decode failed: ' . $e->getMessage());
            return $this->fallbackJson();
        }
    }

    /**
     * ✅ تصديق schema القادم من AI
     */
    private function validateAiSchema(array $data): array
    {
        return [
            'cv' => [
                'summary' => (string) ($data['cv']['summary'] ?? ''),
                'skills'  => [
                    'all'         => is_array($data['cv']['skills']['all'] ?? null)
                        ? $data['cv']['skills']['all'] : [],
                    'recommended' => is_array($data['cv']['skills']['recommended'] ?? null)
                        ? $data['cv']['skills']['recommended'] : [],
                ],
            ],
            'analysis' => [
                'ats_score'            => (int) ($data['analysis']['ats_score'] ?? 0),
                'match_score'          => (int) ($data['analysis']['match_score'] ?? 0),
                'job_readiness_score'  => (int) ($data['analysis']['job_readiness_score'] ?? 0),
                'market_fit'           => in_array($data['analysis']['market_fit'] ?? '', ['low', 'medium', 'high'])
                    ? $data['analysis']['market_fit'] : 'low',
                'seniority_level'      => in_array($data['analysis']['seniority_level'] ?? '', ['junior', 'mid', 'senior'])
                    ? $data['analysis']['seniority_level'] : 'junior',
                'strengths'            => is_array($data['analysis']['strengths'] ?? null)
                    ? $data['analysis']['strengths'] : [],
                'weaknesses'           => is_array($data['analysis']['weaknesses'] ?? null)
                    ? $data['analysis']['weaknesses'] : [],
                'improvements'         => is_array($data['analysis']['improvements'] ?? null)
                    ? $data['analysis']['improvements'] : [],
                // ✅ FIX: missing_market_skills مضمونة دائماً
                'missing_market_skills' => is_array($data['analysis']['missing_market_skills'] ?? null)
                    ? $data['analysis']['missing_market_skills'] : [],
                'job_roles'            => is_array($data['analysis']['job_roles'] ?? null)
                    ? $data['analysis']['job_roles'] : [],
                'career_paths'         => is_array($data['analysis']['career_paths'] ?? null)
                    ? $data['analysis']['career_paths'] : [],
                'skill_domains'        => is_array($data['analysis']['skill_domains'] ?? null)
                    ? $data['analysis']['skill_domains'] : [],
                'skills'               => is_array($data['analysis']['skills'] ?? null)
                    ? $data['analysis']['skills'] : [],
                'market_intelligence'  => is_array($data['analysis']['market_intelligence'] ?? null)
                    ? $data['analysis']['market_intelligence'] : [],
            ],
        ];
    }

    /**
     * ✅ JSON احتياطي
     */
    private function fallbackJson(): array
    {
        return [
            'cv' => [
                'summary' => '',
                'skills'  => ['all' => [], 'recommended' => []],
            ],
            'analysis' => [
                'ats_score'             => 0,
                'match_score'           => 0,
                'job_readiness_score'   => 0,
                'market_fit'            => 'low',
                'seniority_level'       => 'junior',
                'strengths'             => [],
                'weaknesses'            => [],
                'improvements'          => [],
                'missing_market_skills' => [],  // ✅ FIX: موجودة هنا أيضاً
                'job_roles'             => [],
                'career_paths'          => [],
                'skill_domains'         => [],
                'skills'                => [],
                'market_intelligence'   => [],
            ],
        ];
    }
}
