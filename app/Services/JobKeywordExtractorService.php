<?php

namespace App\Services;
class JobKeywordExtractorService
{

    private const STOP_WORDS = [
        'and', 'or', 'the', 'with', 'for', 'of', 'in', 'to', 'a', 'an', 'is', 'are',
        'و', 'أو', 'مع', 'في', 'على', 'من', 'إلى', 'هو', 'هي', 'يجب', 'لديه', 'لديها',
        'years', 'experience', 'able', 'must', 'should', 'will', 'have', 'has',
        'سنوات', 'خبرة', 'قادر', 'يفضل', 'مطلوب', 'الراتب', 'بدوام', 'نبحث', 'عن',
    ];

    public function extract(string $jobTitle, string $jobDescription): array
    {
        $fullText = mb_strtolower(trim($jobTitle . ' ' . $jobDescription));

        if (empty($fullText)) {
            return [];
        }
        $tokens = $this->tokenize($fullText);
        $tokens = $this->removeStopWords($tokens);

        $bigrams = $this->extractBigrams($tokens);
        $frequency = $this->buildFrequencyMap($tokens, $bigrams);
        $titleTokens = $this->removeStopWords($this->tokenize(mb_strtolower($jobTitle)));
        foreach ($titleTokens as $token) {
            if (isset($frequency[$token])) {
                $frequency[$token] *= 2.0;
            }
        }

        return $this->normalize($frequency);
    }
    private function tokenize(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/u', trim($text));

        return array_values(array_filter($words, fn($w) => mb_strlen($w) > 2));
    }


    private function removeStopWords(array $tokens): array
    {
        return array_values(array_filter(
            $tokens,
            fn($t) => !in_array($t, self::STOP_WORDS, true)
        ));
    }

    private function extractBigrams(array $tokens): array
    {
        $bigrams = [];
        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $bigrams[] = $tokens[$i] . ' ' . $tokens[$i + 1];
        }
        return $bigrams;
    }


    private function buildFrequencyMap(array $tokens, array $bigrams): array
    {
        $freq = [];

        foreach ($tokens as $token) {
            $freq[$token] = ($freq[$token] ?? 0) + 1;
        }


        foreach ($bigrams as $bigram) {
            $freq[$bigram] = ($freq[$bigram] ?? 0) + 1.5;
        }

        return $freq;
    }

    private function normalize(array $freq): array
    {
        if (empty($freq)) {
            return [];
        }

        $max = max($freq);
        if ($max <= 0) {
            return [];
        }

        $normalized = [];
        foreach ($freq as $word => $count) {
            $normalized[$word] = round($count / $max, 3);
        }


        arsort($normalized);
        return array_slice($normalized, 0, 40, true);
    }

    public function matchAgainstCv(array $jobKeywords, array $cvTerms): array
    {
        if (empty($jobKeywords)) {
            return ['matched' => [], 'missing' => [], 'score' => 0];
        }

        $cvTermsLower = array_map(fn($t) => mb_strtolower(trim($t)), $cvTerms);
        $cvTermsLower = array_values(array_filter($cvTermsLower));

        $matched = [];
        $missing = [];
        $weightedScore = 0;
        $totalWeight = array_sum($jobKeywords);

        foreach ($jobKeywords as $keyword => $weight) {
            $found = false;
            foreach ($cvTermsLower as $term) {
                if (str_contains($keyword, $term) || str_contains($term, $keyword)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $matched[] = $keyword;
                $weightedScore += $weight;
            } else {
                $missing[] = $keyword;
            }
        }

        $score = $totalWeight > 0 ? round(($weightedScore / $totalWeight) * 100) : 0;

        return [
            'matched' => $matched,
            'missing' => $missing,
            'score' => $score,
        ];
    }
}
