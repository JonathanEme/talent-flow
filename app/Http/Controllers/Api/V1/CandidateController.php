<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CandidateIndexRequest;
use App\Http\Requests\Api\V1\CandidateStoreRequest;
use App\Http\Resources\Api\V1\CandidateResource;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;

class CandidateController extends Controller
{
    public function index(CandidateIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->scopeCandidates(Candidate::query());

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($validated['location'])) {
            $query->where('location', 'like', '%'.$validated['location'].'%');
        }

        if (isset($validated['skill'])) {
            $skill = $validated['skill'];
            $query->whereHas('skills', function ($query) use ($skill): void {
                $query->where('name', 'like', "%{$skill}%");
            });
        }

        return $this->paginatedResponse(CandidateResource::collection($query->latest()->paginate(15)));
    }

    public function store(CandidateStoreRequest $request): JsonResponse
    {
        $candidate = Candidate::create($request->validated());

        return response()->json([
            'data' => new CandidateResource($candidate),
        ], 201);
    }

    public function show(Candidate $candidate): JsonResponse
    {
        $this->ensureCandidate($candidate);

        return response()->json([
            'data' => new CandidateResource($candidate->load(['applications.position.company', 'skills'])),
        ]);
    }
}
