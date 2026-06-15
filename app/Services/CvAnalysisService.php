<?php

namespace App\Services;

use App\Models\CvAnalysis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CvAnalysisService
{
    public function __construct(
        protected AIService           $ai,
        protected JobMatchService     $matcher,
        protected CvBuilderService    $cvBuilder,
        protected EmbeddingService    $embedding,
        protected SkillInsightService $skillInsight,

    )
    {
    }
    public function analyze(
        User $user,
        ?string $jobTitle = null,
        ?string $jobDescription = null,
        ?string $company = 'general'
    ): array {

        $cacheKey = 'cv_analysis_' . md5(
                $user->id .
                $jobTitle .
                $jobDescription .
                $company
            );

        return Cache::remember($cacheKey, 3600, function () use (
            $user,
            $jobTitle,
            $jobDescription
        ) {


            $cv = $this->cvBuilder->build($user);

            $prompt = $this->buildPrompt($cv, $jobTitle, $jobDescription);
            $aiResponse = $this->ai->ask($prompt);
            $ai = $this->safeJson($aiResponse);

            $aiAnalysis = $ai['analysis'] ?? [];

            $realSkills = collect($cv['skills']['all'] ?? [])
                ->map(fn($s) => strtolower(trim($s)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $allowedStack = array_map('strtolower', $realSkills);
            $aiAnalysis = $ai['analysis'] ?? [];

            $allowedStack = $realSkills; // الآن صحيح
            $aiAnalysis['job_roles'] = array_values(array_filter(
                $aiAnalysis['job_roles'] ?? [],
                function ($role) use ($allowedStack) {
                    $text = strtolower(
                        ($role['title'] ?? '') . ' ' .
                        ($role['description'] ?? '')
                    );

                    foreach ($allowedStack as $skill) {
                        if (str_contains($text, $skill)) {
                            return collect($allowedStack)->contains(function ($skill) use ($text) {
                                return str_contains($text, $skill);
                            });
                        }
                    }
                    return false;
                }
            ));
            $aiCv = $ai['cv'] ?? [];


            $finalCv = $cv;

            $aiSummary = trim($aiCv['summary'] ?? '');
            if (strlen($aiSummary) >= 30) {
                $finalCv['summary'] = $aiSummary;
            }


            $realSkills = collect($cv['skills']['all'] ?? [])
                ->map(fn($s) => strtolower(trim($s)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();


            $aiRecommended = collect($aiAnalysis['skills']['recommended_skills'] ?? [])
                ->map(fn($s) => strtolower(trim($s)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();


            $skillInsights = $this->skillInsight->enrich(
                $realSkills,
                $aiAnalysis
            );

            $recommended = collect(array_merge(
                $aiRecommended,
                $skillInsights['recommended_skills'] ?? []
            ))
                ->map(fn($s) => strtolower(trim($s)))
                ->filter()
                ->unique()
                ->reject(fn($skill) =>
                in_array($skill, array_map('strtolower', $skillInsights['missing_skills'] ?? []))
                )
                ->values()
                ->toArray();


            if (empty($recommended)) {
                $recommended = ['docker', 'system design', 'ci/cd'];
            }


            $finalCv['skills']['all'] = $realSkills;
            $finalCv['skills']['recommended'] = $recommended;


            $atsScore = $this->calculateAtsScore(
                $finalCv,
                $aiAnalysis,
                $jobTitle ?? '',
                $jobDescription ?? ''
            );


            $match = $this->matcher->match(
                $finalCv,
                $jobDescription ?? ''
            );

            $matchScore = (float)($match['match_score'] ?? 0);


            $skillsText = implode(' ', $realSkills);
            $summaryText = $finalCv['summary'] ?? '';

            $experienceText = implode(' ', array_map(function ($exp) {
                return ($exp['title'] ?? '') . ' ' .
                    ($exp['company'] ?? '') . ' ' .
                    implode(' ', $exp['highlights'] ?? []);
            }, $finalCv['experience'] ?? []));

            $projectsText = implode(' ', array_map(function ($project) {
                return ($project['title'] ?? '') . ' ' .
                    ($project['description'] ?? '');
            }, $finalCv['projects'] ?? []));

            $cvText = trim($skillsText . ' ' . $summaryText . ' ' . $experienceText . ' ' . $projectsText);

            if (!empty(trim($jobDescription ?? ''))) {
                $semanticScore = $this->semanticMatchScore($cvText, $jobDescription);
            } else {
                $semanticScore = min(100, ($matchScore * 0.6) + ($atsScore * 0.4));
            }

            $semanticNormalized = min(100, max(0, $semanticScore));


            $jobReadiness = (int)($aiAnalysis['job_readiness_score'] ?? 0);


            $marketFitScore =
                ($atsScore * 0.4) +
                ($semanticNormalized * 0.4) +
                ($jobReadiness * 0.2);

            $marketFit = match (true) {
                $marketFitScore >= 80 => 'high',
                $marketFitScore >= 55 => 'medium',
                default => 'low',
            };


            $finalScore = round(
                ($atsScore * 0.4) +
                ($semanticNormalized * 0.4) +
                ($matchScore * 0.2),
                2
            );


            $realWeaknesses = collect(array_merge(
                $aiAnalysis['weaknesses'] ?? [],
                $aiAnalysis['missing_market_skills'] ?? [],
                $skillInsights['missing_skills'] ?? []
            ))
                ->map(fn($s) => strtolower(trim($s)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $improvements = array_values(array_unique(array_merge(
                $aiAnalysis['improvements'] ?? [],
                [
                    'Improve ATS keyword optimization',
                    'Add measurable project impact',
                    'Improve semantic relevance to job roles'
                ]
            )));


            $analysis = [

                'ats_score' => $atsScore,
                'match_score' => $matchScore,
                'semantic_score' => $semanticNormalized,
                'final_score' => $finalScore,
                'job_readiness_score' => $jobReadiness,
                'market_fit' => $marketFit,

                'seniority_level' => $aiAnalysis['seniority_level'] ?? 'unknown',
                'career_paths' => $aiAnalysis['career_paths'] ?? [],
                'strengths' => $aiAnalysis['strengths'] ?? [],
                'weaknesses' => $realWeaknesses,
                'improvements' => $improvements,
                'job_roles' => $aiAnalysis['job_roles'] ?? [],

                'market_intelligence' =>
                    is_array($aiAnalysis['market_intelligence'] ?? null)
                        ? $aiAnalysis['market_intelligence']
                        : [],

                'skills' => [
                    'all' => $realSkills,
                    'domains' => $skillInsights['domains'] ?? [],
                    'roles' => $skillInsights['roles'] ?? [],
                    'recommended_skills' => $recommended,
                    'missing_skills' => $skillInsights['missing_skills'] ?? [],
                    'adjacent_skills' => $skillInsights['adjacent_skills'] ?? [],
                ],

                'match_breakdown' => $match['breakdown'] ?? [],
            ];


            $optimizedCv = $this->optimizeCv($finalCv, $analysis);


            CvAnalysis::create([
                'user_id' => $user->id,
                'type' => 'cv_analysis',
                'cv_snapshot' => $cv,
                'cv_final' => $optimizedCv,
                'ats_score' => $analysis['ats_score'],
                'match_score' => $analysis['match_score'],
                'strengths' => $analysis['strengths'],
                'weaknesses' => $analysis['weaknesses'],
                'suggestions' => $analysis['improvements'],
                'source' => 'hybrid_ai_system',
                'model' => 'llama-3.1-8b-instant',
            ]);

            return [
//                'cv' => $finalCv,
                'optimized_cv' => $optimizedCv,
                'analysis' => $analysis,
            ];
        });
    }
    private function safeJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/```json|```/', '', $text);
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return $this->fallbackJson();
        }
        $json = substr($text, $start, $end - $start + 1);

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return $this->fallbackJson();
        }

        return $this->validateAiSchema($decoded);
    }

    private function validateAiSchema(array $data): array
    {
        return [
            'cv' => $this->safeGet($data, 'cv', [
                'header' => [],
                'summary' => '',
                'skills' => [
                    'all' => [],
                    'technical' => [],
                    'tools' => [],
                    'languages' => [],
                    'soft_skills' => [],
                    'recommended' => [],
                ],
                'experience' => [],
                'education' => [],
                'projects' => [],
                'certifications' => [],
                'trainings' => [],
                'interests' => [],
            ]),

            'analysis' => $this->safeGet($data, 'analysis', [
                'ats_score' => 0,
                'match_score' => 0,
                'job_readiness_score' => 0,
                'hiring_probability' => 0,

                'market_fit' => 'low',
                'seniority_level' => 'junior',

                'strengths' => [],
                'weaknesses' => [],
                'improvements' => [],

                'missing_market_skills' => [],
                'job_roles' => [],
                'career_paths' => [],

                'skill_domains' => [],

                'skills' => [
                    'strong_skills' => [],
                    'missing_skills' => [],
                    'recommended_skills' => [],
                    'adjacent_skills' => [],
                ],

                'market_intelligence' => [
                    'industry_fit' => [],
                    'top_matching_domains' => [],
                    'market_competitiveness' => '',
                    'salary_potential' => '',
                    'learning_priority' => [],
                    'top_company_fit' => [],
                ],
            ]),
        ];
    }

    private function safeGet(array $data, string $key, array $default = [])
    {
        return isset($data[$key]) && is_array($data[$key])
            ? $data[$key]
            : $default;
    }

    private function fallbackJson(): array
    {
        return [
            'cv' => [],
            'analysis' => [
                'ats_score' => 0,
                'match_score' => 0,
                'job_readiness_score' => 0,
                'hiring_probability' => 0,
                'market_fit' => 'low',
                'seniority_level' => 'unknown',
                'strengths' => [],
                'weaknesses' => [],
                'improvements' => [],
                'missing_market_skills' => [],
                'job_roles' => [],
                'career_paths' => [],
                'skill_domains' => [],
                'skills' => [],
                'market_intelligence' => [],
            ],
        ];
    }

    private function marketFit(int $ats, int $jobReadiness): string
    {
        $score = ($ats * 0.6) + ($jobReadiness * 0.4);

        return match (true) {
            $score >= 80 => 'high',
            $score >= 50 => 'medium',
            default => 'low',
        };
    }


    private function buildPrompt(array $cv, ?string $jobTitle, ?string $jobDescription): string
    {
        return "
You are an advanced CV Analysis and ATS Optimization Engine.

You analyze CVs across ALL industries including:
Software, Engineering, Business, Finance, Marketing, Design, Healthcare, Education, and General professions.

━━━━━━━━━━━━━━━━━━
CRITICAL RULES (NON-NEGOTIABLE)
━━━━━━━━━━━━━━━━━━

1. NEVER INVENT FACTUAL DATA
Do NOT create:
- fake jobs, companies, dates, projects, or certifications

2. YOU MAY:
- improve wording professionally
- normalize structure and formatting
- infer seniority level
- infer missing skills realistically
- infer career paths based on evidence
- enhance summaries and descriptions professionally

3. DO NOT:
- change facts (company names, titles, education)
- duplicate skills in multiple fields
- mix roles inside skills arrays
- output inconsistent scoring

4. ALL OUTPUT MUST BE:
- valid JSON only
- no markdown
- no explanations
- no extra text

5. ALL ARRAYS MUST BE VALID
- must never contain empty or irrelevant placeholders
- must not include duplicates

━━━━━━━━━━━━━━━━━━
SKILL RULES
━━━━━━━━━━━━━━━━━━

skills.all:
- only REAL skills from CV (normalized)

skills.recommended:
- future growth skills only (not currently present)

skills.missing_skills:
- skills required for improvement or industry standards

skills.adjacent_skills:
- related technologies, not duplicates of other arrays

IMPORTANT:
- Do NOT repeat same skill in multiple arrays unless absolutely necessary
- Do NOT place roles, salary, or seniority inside skills

━━━━━━━━━━━━━━━━━━
ROLE INFERENCE RULES
━━━━━━━━━━━━━━━━━━

job_roles MUST be:

Array of objects:
{
  title: string,
  industry: string,
  seniority: string (junior|mid|senior),
  description: string
}

- Must be realistic
- Must not include random keywords
- Must be based on CV evidence only

━━━━━━━━━━━━━━━━━━
SCORING RULES
━━━━━━━━━━━━━━━━━━

- ATS score: 0–100 (keyword + structure + relevance)
- match score: 0–100 (job alignment if provided)
- semantic score: 0–100 (text similarity)
- final score: weighted average, realistic
- job_readiness_score: 0–100 realistic (no inflation)

If CV is weak → scores MUST reflect that

━━━━━━━━━━━━━━━━━━
IMPROVEMENT RULES
━━━━━━━━━━━━━━━━━━
NO OUTSIDE TECHNOLOGY INFERENCE
Do NOT introduce technologies not supported by:
- experience
- projects
- certifications
- or explicit CV skills
SENIORITY RULE (VERY IMPORTANT)
- Do NOT infer senior or lead level unless CV explicitly shows:
  - 5+ years real experience OR
  - multiple complex projects OR
  - leadership mentions in experience
- Otherwise default to junior or mid-level
JOB ROLE STRICT RULE
job_roles MUST ONLY use:
- technologies present in CV
- OR directly inferred from experience

Never introduce new stacks (React, Node, etc) if not present
MUST exist in:
- cv.skills
- cv.experience
- cv.projects
- cv.certifications
DO NOT infer roles outside CV domain.

If CV is backend:
→ allowed: backend, full stack (light)
→ forbidden: data science, AI, ML, DevOps senior roles, cloud engineer
unless explicitly supported
seniority rules:
- junior: 0–2 years or simple projects
- mid: 2–5 years + real projects
- senior: 5+ years + leadership + architecture

If unclear → default DOWN not UP
NEVER upgrade seniority beyond evidence in CV.
If unsure → downgrade not upgrade.
Do NOT introduce technologies not explicitly present in:
- CV skills
- projects
- experience
job_roles must strictly match CV domain:
If backend → only backend-related roles
Never introduce full-stack unless frontend exists
Never introduce ML, Data Science, Cloud Engineer unless explicit signals exist
Seniority rules:
- junior: simple experience / single project
- mid: multiple real projects or experience
- senior: 5+ years + leadership + architecture evidence

Default to LOWER level if uncertain
strengths must ONLY be derived from:
- experience highlights
- skills list
- certifications

Never infer personality traits (communication, leadership) without evidence


Career paths must be:
- directly adjacent to current stack only
Example:
Laravel → Backend Developer → PHP Engineer → API Engineer
NOT:
Data Scientist / ML / Cloud Architect unless evidence exists
Default seniority rules:
- 1 project + no enterprise experience = junior/mid-
- mid requires multiple projects or real company experience
- senior requires leadership + system design + scale evidence

NEVER upgrade seniority based on adjectives in summary
Do not introduce programming languages not explicitly present in CV.
Career paths and job_roles MUST be strictly derived from:
- experience technologies
- project technologies
- explicit skills

DO NOT introduce new domains (ML, Data Science, Cloud, Java, Python) unless present in CV
Example:
If React is not in CV → NEVER include it in any field
improvements MUST include:
- ATS optimization
- technical growth
- project improvement
- skill gaps

No generic motivational phrases only

━━━━━━━━━━━━━━━━━━
SKILL EXPANSION LOGIC
━━━━━━━━━━━━━━━━━━

Infer only realistic related skills:

If Laravel:
- PHP, REST API, MySQL, MVC, Eloquent, JWT

If AWS:
- Cloud, CI/CD, Docker, Infrastructure

If React:
- JavaScript, SPA, state management

━━━━━━━━━━━━━━━━━━
OUTPUT FORMAT (STRICT)
━━━━━━━━━━━━━━━━━━

Return EXACT JSON:

{
  \"cv\": {
    \"header\": {},
    \"summary\": \"\",
    \"skills\": {
      \"all\": [],
      \"technical\": [],
      \"tools\": [],
      \"languages\": [],
      \"soft_skills\": [],
      \"recommended\": []
    },
    \"experience\": [],
    \"education\": [],
    \"projects\": [],
    \"certifications\": [],
    \"trainings\": [],
    \"interests\": []
  },

  \"analysis\": {
    \"ats_score\": 0,
    \"match_score\": 0,
    \"semantic_score\": 0,
    \"final_score\": 0,
    \"job_readiness_score\": 0,

    \"market_fit\": \"low|medium|high\",
    \"seniority_level\": \"junior|mid|senior\",

    \"strengths\": [],
    \"weaknesses\": [],
    \"improvements\": [],

    \"job_roles\": [
      {
        \"title\": \"\",
        \"industry\": \"\",
        \"seniority\": \"\",
        \"description\": \"\"
      }
    ],

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
";
    }

    private function calculateAtsScore(
        array  $cv,
        array  $aiAnalysis,
        string $jobTitle = '',
        string $jobDescription = ''
    ): int
    {

        $score = 0;

        $jobText = strtolower(trim($jobTitle . ' ' . $jobDescription));

        $skills = collect($cv['skills']['all'] ?? [])
            ->map(fn($s) => strtolower(trim($s)))
            ->filter()
            ->unique();

        /*
        |----------------------------
        | 1. HARD SKILL MATCH (Core ATS)
        |----------------------------
        */
        $matched = $skills->filter(function ($skill) use ($jobText) {
            return str_contains($jobText, $skill);
        });

        $skillRatio = $skills->count() > 0
            ? $matched->count() / $skills->count()
            : 0;

        // nonlinear scoring (real ATS behavior)
        $score += pow($skillRatio, 0.7) * 35;


        /*
        |----------------------------
        | 2. JOB TITLE ALIGNMENT (high weight in real ATS)
        |----------------------------
        */
        if (!empty($jobTitle)) {
            $titleTokens = collect(explode(' ', strtolower($jobTitle)))
                ->filter(fn($w) => strlen($w) > 2);

            $titleHits = $titleTokens->filter(
                fn($w) => $skills->contains($w)
            )->count();

            $score += min(12, $titleHits * 4);
        }

        /*
        |----------------------------
        | 3. EXPERIENCE DEPTH (log-based realism)
        |----------------------------
        */
        $exp = count($cv['experience'] ?? []);

        $score += match (true) {
            $exp >= 7 => 18,
            $exp >= 4 => 14,
            $exp >= 2 => 9,
            $exp >= 1 => 5,
            default => 0,
        };

        /*
        |----------------------------
        | 4. PROJECT QUALITY SIGNAL (not just count)
        |----------------------------
        */
        $projects = count($cv['projects'] ?? []);

        $score += min(14, ($projects * 3.5));

        /*
        |----------------------------
        | 5. EDUCATION SIGNAL (boolean weighted)
        |----------------------------
        */
        if (!empty($cv['education'])) {
            $score += 6;
        }

        /*
        |----------------------------
        | 6. SUMMARY NLP QUALITY
        |----------------------------
        */
        $summaryLen = strlen($cv['summary'] ?? '');

        if ($summaryLen > 250) $score += 7;
        elseif ($summaryLen > 120) $score += 5;
        elseif ($summaryLen > 60) $score += 2;

        /*
        |----------------------------
        | 7. MODERN STACK SIGNAL (real recruiter bias)
        |----------------------------
        */
        $techWeights = [
            'docker' => 2.5,
            'kubernetes' => 3,
            'aws' => 2.5,
            'microservices' => 3,
            'redis' => 2,
            'ci' => 1.5,
            'cd' => 1.5,
            'react' => 2,
            'laravel' => 2,
        ];

        foreach ($techWeights as $tech => $w) {
            if ($skills->contains($tech)) {
                $score += $w;
            }
        }

        /*
        |----------------------------
        | 8. KEYWORD DENSITY (anti-spam weighted)
        |----------------------------
        */
        $total = max(1, $skills->count());
        $density = $matched->count() / $total;

        $score += $density * 12;

        /*
        |----------------------------
        | 9. CONTEXTUAL JOB MATCH BOOST (semantic hinting)
        |----------------------------
        */
        $jobTokens = collect(explode(' ', $jobText))
            ->filter(fn($w) => strlen($w) > 3)
            ->unique();

        $contextHits = $jobTokens->filter(
            fn($w) => $skills->contains($w)
        )->count();

        $score += min(8, $contextHits * 2);

        /*
        |----------------------------
        | 10. STRUCTURE PENALTIES (real ATS rejection logic)
        |----------------------------
        */
        if (empty($cv['skills']['all'])) $score -= 12;
        if (empty($cv['experience'])) $score -= 10;
        if (empty($cv['projects'])) $score -= 6;
        if (empty($cv['summary'])) $score -= 6;

        /*
        |----------------------------
        | 11. ATS NORMALIZATION (real distribution curve)
        |----------------------------
        */
        $score = max(0, min(100, $score));

        /*
        |----------------------------
        | 12. REAL WORLD BIAS SIMULATION
        | (junior inflation + senior strictness)
        |----------------------------
        */
        if ($exp <= 1 && $score > 85) {
            $score = 85; // prevent unrealistic inflation
        }

        if ($exp >= 5 && $score < 30) {
            $score += 5; // baseline recruiter leniency
        }

        return (int)round($score);
    }



    private function semanticMatchScore(string $cvText, string $jobText): float
{
    if (trim($jobText) === '') {
        return 0;
    }

    $cvVector = $this->embedding->embed($cvText);
    $jobVector = $this->embedding->embed($jobText);

    if (empty($cvVector) || empty($jobVector)) {
        return 0;
    }

    $similarity = $this->cosine($cvVector, $jobVector);

    return round($similarity * 100, 2);
}
    private function cosine(array $a, array $b): float
    {
        $dot = 0;
        $magA = 0;
        $magB = 0;

        foreach ($a as $i => $val) {
            $dot += $val * ($b[$i] ?? 0);
            $magA += $val * $val;
            $magB += ($b[$i] ?? 0) * ($b[$i] ?? 0);
        }

        return ($magA && $magB)
            ? $dot / (sqrt($magA) * sqrt($magB))
            : 0;
    }
    public function optimizeCv(array $cv, array $analysis): array
    {
        $prompt = "Return ONLY valid JSON. Rewrite this CV professionally:\n" .
            json_encode([
                'cv' => $cv,
                'improvements' => $analysis['improvements']
            ]);

        $response = $this->ai->ask($prompt);

        $json = $this->safeJson($response);

        return $json['cv'] ?? $cv;
    }
    public function generatePdf(array $cv, ?int $userId = null, string $template = 'ats')
    {
        $template = in_array($template, ['ats','modern','creative']) ? $template : 'ats';

        $html = view("cv.templates.$template", ['cv' => $cv])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $file = "cv/cv_" . time() . ".pdf";

        Storage::disk('public')->put($file, $pdf->output());

        if ($userId && $user = User::find($userId)) {
            optional($user->profile)->update([
                'cv_file' => $file
            ]);
        }

        return $file;
    }
}
