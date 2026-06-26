<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;


class CvFileExtractionService
{
    public function __construct(
        protected AIService $ai
    ) {}

    /**
     * استخراج CV من ملف
     */
    public function extractFromFile(UploadedFile $file): array
    {
        $rawText = $this->extractRawText($file);

        if (trim($rawText) === '') {
            throw new \RuntimeException('لم نتمكن من قراءة محتوى الملف. تأكد أن الملف غير صورة ممسوحة بدون نص قابل للتحديد.');
        }

        return $this->structureRawText($rawText);
    }

    /**
     * استخراج النص الخام من الملف
     */
    private function extractRawText(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->extractFromPdf($file),
            'docx', 'doc' => $this->extractFromWord($file),
            default => throw new \InvalidArgumentException('صيغة الملف غير مدعومة. الصيغ المدعومة: PDF, DOCX.'),
        };
    }

    /**
     * استخراج من PDF
     */
    private function extractFromPdf(UploadedFile $file): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getRealPath());
        return $pdf->getText();
    }

    /**
     * استخراج من Word
     */
    private function extractFromWord(UploadedFile $file): string
    {
        $phpWord = WordIOFactory::load($file->getRealPath());
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $sub) {
                        if (method_exists($sub, 'getText')) {
                            $text .= $sub->getText() . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return $text;
    }

    /**
     * تحويل النص الخام إلى بنية منظمة عبر AI
     */
    private function structureRawText(string $rawText): array
    {
        $prompt = $this->buildExtractionPrompt($rawText);
        $response = $this->ai->ask($prompt);

        Log::info('CV extraction AI raw response', [
            'raw_text_length' => mb_strlen($rawText),
            'ai_raw_response' => $response,
        ]);

        $structured = $this->safeJsonDecode($response);

        if (!$this->hasAnyRealData($structured)) {
            Log::warning('CV extraction returned structurally empty data', [
                'decoded_structure' => $structured,
            ]);

            throw new \RuntimeException(
                'لم يتمكن الذكاء الاصطناعي من استخراج أي بيانات فعلية من الملف.'
            );
        }

        return $this->fillMissingWithEmpty($structured);
    }

    /**
     * التحقق من وجود بيانات فعلية
     */
    private function hasAnyRealData(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->hasAnyRealData($value)) {
                    return true;
                }
            } elseif (is_string($value) && trim($value) !== '') {
                return true;
            } elseif (is_numeric($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * بناء prompt للاستخراج
     */
    private function buildExtractionPrompt(string $rawText): string
    {
        return <<<PROMPT
You are a strict CV TEXT EXTRACTOR, not a generator.

Your ONLY job is to extract information that is LITERALLY present in the
raw text below, and place it into the JSON structure.

ABSOLUTE RULES:
- If a field is not explicitly present in the text, leave it as an empty
  string or empty array. NEVER invent, guess, or infer missing facts.
- NEVER invent company names, job titles, dates, degrees, or numbers.
- Do not translate or rephrase facts — copy them as close to the
  original wording as possible.
- Output ONLY valid JSON, no markdown, no explanation.

RAW CV TEXT:
---
{$rawText}
---

Return EXACTLY this JSON structure:
{
  "header": {
    "name": "",
    "title": "",
    "contact": { "email": "", "phone": "", "location": "", "linkedin": "", "github": "" }
  },
  "summary": "",
  "skills": { "all": [], "technical": [], "tools": [], "languages": [], "soft_skills": [] },
  "experience": [ { "title": "", "company": "", "start_date": "", "end_date": "", "highlights": [] } ],
  "education": [ { "institution": "", "degree": "", "field_of_study": "", "start_date": "", "end_date": "" } ],
  "projects": [ { "title": "", "description": "", "technologies": [] } ],
  "certifications": [ { "name": "", "issuer": "" } ]
}

RETURN ONLY JSON, NO MARKDOWN.
PROMPT;
    }

    /**
     * تحويل JSON بأمان
     */
    private function safeJsonDecode(string $text): array
    {
        $text = trim(preg_replace('/```json|```/', '', $text));
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            return [];
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * ملء الحقول الفارغة
     */
    private function fillMissingWithEmpty(array $data): array
    {
        $defaults = [
            'header' => ['name' => '', 'title' => '', 'contact' => []],
            'summary' => '',
            'skills' => ['all' => [], 'technical' => [], 'tools' => [], 'languages' => [], 'soft_skills' => []],
            'experience' => [],
            'education' => [],
            'projects' => [],
            'certifications' => [],
        ];

        return array_merge($defaults, $data);
    }
}
