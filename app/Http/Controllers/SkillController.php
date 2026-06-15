<?php

namespace App\Http\Controllers;

use App\Http\Requests\Skill\StoreSkillRequest;
use App\Http\Requests\Skill\UpdateSkillRequest;
use App\Models\Skill;
use App\Services\SkillService;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function __construct(
        protected SkillService $service
    ) {}

    // CREATE
    public function store(StoreSkillRequest $request)
    {
        return response()->json(
            $this->service->store(
                $request->user(),
                $request->validated()
            )
        );
    }

    // INDEX
    public function index(Request $request)
    {
        return response()->json(
            $this->service->index($request->user())
        );
    }

    // UPDATE
    public function update(
        UpdateSkillRequest $request,
        Skill $skill
    ) {
        return response()->json(
            $this->service->update(
                $request->user(),
                $skill,
                $request->validated()
            )
        );
    }

    // DELETE
    public function destroy(
        Request $request,
        Skill $skill
    ) {
        return response()->json(
            $this->service->delete(
                $request->user(),
                $skill
            )
        );
    }
}
