<?php

namespace App\Http\Controllers;

use App\Models\CvFile;
use App\Services\CvAnalysisService;
use App\Services\CvEnhancerService;
use App\Services\CvSourceResolverService;
use App\Services\JobMatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvAnalysisController extends Controller
{
    public function __construct(
        protected CvAnalysisService       $analysisService,
        protected JobMatchService         $jobMatchService,
        protected CvSourceResolverService $sourceResolver,
        protected CvEnhancerService       $enhancer,
    ) {}

    // ══════════════════════════════════════════════════
    //  GET /api/cv/generate
    // ══════════════════════════════════════════════════
    public function build(Request $request)
    {
        $cv = $this->sourceResolver->fromManualProfile($request->user());
        return response()->json(['success' => true, 'cv' => $cv]);
    }

    // ══════════════════════════════════════════════════
    //  GET /api/cv/files
    // ══════════════════════════════════════════════════
    public function listFiles(Request $request)
    {
        $files = CvFile::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'original_name', 'stored_path', 'disk', 'size', 'mime_type', 'is_confirmed', 'created_at'])
            ->map(function ($file) {
                return [
                    'id'            => $file->id,
                    'original_name' => $file->original_name,
                    'size'          => $file->size,
                    'mime_type'     => $file->mime_type,
                    'is_confirmed'  => $file->is_confirmed,
                    'created_at'    => $file->created_at,
                    'has_file'      => !empty($file->stored_path),
                    'file_url'      => $file->getPublicUrl(),  // ✅
                ];
            });

        return response()->json(['success' => true, 'files' => $files]);
    }

    // ══════════════════════════════════════════════════
    //  GET /api/cv/files/{id}
    // ══════════════════════════════════════════════════
    public function showFile(Request $request, int $id)
    {
        $record = CvFile::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'file'    => [
                'id'            => $record->id,
                'original_name' => $record->original_name,
                'size'          => $record->size,
                'mime_type'     => $record->mime_type,
                'is_confirmed'  => $record->is_confirmed,
                'created_at'    => $record->created_at,
                'has_file'      => !empty($record->stored_path),
                // ✅ رابط مباشر
                'file_url'      => $record->getPublicUrl(),
                'extracted_cv'  => $record->extracted_cv,
                'improved_cv'   => $record->improved_cv,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════
    //  GET /api/cv/files/{id}/download
    // ══════════════════════════════════════════════════
    public function downloadOriginalFile(Request $request, int $id)
    {
        $record = CvFile::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (empty($record->stored_path)) {
            return response()->json([
                'success' => false,
                'message' => 'هذا السجل لا يحتوي على ملف مرفوع.',
            ], 404);
        }

        if (!Storage::disk($record->disk)->exists($record->stored_path)) {
            return response()->json([
                'success' => false,
                'message' => 'الملف غير موجود.',
            ], 404);
        }

        // ✅ الملف في public — ممكن تحميل مباشر
        return Storage::disk($record->disk)
            ->download($record->stored_path, $record->original_name);
    }
    // ══════════════════════════════════════════════════
    //  POST /api/cv/upload
    // ══════════════════════════════════════════════════
    public function uploadAndExtract(Request $request)
    {
        $request->validate([
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        try {
            $extracted = $this->sourceResolver->fromUploadedFile(
                $request->file('cv_file'),
                $request->user()
            );

            return response()->json([
                'success'        => true,
                'message'        => 'تم استخراج البيانات. راجعها قبل الحفظ.',
                'file_record_id' => $extracted['_file_record_id'] ?? null,
                'extracted_cv'   => $extracted,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ══════════════════════════════════════════════════
    //  POST /api/cv/upload/confirm
    // ══════════════════════════════════════════════════
    public function confirmUploadedCv(Request $request)
    {
        $request->validate(['reviewed_cv' => 'required|array']);

        $reviewedCv = $request->input('reviewed_cv');

        // ✅ يقبل كلا الشكلين
        if (isset($reviewedCv['extracted_cv']) && is_array($reviewedCv['extracted_cv'])) {
            $inner = $reviewedCv['extracted_cv'];
            if (!isset($inner['_file_record_id']) && isset($reviewedCv['_file_record_id'])) {
                $inner['_file_record_id'] = $reviewedCv['_file_record_id'];
            }
            $reviewedCv = $inner;
        }

        $this->sourceResolver->confirmAndSaveExtractedCv(
            $request->user(),
            $reviewedCv
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ البيانات بنجاح في ملفك الشخصي.',
        ]);
    }

    // ══════════════════════════════════════════════════
    //  POST /api/cv/analyze
    // ══════════════════════════════════════════════════
    public function analyze(Request $request)
    {
        $request->validate([
            'job_title'       => 'nullable|string|max:500',
            'job_description' => 'nullable|string',
            'company'         => 'nullable|string|max:255',
            'source'          => 'nullable|in:profile,file,merge,saved_file',
            'cv_file'         => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'file_record_id'  => 'nullable|integer|exists:cv_files,id',
        ]);

        $source = $request->input('source', 'profile');

        if (in_array($source, ['file', 'merge']) && !$request->hasFile('cv_file')) {
            return response()->json([
                'success' => false,
                'message' => 'يجب رفع cv_file مع source=file أو merge',
            ], 422);
        }

        try {
            $extractedCv = null;

            if (in_array($source, ['file', 'merge'])) {
                $extractedCv = $this->sourceResolver->fromUploadedFile(
                    $request->file('cv_file'),
                    $request->user()
                );
            } elseif ($source === 'saved_file' && $request->filled('file_record_id')) {
                $extractedCv = $this->sourceResolver->fromSavedFileRecord(
                    (int) $request->input('file_record_id'),
                    $request->user(),
                    false
                );
            }

            $result = $this->analysisService->analyze(
                user:             $request->user(),
                jobTitle:         $request->input('job_title'),
                jobDescription:   $request->input('job_description'),
                company:          $request->input('company', 'general'),
                extractedCv:      $extractedCv,
                mergeWithProfile: $source === 'merge',
            );

            return response()->json([
                'success' => true,
                'message' => 'CV analyzed successfully',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('CV Analysis Error', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze CV: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════
    //  POST /api/cv/enhance
    // ══════════════════════════════════════════════════
    public function enhance(Request $request)
    {
        $request->validate([
            'source'          => 'nullable|in:profile,file,payload,saved_file',
            'cv'              => 'nullable|array',
            'cv_file'         => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'file_record_id'  => 'nullable|integer|exists:cv_files,id',
            'job_title'       => 'nullable|string|max:500',
            'company'         => 'nullable|string|max:255',
            'job_description' => 'nullable|string',
            'save'            => 'nullable|boolean',
        ]);

        $source       = $request->input('source', 'profile');
        $saveImproved = (bool) $request->input('save', false);

        try {
            $fileRecordId = null;

            // ── جلب الـ CV حسب المصدر ──────────────────────
            if ($source === 'payload' && $request->filled('cv')) {

                $cv = $request->input('cv');

                // ✅ لو طالب الحفظ → أنشئ سجل جديد
                if ($saveImproved) {
                    $record = CvFile::create([
                        'user_id'       => $request->user()->id,
                        'original_name' => 'payload-cv-' . now()->format('Y-m-d-His'),
                        'stored_path'   => '',   // ← لا ملف فعلي
                        'disk'          => 'local',
                        'size'          => 0,
                        'mime_type'     => 'application/json',
                        'extracted_cv'  => $cv,
                        'improved_cv'   => null,
                        'is_confirmed'  => false,
                    ]);
                    $fileRecordId = $record->id;
                }

            } elseif ($source === 'file' && $request->hasFile('cv_file')) {

                $extracted    = $this->sourceResolver->fromUploadedFile(
                    $request->file('cv_file'),
                    $request->user()
                );
                $fileRecordId = $extracted['_file_record_id'] ?? null;
                $cv           = $extracted;

            } elseif ($source === 'saved_file' && $request->filled('file_record_id')) {

                $fileRecordId = (int) $request->input('file_record_id');
                // ✅ دايماً من extracted_cv الأصلي النظيف
                $cv = $this->sourceResolver->fromSavedFileRecord(
                    $fileRecordId,
                    $request->user(),
                    true
                );

            } else {

                // default: profile
                $cv = $this->sourceResolver->fromManualProfile($request->user());

                // ✅ لو طالب الحفظ → أنشئ سجل جديد
                if ($saveImproved) {
                    $record = CvFile::create([
                        'user_id'       => $request->user()->id,
                        'original_name' => 'profile-cv-' . now()->format('Y-m-d-His'),
                        'stored_path'   => '',   // ← لا ملف فعلي
                        'disk'          => 'local',
                        'size'          => 0,
                        'mime_type'     => 'application/json',
                        'extracted_cv'  => $cv,
                        'improved_cv'   => null,
                        'is_confirmed'  => false,
                    ]);
                    $fileRecordId = $record->id;
                }
            }

            // ── تطبيق التحسينات ──────────────────────────
            $enhanced = $this->enhancer->enhance(
                cv:             $cv,
                fileRecordId:   $fileRecordId,
                jobTitle:       $request->input('job_title'),
                company:        $request->input('company'),
                jobDescription: $request->input('job_description'),
                saveImproved:   $saveImproved && $fileRecordId !== null,
            );

            $savedMessage = ($saveImproved && $fileRecordId)
                ? 'وتم حفظ النسخة المحسّنة.'
                : 'لم يتم الحفظ — أرسل save=true لحفظها.';

            return response()->json([
                'success'                   => true,
                'message'                   => "تم تحسين الـ CV بنجاح. {$savedMessage}",
                'file_record_id'            => $fileRecordId,
                'saved'                     => $saveImproved && $fileRecordId !== null,
                'targeted'                  => !empty($request->input('job_title'))
                    || !empty($request->input('company')),
                'changes'                   => $enhanced['_changes'] ?? [],
                'suggested_skills_to_learn' => $enhanced['skills']['suggested_for_job'] ?? [],
                'original_cv'               => $cv,
                'enhanced_cv'               => $enhanced,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحسين: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════
    //  POST /api/cv/enhance/save
    // ══════════════════════════════════════════════════
    public function saveEnhanced(Request $request)
    {
        $request->validate([
            'file_record_id' => 'required|integer|exists:cv_files,id',
            'enhanced_cv'    => 'required|array',
        ]);

        $fileRecordId = (int) $request->input('file_record_id');

        try {
            // ✅ تحقق إن الملف تابع للمستخدم
            $record = CvFile::where('id', $fileRecordId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $saved = $this->enhancer->saveImprovedCv(
                $fileRecordId,
                $request->input('enhanced_cv')
            );

            return response()->json([
                'success'        => $saved,
                'message'        => $saved
                    ? 'تم حفظ الـ CV المحسّن بنجاح.'
                    : 'فشل الحفظ — حاول مرة أخرى.',
                'file_record_id' => $fileRecordId,
                'hint'           => $saved
                    ? 'يمكنك الآن تحميله كـ PDF عبر POST /api/cv/pdf مع source=saved_file وfile_record_id=' . $fileRecordId
                    : null,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود أو لا تملك صلاحية الوصول إليه.',
            ], 403);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل الحفظ: ' . $e->getMessage(),
            ], 500);
        }
    }
    // ══════════════════════════════════════════════════
    //  POST /api/cv/pdf
    // ══════════════════════════════════════════════════
    public function downloadPdf(Request $request)
    {
        $template = in_array($request->input('template'), ['ats', 'modern', 'creative'])
            ? $request->input('template') : 'ats';

        try {
            $source = $request->input('source', 'payload');

            if ($request->has('cv') && is_array($request->input('cv'))) {
                $cv = $this->getCvPayload($request->input('cv'));

            } elseif ($source === 'saved_file' && $request->filled('file_record_id')) {
                $cv = $this->sourceResolver->fromSavedFileRecord(
                    (int) $request->input('file_record_id'),
                    $request->user(),
                    false
                );

            } elseif ($source === 'file' && $request->hasFile('cv_file')) {
                $cv = $this->sourceResolver->fromUploadedFile(
                    $request->file('cv_file'),
                    $request->user()
                );

            } elseif ($source === 'profile') {
                $cv = $this->sourceResolver->fromManualProfile($request->user());

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'حدد مصدر الـ CV.',
                ], 422);
            }

            $cv       = $this->normalizeCvForPdf($cv);
            $pdf      = Pdf::loadView('pdf.cv', ['cv' => $cv, 'template' => $template])
                ->setPaper('a4', 'portrait');
            $name     = $cv['header']['name'] ?? ($request->user()->name ?? 'cv');
            $fileName = Str::slug($name) . '-cv.pdf';

            return $pdf->download($fileName);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'فشل توليد PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════
    //  POST /api/cv/match
    // ══════════════════════════════════════════════════
    public function match(Request $request)
    {
        $request->validate([
            'job_description' => 'required|string',
            'source'          => 'nullable|in:profile,saved_file',
            'file_record_id'  => 'nullable|integer|exists:cv_files,id',
        ]);

        try {
            // ✅ يدعم profile و saved_file
            if (
                $request->input('source') === 'saved_file'
                && $request->filled('file_record_id')
            ) {
                $cv = $this->sourceResolver->fromSavedFileRecord(
                    (int) $request->input('file_record_id'),
                    $request->user(),
                    false  // improved_cv لو موجود
                );
            } else {
                $cv = $this->sourceResolver->fromManualProfile($request->user());
            }

            $result = $this->jobMatchService->match(
                $cv,
                $request->input('job_description')
            );

            return response()->json([
                'success' => true,
                'result'  => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // ══════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════

    private function getCvPayload(mixed $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid CV JSON: ' . json_last_error_msg());
            }
            $payload = $decoded;
        }

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('CV payload يجب أن يكون array.');
        }

        if (isset($payload['result']['optimized_cv'])) return $payload['result']['optimized_cv'];
        if (isset($payload['optimized_cv']))            return $payload['optimized_cv'];

        return $payload;
    }

    private function normalizeCvForPdf(array $cv): array
    {
        $cv['header']            = $cv['header'] ?? [];
        $cv['header']['name']    = $cv['header']['name'] ?? '';
        $cv['header']['title']   = $cv['header']['title'] ?? '';
        $cv['header']['contact'] = $cv['header']['contact'] ?? [];
        $cv['summary']           = $cv['summary'] ?? '';
        $cv['skills']            = $cv['skills'] ?? [];
        $cv['skills']['all']     = array_values(array_unique(array_filter(array_merge(
            $cv['skills']['all']         ?? [],
            $cv['skills']['technical']   ?? [],
            $cv['skills']['tools']       ?? [],
            $cv['skills']['soft_skills'] ?? [],
            $cv['skills']['languages']   ?? [],
        ))));
        $cv['experience']     = $cv['experience']     ?? [];
        $cv['education']      = $cv['education']      ?? [];
        $cv['projects']       = $cv['projects']       ?? [];
        $cv['certifications'] = $cv['certifications'] ?? [];
        $cv['languages']      = $cv['languages']      ?? [];
        $cv['trainings']      = $cv['trainings']      ?? [];
        $cv['interests']      = $cv['interests']      ?? [];

        return $cv;
    }
}
