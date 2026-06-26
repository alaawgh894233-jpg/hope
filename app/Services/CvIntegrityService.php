<?php

namespace App\Services;

/**
 * ✅ مسؤولة عن دمج تحسينات AI بدون السماح بتغيير الحقائق
 */
class CvIntegrityService
{
    /**
     * دمج CV المحسّن من AI مع الحماية الكاملة للحقائق
     */
    public function merge(array $originalCv, array $aiSuggestedCv): array
    {
        $safeCv = $originalCv;

        // 1️⃣ Summary: نسمح بتحسين الصياغة فقط
        if (!empty($aiSuggestedCv['summary'])) {
            $candidate = trim($aiSuggestedCv['summary']);
            if ($this->isSafeTextRewrite($candidate, $originalCv)) {
                $safeCv['summary'] = $candidate;
            }
        }

        // 2️⃣ Experience: الحقائق ثابتة، فقط highlights يمكن تحسينها
        $safeCv['experience'] = $this->mergeExperience(
            $originalCv['experience'] ?? [],
            $aiSuggestedCv['experience'] ?? []
        );

        // 3️⃣ كل الحقول الوقائعية تبقى كما هي
        $safeCv['education'] = $originalCv['education'] ?? [];
        $safeCv['projects'] = $originalCv['projects'] ?? [];
        $safeCv['certifications'] = $originalCv['certifications'] ?? [];
        $safeCv['trainings'] = $originalCv['trainings'] ?? [];
        $safeCv['interests'] = $originalCv['interests'] ?? [];
        $safeCv['header'] = $originalCv['header'] ?? [];

        // 4️⃣ Skills: لا نسمح بإضافة مهارات "موجودة" مزيفة
        $safeCv['skills'] = $originalCv['skills'] ?? [];
        if (!empty($aiSuggestedCv['skills']['recommended'])) {
            $safeCv['skills']['recommended'] = $this->onlyNewSuggestions(
                $aiSuggestedCv['skills']['recommended'],
                $originalCv['skills']['all'] ?? []
            );
        }

        return $safeCv;
    }

    /**
     * التحقق من أن AI لم يخترع أرقام جديدة
     */
    private function isSafeTextRewrite(string $candidateText, array $originalCv): bool
    {
        $numbersInCandidate = $this->extractNumbers($candidateText);

        if (empty($numbersInCandidate)) {
            return true;
        }

        $originalNumbers = $this->extractNumbers(json_encode($originalCv));

        foreach ($numbersInCandidate as $num) {
            if (!in_array($num, $originalNumbers, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * استخراج الأرقام من النص
     */
    private function extractNumbers(string $text): array
    {
        preg_match_all('/\d+/', $text, $matches);
        return array_unique($matches[0] ?? []);
    }

    /**
     * دمج الخبرات: الحقائق من الأصل، highlights يمكن تحسينها
     */
    private function mergeExperience(array $originalExperiences, array $aiExperiences): array
    {
        $result = [];

        foreach ($originalExperiences as $index => $original) {
            $merged = $original;

            $aiVersion = $aiExperiences[$index] ?? null;

            if ($aiVersion && !empty($aiVersion['highlights']) && is_array($aiVersion['highlights'])) {
                $safeHighlights = [];

                foreach ($aiVersion['highlights'] as $i => $highlight) {
                    $originalHighlight = $original['highlights'][$i] ?? '';
                    $testCv = ['summary' => $originalHighlight];

                    if ($this->isSafeTextRewrite((string) $highlight, $testCv)) {
                        $safeHighlights[] = $highlight;
                    } else {
                        $safeHighlights[] = $originalHighlight;
                    }
                }

                $merged['highlights'] = $safeHighlights;
            }

            $result[] = $merged;
        }

        return $result;
    }

    /**
     * المهارات المقترحة يجب ألا تكون موجودة أصلاً
     */
    private function onlyNewSuggestions(array $suggested, array $existingSkills): array
    {
        $existing = array_map('mb_strtolower', $existingSkills);

        return array_values(array_filter(
            array_unique(array_map('mb_strtolower', $suggested)),
            fn($skill) => !in_array($skill, $existing, true)
        ));
    }
}
