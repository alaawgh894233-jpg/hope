<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateUserDataExport;
use App\Models\DataExportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataExportController
{

    public function store(Request $r)
    {
        $req = DataExportRequest::create(['user_id' => auth()->id(), 'status' => 'pending']);
        GenerateUserDataExport::dispatch($req);
        return response()->json(['message' => 'طلبك قيد المعالجة، رح يصلك إيميل فيه رابط التحميل', 'id' => $req->id]);
    }

    public function download(DataExportRequest $req)
    {
        abort_if($req->user_id !== auth()->id() || $req->status !== 'ready' || $req->expires_at < now(), 403);
        return Storage::disk('local')->download($req->file_path);
    }
    public function status()
    {
        $request = DataExportRequest::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$request) {
            return response()->json([
                'message' => 'لا يوجد طلب تصدير.',
            ], 404);
        }

        return response()->json($request);
    }
}
