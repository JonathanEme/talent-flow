<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Position;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionMatchingController extends Controller
{
    public function __construct(
        protected MatchingService $matchingService
    ) {}

    /**
     * Ranking de candidatos recomendados para una posición.
     */
    public function recommendations(Request $request, Position $position): JsonResponse
    {
        $this->ensurePosition($position);

        $limit = (int) $request->query('limit', 15);
        $recommendations = $this->matchingService->getRecommendationsForPosition($position, $limit);

        return response()->json([
            'position' => [
                'id' => $position->id,
                'title' => $position->title,
                'total_requirements' => $position->skills()->count(),
            ],
            'data' => $recommendations,
        ]);
    }

    /**
     * Comparativa detallada 1 a 1 entre un candidato y una vacante.
     */
    public function compare(Position $position, Candidate $candidate): JsonResponse
    {
        $this->ensurePosition($position);

        $position->load('skills');
        $candidate->load('skills');

        $matchResult = $this->matchingService->calculateMatch($candidate, $position);

        return response()->json([
            'data' => $matchResult,
        ]);
    }
}