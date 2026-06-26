<?php

namespace App\Services;

use App\Models\CvFile;

class CvEnhancerService
{
    public function __construct(
        protected AIService $ai
    ) {}

    /**
     * ✅ تحسين CV كامل مع إمكانية الحفظ
     */
    public function enhance(
        array   $cv,
        ?int    $fileRecordId   = null,
        ?string $jobTitle       = null,
        ?string $company        = null,
        ?string $jobDescription = null,
        bool    $saveImproved   = false,
    ): array {
        // ✅ أول شيء: نظف الـ CV من أي تحسينات سابقة
        $cv = $this->cleanCvFromPreviousEnhancements($cv);

        $changes  = [];
        $targeted = !empty($jobTitle) || !empty($company) || !empty($jobDescription);

        // 1️⃣ توحيد المهارات من كل sub-keys
        $normalizedSkills            = $this->normalizeSkills($cv['skills'] ?? []);
        $cv['skills']['all']         = $normalizedSkills;
        $cv['skills']['technical']   = [];
        $cv['skills']['tools']       = [];
        $cv['skills']['soft_skills'] = [];
        $cv['skills']['languages']   = [];
        $changes[] = 'تم توحيد قائمة المهارات';

        // 2️⃣ استنتاج العنوان لو فارغ — لا نحط jobTitle المستهدف
        if (empty($cv['header']['title'])) {
            if (!empty($cv['experience'])) {
                $cv['header']['title'] = $this->inferTitle($cv['experience']);
                $changes[] = 'تم استنتاج المسمى الوظيفي من الخبرات';
            }
        }

        // 3️⃣ AI call واحد يحسن كل شيء
        $aiResult = $this->callAiEnhancement($cv, $jobTitle, $company, $jobDescription, $targeted);

        // 4️⃣ تطبيق Summary
        $originalSummary = trim($cv['summary'] ?? '');
        $aiSummary       = trim($aiResult['summary'] ?? '');
        if (
            !empty($aiSummary)
            && $aiSummary !== $originalSummary
            && !$this->hasInventedNumbers($aiSummary, json_encode($cv))
            && !$this->containsForbiddenDomain($aiSummary, $cv)
            && mb_strlen($aiSummary) >= 20
        ) {
            $cv['summary'] = $aiSummary;
            $changes[] = $targeted
                ? 'تم تحسين Summary ليناسب الوظيفة والشركة المستهدفة'
                : 'تم تحسين صياغة الـ Summary';
        } elseif (empty($originalSummary) && !empty($normalizedSkills)) {
            $cv['summary'] = $this->generateBasicSummary($cv, $jobTitle, $company);
            $changes[] = 'تم إنشاء Summary';
        }

        // 5️⃣ تطبيق ترتيب المهارات
        $aiSkills = $aiResult['skills_ordered'] ?? [];
        if (!empty($aiSkills) && is_array($aiSkills)) {
            $reordered = $this->applySkillsOrder($normalizedSkills, $aiSkills);
            if ($reordered !== $normalizedSkills) {
                $cv['skills']['all'] = $reordered;
                $changes[] = $targeted
                    ? 'تم ترتيب المهارات حسب أولوية الوظيفة'
                    : 'تم ترتيب المهارات';
            }
        }

        // 6️⃣ تطبيق تحسين الخبرات
        $aiExperiences = $aiResult['experience'] ?? [];
        if (!empty($aiExperiences) && is_array($aiExperiences)) {
            $enhancedExp = $this->applyExperienceImprovements(
                $cv['experience'] ?? [],
                $aiExperiences,
                json_encode($cv)
            );
            if ($enhancedExp !== ($cv['experience'] ?? [])) {
                $cv['experience'] = $enhancedExp;
                $changes[] = $targeted
                    ? 'تم تحسين نقاط الخبرات لتبرز ما يناسب الوظيفة'
                    : 'تم تحسين صياغة نقاط الخبرات';
            }
        }

        // 7️⃣ تطبيق تحسين المشاريع
        $aiProjects = $aiResult['projects'] ?? [];
        if (!empty($aiProjects) && is_array($aiProjects)) {
            $enhancedProj = $this->applyProjectImprovements(
                $cv['projects'] ?? [],
                $aiProjects,
                json_encode($cv)
            );
            if ($enhancedProj !== ($cv['projects'] ?? [])) {
                $cv['projects'] = $enhancedProj;
                $changes[] = $targeted
                    ? 'تم تحسين أوصاف المشاريع لتناسب الوظيفة'
                    : 'تم تحسين أوصاف المشاريع';
            }
        }

        // 8️⃣ مهارات مقترحة للتعلم فقط
        $suggestedSkills = $aiResult['suggested_skills'] ?? [];
        if (!empty($suggestedSkills)) {
            $existingLower = array_map('mb_strtolower', $cv['skills']['all']);
            $filtered      = array_values(array_filter(
                $suggestedSkills,
                fn($s) => !in_array(mb_strtolower(trim((string)$s)), $existingLower)
                    && mb_strlen(trim((string)$s)) > 1
            ));
            if (!empty($filtered)) {
                $cv['skills']['suggested_for_job'] = $filtered;
                $changes[] = 'تمت إضافة مهارات مقترحة لتقوية ملفك لهذه الوظيفة';
            }
        }

        // 9️⃣ Meta
        $cv['_changes']  = $changes;
        $cv['_targeted'] = $targeted ? [
            'job_title'       => $jobTitle,
            'company'         => $company,
            'job_description' => $jobDescription,
        ] : null;

        // 🔟 حفظ في improved_cv لو طُلب
        if ($saveImproved && $fileRecordId) {
            $this->saveImprovedCv($fileRecordId, $cv);
        }

        return $cv;
    }

