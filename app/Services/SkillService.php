<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;

class SkillService
{
    public function store(User $user, array $data)
    {
        return $user->skills()->create($data);
    }

    public function index(User $user)
    {
        return $user->skills()
            ->latest()
            ->get();
    }

    public function update(User $user, Skill $skill, array $data)
    {
        $skill = $user->skills()->findOrFail($skill->id);

        $skill->update($data);

        return $skill;
    }

    public function delete(User $user, Skill $skill)
    {
        $skill = $user->skills()->findOrFail($skill->id);

        $skill->delete();

        return [
            'message' => 'Skill deleted successfully'
        ];
    }
}
