<?php

namespace App\Services;

use App\Models\Education;
use App\Models\User;

class EducationService
{
    public function create(User $user, array $data)
    {
        return Education::create([
            ...$data,
            'user_id' => $user->id
        ]);
    }

    public function update(
        User $user,
        int $id,
        array $data
    ) {
        $education = Education::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$education) {
            return null;
        }

        $education->update($data);

        return $education;
    }

    public function getAll(User $user)
    {
        return Education::where(
            'user_id',
            $user->id
        )->latest()->get();
    }

    public function getOne(
        User $user,
        int $id
    ) {
        return Education::where(
            'user_id',
            $user->id
        )->find($id);
    }

    public function delete(
        User $user,
        int $id
    ) {
        $education = Education::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$education) {
            return false;
        }

        return $education->delete();
    }
}