    /**
     * ✅ حفظ الـ CV المحسّن في قاعدة البيانات
     */
    public function saveImprovedCv(int $fileRecordId, array $enhancedCv): bool
    {
        $updated = CvFile::where('id', $fileRecordId)
            ->update(['improved_cv' => $enhancedCv]);

        return $updated > 0;
    }

    // ══════════════════════════════════════════════════
    //  CLEAN
    // ══════════════════════════════════════════════════

    /**
     * ✅ تنظيف الـ CV من أي تلوث من تحسينات سابقة
     */
    private function cleanCvFromPreviousEnhancements(array $cv): array
    {
        unset($cv['_changes']);
        unset($cv['_targeted']);

        if (isset($cv['skills']['suggested_for_job'])) {
            unset($cv['skills']['suggested_for_job']);
        }

        return $cv;
    }

    // ══════════════════════════════════════════════════
    //  FORBIDDEN DOMAIN CHECK
    // ══════════════════════════════════════════════════

    /**
     * ✅ تحقق من وجود domain مخترع في النص
     */
    private function containsForbiddenDomain(string $text, array $cv): bool
    {
        $cvText = mb_strtolower(json_encode($cv));

        $forbidden = [
            'financial', 'finance', 'accounting', 'accountant',
            'medical', 'medicine', 'doctor', 'clinical', 'healthcare',
            'legal', 'lawyer', 'attorney',
            'data scientist', 'machine learning engineer',
            'excel', 'power bi', 'tableau', 'bloomberg',
            'financial modeling', 'financial analysis',
            'passion for financial', 'potential for financial',
            'reporting skills',
        ];

        $textLow = mb_strtolower($text);

        foreach ($forbidden as $term) {
            if (str_contains($textLow, $term) && !str_contains($cvText, $term)) {
                return true;
            }
        }

        return false;
    }

    // ══════════════════════════════════════════════════
    //  AI CALL
    // ══════════════════════════════════════════════════

