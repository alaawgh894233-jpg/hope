<?php

namespace App\Http\Controllers;

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
}
