<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncPositionRequirementsRequest;
use App\Http\Resources\Api\V1\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionRequirementController extends Controller
{
    public function sync(SyncPositionRequirementsRequest $request, Position $position): JsonResponse
    {
        $this->ensurePosition($position);

        $syncData = [];
        foreach ($request->validated('skills') as $skill) {
            $syncData[$skill['skill_id']] = [
                'is_mandatory' => $skill['is_mandatory'],
                'min_years_experience' => $skill['min_years_experience'] ?? 1,
                'expected_level' => $skill['expected_level'] ?? 'intermediate',
            ];
        }

        $position->skills()->sync($syncData);

        return response()->json([
            'message' => 'Requirements updated successfully.',
            'data' => new PositionResource($position->load('skills')),
        ]);
    }
}
