<?php

namespace App\Services;

use App\Models\Experience;
use App\Models\User;

class ExperienceService
{
    public function create(User $user, array $data)
    {
        return Experience::create([
            ...$data,
            'user_id' => $user->id
        ]);
    }

    public function update(User $user, int $id, array $data)
    {
        $experience = Experience::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$experience) {
            return null;
        }

        $experience->update($data);

        return $experience;
    }

    public function getAll(User $user)
    {
        return Experience::where(
            'user_id',
            $user->id
        )->latest()->get();
    }

    public function getOne(User $user, int $id)
    {
        return Experience::where(
            'user_id',
            $user->id
        )->find($id);
    }

    public function delete(User $user, int $id)
    {
        $experience = Experience::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$experience) {
            return false;
        }

        return $experience->delete();
    }
}
