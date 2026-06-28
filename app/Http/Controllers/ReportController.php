<?php

namespace App\Http\Controllers;
use App\Models\Report;
use Carbon\Carbon;
use App\Models\Comment;
use App\Models\JobPost;
use Illuminate\Http\Request;

class ReportController
{

    public function store(Request $r)
    {
        $data = $r->validate([
            'reportable_type' => 'required|in:job_post,comment',
            'reportable_id'   => 'required|integer',
            'reason'          => 'required|in:spam,scam,fake_job,inappropriate,harassment,other',
            'details'         => 'nullable|string|max:1000',
        ]);

        $map = ['job_post' => JobPost::class, 'comment' => Comment::class];
        $model = $map[$data['reportable_type']]::findOrFail($data['reportable_id']);

        $report = $model->reports()->create([
            'reporter_id' => auth()->id(),
            'reason'      => $data['reason'],
            'details'     => $data['details'] ?? null,
        ]);

        return response()->json($report, 201);
    }
    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => 'required|in:reviewed,dismissed,action_taken',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Report resolved successfully.',
            'report' => $report->load([
                'reporter',
                'reviewer',
                'reportable',
            ]),
        ]);
    }

    public function index(Request $request)
    {
    $reports = Report::with([
        'reporter:id,name,email',
        'reportable'
    ])
        ->when($request->status, function ($query) use ($request) {
            $query->where('status', $request->status);
        })
        ->latest()
        ->paginate(10);

    return response()->json($reports);
}
}
