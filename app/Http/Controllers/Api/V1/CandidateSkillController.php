<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateSkillController extends Controller
{
    public function sync(Request $request, Candidate $candidate): JsonResponse
    {
        $validated = $request->validate([
            'skills' => ['required', 'array'],
            'skills.*.skill_id' => ['required', 'exists:skills,id'],
            'skills.*.years_experience' => ['nullable', 'integer', 'min:0'],
            'skills.*.level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
        ]);

        $syncData = [];
        foreach ($validated['skills'] as $skill) {
            $syncData[$skill['skill_id']] = [
                'years_experience' => $skill['years_experience'] ?? 1,
                'level' => $skill['level'] ?? 'intermediate',
            ];
        }

        $candidate->skills()->sync($syncData);

        return response()->json([
            'message' => 'Candidate skills updated successfully.',
            'data' => $candidate->load('skills'),
        ]);
    }
}