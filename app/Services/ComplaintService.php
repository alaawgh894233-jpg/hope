<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintService
{
    public function create($userId, array $data)
    {
        return Complaint::create([
            'user_id' => $userId,
            'type' => $data['type'],
            'reference_id' => $data['reference_id'],
            'message' => $data['message'],
            'status' => 'pending'
        ]);
    }

    public function getAll()
    {
        return Complaint::with('user')
            ->latest()
            ->paginate(10);
    }

    public function updateStatus($id, $status)
    {
        $complaint = Complaint::findOrFail($id);

        $complaint->update([
            'status' => $status
        ]);

        return $complaint;
    }

    public function delete($id)
    {
        return Complaint::findOrFail($id)->delete();
    }
}
