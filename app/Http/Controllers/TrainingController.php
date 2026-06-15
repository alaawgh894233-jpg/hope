<?php

namespace App\Http\Controllers;

use App\Http\Requests\Training\StoreTrainingRequest;
use App\Http\Requests\Training\UpdateTrainingRequest;
use App\Models\Training;
use App\Services\TrainingService;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingService $service
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->service->index($request->user())
        );
    }

    public function store(StoreTrainingRequest $request)
    {
        return response()->json(
            $this->service->store(
                $request->user(),
                $request->validated()
            )
        );
    }

    public function update(UpdateTrainingRequest $request, Training $training)
    {
        return response()->json(
            $this->service->update(
                $request->user(),
                $training,
                $request->validated()
            )
        );
    }

    public function destroy(Request $request, Training $training)
    {
        return response()->json(
            $this->service->delete(
                $request->user(),
                $training
            )
        );
    }
}