    private function callAiEnhancement(
        array   $cv,
        ?string $jobTitle,
        ?string $company,
        ?string $jobDescription,
        bool    $targeted
    ): array {
        $skillsList = implode('", "', $cv['skills']['all'] ?? []);

        $compactCv = [
            'name'       => $cv['header']['name'] ?? '',
            'title'      => $cv['header']['title'] ?? '',
            'summary'    => $cv['summary'] ?? '',
            'skills'     => $cv['skills']['all'] ?? [],
            'experience' => array_map(fn($e) => [
                'title'      => $e['title'] ?? $e['position'] ?? '',
                'company'    => $e['company'] ?? '',
                'highlights' => array_values(array_filter(
                    $e['highlights'] ?? [],
                    fn($h) => mb_strlen(trim((string)$h)) > 3
                )),
            ], $cv['experience'] ?? []),
            'projects'   => array_map(fn($p) => [
                'title'       => $p['title'] ?? '',
                'description' => $p['description'] ?? '',
            ], $cv['projects'] ?? []),
        ];

        $targetSection = $targeted
            ? "TARGET JOB:
- Job Title: " . ($jobTitle ?? 'Not specified') . "
- Company: " . ($company ?? 'Not specified') . "
- Job Description: " . ($jobDescription ?? 'Not provided') . "

TAILORING GOAL:
- Reorder skills so the most relevant to this job appear FIRST.
- Rewrite experience highlights to emphasize what is relevant.
- Improve project descriptions to highlight relevant aspects — but ONLY using facts from the original descriptions.
- Rewrite summary to highlight candidate's EXISTING skills that match this job.
- DO NOT mention any domain (finance, medicine, law, etc.) unless it EXISTS in the candidate's CV data below.
- DO NOT change the candidate's professional title or role."
            : "GOAL: Improve professional quality and clarity. No domain changes.";

        $prompt = "You are a professional CV writer for ALL industries.

{$targetSection}

══════════════════════════════════
ABSOLUTE RULES — NEVER BREAK ANY:
══════════════════════════════════
1. NEVER invent facts, numbers, skills, or experiences NOT in the original CV.
2. NEVER mention a professional domain unless it EXISTS in the candidate's CV data below.
3. NEVER change the candidate's professional role or title.
4. summary: use ONLY existing facts — no new claims, no invented passions or skills.
5. skills_ordered: use ONLY skills from this exact list (reorder only, no additions):
   [\"{$skillsList}\"]
6. experience: SAME number of bullet points, SAME facts, better wording only.
7. projects: ONLY improve wording of the ORIGINAL description. Do NOT add new sentences, new technologies, or new domain claims not present in the original description.
8. suggested_skills: skills the candidate does NOT currently have but should learn (max 5).
9. Return ONLY valid JSON — no markdown, no explanation.

ORIGINAL CV:
" . json_encode($compactCv, JSON_UNESCAPED_UNICODE) . "

Return EXACTLY this JSON:
{
  \"summary\": \"improved summary using only facts from the original\",
  \"skills_ordered\": [\"most relevant skill first\", \"...\"],
  \"experience\": [
    {
      \"title\": \"exact same title\",
      \"company\": \"exact same company\",
      \"highlights\": [\"improved bullet 1\", \"improved bullet 2\"]
    }
  ],
  \"projects\": [
    {
      \"title\": \"exact same title\",
      \"description\": \"improved description using ONLY the original words — no new sentences\"
    }
  ],
  \"suggested_skills\": [\"skill to learn 1\", \"skill to learn 2\"]
}

ONLY JSON.";

        $response = trim($this->ai->ask($prompt));
        $response = preg_replace('/```json|```/', '', $response);

        $start = strpos($response, '{');
        $end   = strrpos($response, '}');

        if ($start === false || $end === false) return [];

        $decoded = json_decode(substr($response, $start, $end - $start + 1), true);

        if (!is_array($decoded)) return [];

