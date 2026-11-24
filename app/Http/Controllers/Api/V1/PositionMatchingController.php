<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PositionRecommendationsRequest;
use App\Http\Resources\Api\V1\MatchResource;
use App\Models\Candidate;
use App\Models\Position;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;

class PositionMatchingController extends Controller
{
    public function __construct(
        protected MatchingService $matchingService
    ) {}

    public function recommendations(PositionRecommendationsRequest $request, Position $position): JsonResponse
    {
        $this->ensurePosition($position);

        $limit = $request->validated('limit') ?? 15;
        $recommendations = $this->matchingService->getRecommendationsForPosition($position, $limit);

        return response()->json([
            'position' => [
                'id' => $position->id,
                'title' => $position->title,
                'total_requirements' => $position->skills()->count(),
            ],
            'data' => MatchResource::collection($recommendations),
        ]);
    }

    public function compare(Position $position, Candidate $candidate): JsonResponse
    {
        $this->ensurePosition($position);
        $this->ensureCandidate($candidate);

        $position->load('skills');
        $candidate->load('skills');

        return response()->json([
            'data' => new MatchResource($this->matchingService->calculateMatch($candidate, $position)),
        ]);
    }
}
