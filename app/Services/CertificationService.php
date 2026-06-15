<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\User;

class CertificationService
{
    public function create(User $user, array $data)
    {
        return Certification::create([
            ...$data,
            'user_id' => $user->id
        ]);
    }

    public function update(
        User $user,
        int $id,
        array $data
    ) {
        $certification = Certification::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$certification) {
            return null;
        }

        $certification->update($data);

        return $certification;
    }

    public function getAll(User $user)
    {
        return Certification::where(
            'user_id',
            $user->id
        )->latest()->get();
    }

    public function getOne(
        User $user,
        int $id
    ) {
        return Certification::where(
            'user_id',
            $user->id
        )->find($id);
    }

    public function delete(
        User $user,
        int $id
    ) {
        $certification = Certification::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$certification) {
            return false;
        }

        return $certification->delete();
    }
}