        return $this->validateAiResponse($decoded, $cv);
    }

    /**
     * ✅ تحقق وفلترة الـ AI response
     */
    private function validateAiResponse(array $aiResult, array $cv): array
    {
        $originalJson      = json_encode($cv);
        $existingSkillsLow = array_map('mb_strtolower', $cv['skills']['all'] ?? []);
        $cvText            = mb_strtolower($originalJson);

        // ── 1) Summary: رفض أي domain مخترع ──
        $summary = $aiResult['summary'] ?? '';
        if ($this->containsForbiddenDomain($summary, $cv)) {
            $aiResult['summary'] = '';
        }

        // ── 2) skills_ordered: احذف أي مهارة غير موجودة ──
        if (!empty($aiResult['skills_ordered'])) {
            $aiResult['skills_ordered'] = array_values(array_filter(
                $aiResult['skills_ordered'],
                fn($s) => in_array(mb_strtolower(trim((string)$s)), $existingSkillsLow)
            ));
        }

        // ── 3) suggested_skills: فلترة موسعة بالـ keywords ──
        if (!empty($aiResult['suggested_skills'])) {
            $existingKeywords = [];
            foreach ($cv['skills']['all'] ?? [] as $skill) {
                $words = preg_split('/[\s&()\-\/]+/', mb_strtolower((string)$skill));
                foreach ($words as $word) {
                    if (mb_strlen(trim($word)) > 2) {
                        $existingKeywords[] = trim($word);
                    }
                }
            }

            $aiResult['suggested_skills'] = array_values(array_filter(
                $aiResult['suggested_skills'],
                function ($s) use ($existingSkillsLow, $existingKeywords) {
                    $sLow = mb_strtolower(trim((string)$s));

                    if (in_array($sLow, $existingSkillsLow)) return false;

                    foreach ($existingKeywords as $kw) {
                        if (mb_strlen($kw) > 3 && str_contains($sLow, $kw)) {
                            return false;
                        }
                    }

                    return mb_strlen($sLow) > 1;
                }
            ));
        }

        // ── 4) projects: فلترة قوية — لازم يكون مبني على الأصل ──
        if (!empty($aiResult['projects'])) {
            $forbiddenProjectPhrases = [
                'potential for financial', 'financial analysis',
                'financial reporting', 'financial modeling',
                'accounting features', 'medical features', 'clinical data',
                'service layer architecture',  // ← مهارة مخترعة في المشاريع
                'authentication and authorization',
                'separation of concerns',
                'code optimization principles',
                'clean code principles',
                'object-oriented programming',
            ];

            foreach ($aiResult['projects'] as $i => $proj) {
                $aiDesc = trim($proj['description'] ?? '');
                if (empty($aiDesc)) continue;

                // ابحث عن المشروع الأصلي
                $origProj = null;
                foreach ($cv['projects'] ?? [] as $orig) {
                    if (
                        mb_strtolower(trim($orig['title'] ?? ''))
                        === mb_strtolower(trim($proj['title'] ?? ''))
                    ) {
                        $origProj = $orig;
                        break;
                    }
                }

                $origDesc = trim($origProj['description'] ?? '');

                // لو الأصل فارغ: اقبل الجديد بس لو قصير
                if (empty($origDesc)) {
                    if (mb_strlen($aiDesc) > 100) {
                        $aiResult['projects'][$i]['description'] = '';
                    }
                    continue;
                }

                // ── فلترة 1: forbidden phrases ──
                $origText = mb_strtolower(json_encode($origProj ?? ''));
                $replaced = false;
                foreach ($forbiddenProjectPhrases as $phrase) {
                    if (
                        str_contains(mb_strtolower($aiDesc), $phrase)
                        && !str_contains($origText, $phrase)
                    ) {
                        $aiResult[$i]['description'] = $origDesc;
                        $replaced = true;
                        break;
                    }
                }

                if ($replaced) continue;

                // ── فلترة 2: نسبة التطابق مع الأصل ≥ 50% ──
                $origWords = array_filter(
                    preg_split('/\s+/', mb_strtolower($origDesc)),
                    fn($w) => mb_strlen($w) > 3
                );
                $aiDescLow    = mb_strtolower($aiDesc);
                $matchedWords = 0;

                foreach ($origWords as $word) {
                    if (str_contains($aiDescLow, $word)) $matchedWords++;
                }

                $matchRatio = count($origWords) > 0
                    ? $matchedWords / count($origWords)
                    : 0;

                // لو أقل من 50% من كلمات الأصل موجودة → ارجع للأصل
                if ($matchRatio < 0.5) {
                    $aiResult['projects'][$i]['description'] = $origDesc;
                }
            }
        }

        return $aiResult;
    }

    // ══════════════════════════════════════════════════
    //  APPLY HELPERS
    // ══════════════════════════════════════════════════

    private function applySkillsOrder(array $original, array $aiOrdered): array
    {
        $originalLower = array_map('mb_strtolower', $original);
        $result        = [];

        foreach ($aiOrdered as $skill) {
            $idx = array_search(mb_strtolower(trim((string)$skill)), $originalLower);
            if ($idx !== false && !in_array($original[$idx], $result)) {
                $result[] = $original[$idx];
            }
        }

        foreach ($original as $skill) {
            if (!in_array($skill, $result)) {
                $result[] = $skill;
            }
        }

        return array_values(array_unique($result));
    }

    private function applyExperienceImprovements(
        array  $original,
        array  $aiExperiences,
        string $originalJson
    ): array {
        $result = $original;

        foreach ($aiExperiences as $aiExp) {
            $aiTitle   = mb_strtolower(trim($aiExp['title'] ?? ''));
            $aiCompany = mb_strtolower(trim($aiExp['company'] ?? ''));

            foreach ($result as $i => $orig) {
                $origTitle   = mb_strtolower(trim($orig['title'] ?? $orig['position'] ?? ''));
                $origCompany = mb_strtolower(trim($orig['company'] ?? ''));

                if ($origTitle === $aiTitle && $origCompany === $aiCompany) {
                    $aiHighlights = $aiExp['highlights'] ?? [];
                    if (!is_array($aiHighlights) || empty($aiHighlights)) continue;

                    $safe = true;
                    foreach ($aiHighlights as $bullet) {
                        if ($this->hasInventedNumbers((string)$bullet, $originalJson)) {
                            $safe = false;
                            break;
                        }
                    }

                    if ($safe) {
                        $result[$i]['highlights'] = array_values(array_filter(
                            array_map('trim', $aiHighlights),
                            fn($h) => mb_strlen($h) > 5
                        ));
                    }
                    break;
                }
            }
        }

        return $result;
    }

    private function applyProjectImprovements(
        array  $original,
        array  $aiProjects,
        string $originalJson
    ): array {
        $result = $original;

        foreach ($aiProjects as $aiProj) {
            $aiTitle = mb_strtolower(trim($aiProj['title'] ?? ''));

            foreach ($result as $i => $orig) {
                $origTitle = mb_strtolower(trim($orig['title'] ?? ''));

                if ($origTitle === $aiTitle) {
                    $aiDesc = trim($aiProj['description'] ?? '');

                    if (
                        !empty($aiDesc)
                        && !$this->hasInventedNumbers($aiDesc, $originalJson)
                        && mb_strlen($aiDesc) >= 10
                    ) {
                        $result[$i]['description'] = $aiDesc;
                    }
                    break;
                }
            }
        }

        return $result;
    }

    // ══════════════════════════════════════════════════
    //  SHARED HELPERS
    // ══════════════════════════════════════════════════

    private function normalizeSkills(array $skills): array
    {
        $all = array_values(array_unique(array_filter(array_merge(
            $skills['all']         ?? [],
            $skills['technical']   ?? [],
            $skills['tools']       ?? [],
            $skills['soft_skills'] ?? [],
            $skills['languages']   ?? [],
        ), fn($s) => mb_strlen(trim((string) $s)) > 1)));

        return array_values(array_unique(
            array_map(fn($s) => trim((string) $s), $all)
        ));
    }

    private function inferTitle(array $experiences): string
    {
        $latest = collect($experiences)->sortByDesc('start_date')->first();
        return $latest['title'] ?? $latest['position'] ?? '';
    }

    private function generateBasicSummary(array $cv, ?string $jobTitle, ?string $company): string
    {
        $title    = $cv['header']['title'] ?? 'Professional';
        $skills   = array_slice($cv['skills']['all'] ?? [], 0, 4);
        $expCount = count($cv['experience'] ?? []);

        $s = "A dedicated {$title}";
        if ($expCount > 0) $s .= " with hands-on experience";
        if (!empty($skills)) $s .= ", skilled in " . implode(', ', $skills);
        if ($company) $s .= ". Eager to contribute to {$company}";
        $s .= ".";

        return $s;
    }

    private function hasInventedNumbers(string $new, string $original): bool
    {
        preg_match_all('/\d+/', $new, $n);
        preg_match_all('/\d+/', $original, $o);
        $newNums  = array_unique($n[0] ?? []);
        $origNums = array_unique($o[0] ?? []);

        foreach ($newNums as $num) {
            if ((int)$num <= 10) continue;
            if (!in_array($num, $origNums)) return true;
        }
        return false;
    }
}
