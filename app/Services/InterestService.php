<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\User;

class InterestService
{
    public function store(User $user, array $data)
    {
        return Interest::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'category' => $data['category'] ?? 'other',
            'level' => $data['level'] ?? 1,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function index(User $user)
    {
        return $user->interests()->latest()->get();
    }

    public function update(User $user, Interest $interest, array $data)
    {
        if ($interest->user_id !== $user->id) {
            abort(403);
        }

        $interest->update($data);

        return $interest;
    }

    public function delete(User $user, Interest $interest)
    {
        if ($interest->user_id !== $user->id) {
            abort(403);
        }

        $interest->delete();

        return ['message' => 'Interest deleted successfully'];
    }
}
