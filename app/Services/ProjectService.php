<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

class ProjectService
{
    public function create(User $user, array $data)
    {
        return Project::create([
            ...$data,
            'user_id' => $user->id
        ]);
    }

    public function update(
        User $user,
        int $id,
        array $data
    ) {
        $project = Project::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$project) {
            return null;
        }

        $project->update($data);

        return $project;
    }

    public function getAll(User $user)
    {
        return Project::where(
            'user_id',
            $user->id
        )->latest()->get();
    }

    public function getOne(
        User $user,
        int $id
    ) {
        return Project::where(
            'user_id',
            $user->id
        )->find($id);
    }

    public function delete(
        User $user,
        int $id
    ) {
        $project = Project::where(
            'user_id',
            $user->id
        )->find($id);

        if (!$project) {
            return false;
        }

        return $project->delete();
    }
}
