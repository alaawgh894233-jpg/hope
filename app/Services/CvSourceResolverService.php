<?php

namespace App\Services;

use App\Models\CvFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CvSourceResolverService
{
    public function __construct(
        protected CvBuilderService        $manualBuilder,
        protected CvFileExtractionService $fileExtractor,
    ) {}

    /**
     * ✅ من البروفايل اليدوي
     */
    public function fromManualProfile(User $user): array
    {
        $cv = $this->manualBuilder->build($user);
        return $this->tagSource($cv, 'manual');
    }

    /**
     * ✅ من ملف مرفوع — يحفظ الملف + البيانات في cv_files
     */
    public function fromUploadedFile(UploadedFile $file, ?User $user = null): array
    {
        $cv = $this->fileExtractor->extractFromFile($file);

        if ($user) {
            // ✅ احفظ في public/cv_files/{user_id}/
            $path = $file->store("{$user->id}", 'cv_files');

            $record = CvFile::create([
                'user_id'       => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $path,
                'disk'          => 'cv_files',   // ← الـ disk الجديد
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
                'extracted_cv'  => $cv,
                'improved_cv'   => null,
                'is_confirmed'  => false,
            ]);

            $cv['_file_record_id'] = $record->id;
        }

        return $this->tagSource($cv, 'uploaded_file');
    }
    /**
     * ✅ من سجل ملف محفوظ سابقاً
     *
     * @param bool $useExtracted
     *   true  → دايماً من extracted_cv الأصلي النظيف (للتحسين)
     *   false → improved_cv لو موجود، وإلا extracted_cv (للعرض والـ PDF)
     */
    public function fromSavedFileRecord(
        int  $fileRecordId,
        User $user,
        bool $useExtracted = false
    ): array {
        $record = CvFile::where('id', $fileRecordId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($useExtracted || empty($record->improved_cv)) {
            $cv = $record->extracted_cv ?? [];
        } else {
            $cv = $record->improved_cv;
        }

        $cv['_file_record_id'] = $record->id;

        return $this->tagSource($cv, 'saved_file');
    }

    /**
     * ✅ المسار الكامل للملف الأصلي على disk
     */
    public function getRawFilePath(int $fileRecordId, User $user): string
    {
        $record = CvFile::where('id', $fileRecordId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!Storage::disk($record->disk)->exists($record->stored_path)) {
            throw new \RuntimeException('الملف غير موجود على الخادم.');
        }

        return Storage::disk($record->disk)->path($record->stored_path);
    }

    /**
     * ✅ حفظ البيانات بعد مراجعة المستخدم في قاعدة البيانات
     */
    public function confirmAndSaveExtractedCv(User $user, array $reviewedCv): void
    {
        // ── تحديث is_confirmed في cv_files ──
        if (!empty($reviewedCv['_file_record_id'])) {
            CvFile::where('id', $reviewedCv['_file_record_id'])
                ->where('user_id', $user->id)
                ->update([
                    'extracted_cv' => $reviewedCv,
                    'is_confirmed' => true,
                ]);
        }

        // 1️⃣ Profile
        $profileData = [
            'full_name' => $reviewedCv['header']['name'] ?? $user->name,
            'headline'  => $reviewedCv['header']['title'] ?? null,
            'summary'   => $reviewedCv['summary'] ?? null,
        ];

        $contact = $reviewedCv['header']['contact'] ?? [];
        if (!empty($contact['phone']))    $profileData['phone']    = $contact['phone'];
        if (!empty($contact['linkedin'])) $profileData['linkedin'] = $contact['linkedin'];
        if (!empty($contact['github']))   $profileData['github']   = $contact['github'];
        if (!empty($contact['location'])) $profileData['city']     = $contact['location'];

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            array_filter($profileData, fn($v) => $v !== null)
        );

        // 2️⃣ Skills — كل sub-keys
        $allSkills = array_values(array_unique(array_filter(array_merge(
            $reviewedCv['skills']['all']         ?? [],
            $reviewedCv['skills']['technical']   ?? [],
            $reviewedCv['skills']['tools']       ?? [],
            $reviewedCv['skills']['soft_skills'] ?? [],
            $reviewedCv['skills']['languages']   ?? [],
        ))));

        foreach ($allSkills as $skillName) {
            $trimmed = trim((string) $skillName);
            if ($trimmed === '') continue;
            $user->skills()->firstOrCreate(
                ['user_id' => $user->id, 'name' => $trimmed],
                ['name' => $trimmed, 'type' => 'technical']
            );
        }

        // 3️⃣ Experiences
        foreach (($reviewedCv['experience'] ?? []) as $exp) {
            if (empty($exp['company']) || empty($exp['title'])) continue;

            $highlights = [];
            if (!empty($exp['highlights']) && is_array($exp['highlights'])) {
                $highlights = array_values(array_filter(
                    $exp['highlights'],
                    fn($h) => trim((string)$h)
                ));
            }

            $user->experiences()->firstOrCreate(
                [
                    'user_id'  => $user->id,
                    'company'  => $exp['company'],
                    'position' => $exp['title'],
                ],
                [
                    'start_date'        => !empty($exp['start_date'])
                        ? date('Y-m-d', strtotime($exp['start_date'])) : now(),
                    'end_date'          => !empty($exp['end_date'])
                        ? date('Y-m-d', strtotime($exp['end_date'])) : null,
                    'is_current'        => !empty($exp['current']),
                    'description'       => implode("\n", $highlights),
                    'technologies_used' => $exp['technologies'] ?? [],
                ]
            );
        }

        // 4️⃣ Education
        foreach (($reviewedCv['education'] ?? []) as $edu) {
            if (empty($edu['institution']) || empty($edu['degree'])) continue;

            $user->educations()->firstOrCreate(
                [
                    'user_id'     => $user->id,
                    'institution' => $edu['institution'],
                    'degree'      => $edu['degree'],
                ],
                [
                    'field_of_study' => $edu['field_of_study'] ?? null,
                    'start_date'     => !empty($edu['start_date'])
                        ? date('Y-m-d', strtotime($edu['start_date'])) : now(),
                    'end_date'       => !empty($edu['end_date'])
                        ? date('Y-m-d', strtotime($edu['end_date'])) : null,
                    'grade'          => is_numeric($edu['grade'] ?? null)
                        ? (int)$edu['grade'] : null,
                ]
            );
        }

        // 5️⃣ Projects
        if (method_exists($user, 'projects') && !empty($reviewedCv['projects'])) {
            foreach ($reviewedCv['projects'] as $proj) {
                if (empty($proj['title'])) continue;
                $user->projects()->firstOrCreate(
                    ['user_id' => $user->id, 'title' => $proj['title']],
                    [
                        'description'  => $proj['description'] ?? null,
                        'link'         => $proj['link'] ?? null,
                        'technologies' => $proj['technologies'] ?? [],
                    ]
                );
            }
        }

        // 6️⃣ Certifications
        if (method_exists($user, 'certifications') && !empty($reviewedCv['certifications'])) {
            foreach ($reviewedCv['certifications'] as $cert) {
                if (empty($cert['name'])) continue;
                $user->certifications()->firstOrCreate(
                    ['user_id' => $user->id, 'name' => $cert['name']],
                    [
                        'issuer'        => $cert['issuer'] ?? null,
                        'issued_at'     => !empty($cert['issued_at'])
                            ? date('Y-m-d', strtotime($cert['issued_at'])) : now(),
                        'expires_at'    => !empty($cert['expires_at'])
                            ? date('Y-m-d', strtotime($cert['expires_at'])) : null,
                        'credential_id' => $cert['credential_id'] ?? null,
                    ]
                );
            }
        }
    }

    private function tagSource(array $cv, string $source): array
    {
        $cv['_meta'] = ['source' => $source];
        return $cv;
    }
}
