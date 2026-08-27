<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionRequirementController extends Controller
{
    public function sync(Request $request, Position $position): JsonResponse
    {
        $validated = $request->validate([
            'skills' => ['required', 'array'],
            'skills.*.skill_id' => ['required', 'exists:skills,id'],
            'skills.*.is_mandatory' => ['required', 'boolean'],
            'skills.*.min_years_experience' => ['nullable', 'integer', 'min:0'],
            'skills.*.expected_level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
        ]);

        $syncData = [];
        foreach ($validated['skills'] as $skill) {
            $syncData[$skill['skill_id']] = [
                'is_mandatory' => $skill['is_mandatory'],
                'min_years_experience' => $skill['min_years_experience'] ?? 1,
                'expected_level' => $skill['expected_level'] ?? 'intermediate',
            ];
        }

        $position->skills()->sync($syncData);

        return response()->json([
            'message' => 'Requirements updated successfully.',
            'data' => $position->load('skills'),
        ]);
    }
}