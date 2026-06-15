<?php

namespace App\Http\Controllers;

use App\Services\CvBuilderService;
use Illuminate\Http\Request;
use App\Services\CvAnalysisService;
use App\Services\JobMatchService;

class CvAnalysisController extends Controller
{
    public function __construct(
        protected CvAnalysisService $analysisService,
        protected JobMatchService $jobMatchService,
        protected CvBuilderService $service,

    ) {}



    public function build(Request $request)
    {
        $user = $request->user();

        $cv = $this->service->build($user);

        return response()->json([
            'success' => true,
            'cv' => $cv
        ]);
    }
    public function analyze(Request $request)
    {
        $result = $this->analysisService->analyze(
            $request->user(),
            $request->job_title,
            $request->job_description
        );

        return response()->json([
            'success' => true,
            'message' => 'CV analyzed successfully',
            'result' => $result
        ]);
    }



    public function downloadPdf(Request $request)
    {
        $template = $request->template ?? 'ats';

        if (!in_array($template, [
            'ats',
            'modern',
            'creative'
        ])) {
            $template = 'ats';
        }

        $analysis = $this->analysisService->analyze($request->user());

        $file = $this->analysisService->generatePdf(
            $analysis['cv'],
            $request->user()->id,
            $template
        );

        return response()->json([
            'success' => true,
            'message' => 'PDF generated successfully',
            'path' => $file
        ]);
    }

public function match(Request $request)
 {
     $request->validate([
         'job_description' => 'required|string'
     ]);

    $analysis = $this->analysisService->analyze($request->user());

    $result = $this->jobMatchService->match(
         $analysis['cv'],
         $request->job_description
     );

    return response()->json([
         'success' => true,
         'result' => $result
     ]);
 }



}
