<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BlockController extends Controller
{
    private array $map = [
        'user'    => User::class,
        'company' => Company::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $blocks = $request->user()
            ->blocks()
            ->with('blockable')
            ->latest()
            ->paginate(20);

        return response()->json($blocks);
    }


    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blockable_type' => 'required|in:user,company',
            'blockable_id'   => 'required|integer',
        ]);

        $modelClass = $this->map[$data['blockable_type']];
        $target = $modelClass::findOrFail($data['blockable_id']);


        if ($data['blockable_type'] === 'user' && $target->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'blockable_id' => 'لا يمكنك حظر حسابك أنت.',
            ]);
        }


        $block = $request->user()->blocks()->firstOrCreate([
            'blockable_type' => $modelClass,
            'blockable_id' => $target->id,
        ]);

        return response()->json([
            'message' => $block->wasRecentlyCreated
                ? 'تم الحظر بنجاح.'
                : 'هذا العنصر محظور بالفعل.',
            'data' => $block,
        ], $block->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $block = $request->user()->blocks()->findOrFail($id);
        $block->delete();

        return response()->json(['message' => 'تم إلغاء الحظر.']);
    }


    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blockable_type' => 'required|in:user,company',
            'blockable_id'   => 'required|integer',
        ]);

        $modelClass = $this->map[$data['blockable_type']];

        $isBlocked = $request->user()->blocks()
            ->where('blockable_type', $modelClass)
            ->where('blockable_id', $data['blockable_id'])
            ->exists();

        return response()->json(['is_blocked' => $isBlocked]);
    }
}
