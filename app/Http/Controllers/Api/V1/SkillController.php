<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Skill::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        return response()->json([
            'data' => $query->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:skills,name'],
            'category' => ['nullable', 'string', 'in:technical,soft_skill,language,tool'],
        ]);

        $skill = Skill::create($validated);

        return response()->json([
            'data' => $skill,
        ], 201);
    }
}