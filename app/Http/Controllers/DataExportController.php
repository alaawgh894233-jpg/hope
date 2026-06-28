<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateUserDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataExportController
{
// app/Http/Controllers/DataExportController.php
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
}
