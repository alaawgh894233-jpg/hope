<?php

namespace App\Services;

use App\Models\Training;
use App\Models\User;

class TrainingService
{
    public function index(User $user)
    {
        return $user->trainings()->latest()->get();
    }

    public function store(User $user, array $data)
    {
        return Training::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'provider' => $data['provider'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_completed' => $data['is_completed'] ?? false,
            'description' => $data['description'] ?? null,
            'technologies' => $data['technologies'] ?? null,
        ]);
    }

    public function update(User $user, Training $training, array $data)
    {
        if ($training->user_id !== $user->id) {
            abort(403);
        }

        $training->update($data);

        return $training;
    }

    public function delete(User $user, Training $training)
    {
        if ($training->user_id !== $user->id) {
            abort(403);
        }

        $training->delete();

        return ['message' => 'Training deleted successfully'];
    }
}
