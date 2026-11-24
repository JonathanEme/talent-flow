<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncCandidateSkillsRequest;
use App\Http\Resources\Api\V1\CandidateResource;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;

class CandidateSkillController extends Controller
{
    public function sync(SyncCandidateSkillsRequest $request, Candidate $candidate): JsonResponse
    {
        $this->ensureCandidate($candidate);

        $syncData = [];
        foreach ($request->validated('skills') as $skill) {
            $syncData[$skill['skill_id']] = [
                'years_experience' => $skill['years_experience'] ?? 1,
                'level' => $skill['level'] ?? 'intermediate',
            ];
        }

        $candidate->skills()->sync($syncData);

        return response()->json([
            'message' => 'Candidate skills updated successfully.',
            'data' => new CandidateResource($candidate->load('skills')),
        ]);
    }
}
