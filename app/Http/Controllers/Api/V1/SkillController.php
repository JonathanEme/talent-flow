<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SkillIndexRequest;
use App\Http\Requests\Api\V1\SkillStoreRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    public function index(SkillIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Skill::query();

        if (isset($validated['search'])) {
            $query->where('name', 'like', '%'.$validated['search'].'%');
        }

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        return $this->paginatedResponse(SkillResource::collection($query->orderBy('name')->paginate(30)));
    }

    public function store(SkillStoreRequest $request): JsonResponse
    {
        $skill = Skill::create($request->validated());

        return response()->json([
            'data' => new SkillResource($skill),
        ], 201);
    }
}
