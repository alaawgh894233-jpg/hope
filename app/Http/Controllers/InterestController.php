<?php

namespace App\Http\Controllers;

use App\Http\Requests\Interest\StoreInterestRequest;
use App\Http\Requests\Interest\UpdateInterestRequest;
use App\Models\Interest;
use App\Services\InterestService;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    public function __construct(
        protected InterestService $service
    ) {}

    public function store(StoreInterestRequest $request)
    {
        return response()->json(
            $this->service->store(
                $request->user(),
                $request->validated()
            )
        );
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->service->index($request->user())
        );
    }

    public function update(UpdateInterestRequest $request, Interest $interest)
    {
        return response()->json(
            $this->service->update(
                $request->user(),
                $interest,
                $request->validated()
            )
        );
    }

    public function destroy(Request $request, Interest $interest)
    {
        return response()->json(
            $this->service->delete(
                $request->user(),
                $interest
            )
        );
    }
}
