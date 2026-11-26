<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PositionIndexRequest;
use App\Http\Requests\Api\V1\PositionStoreRequest;
use App\Http\Resources\Api\V1\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    public function index(PositionIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->scopePositions(Position::query())->with('company');

        if (isset($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['seniority'])) {
            $query->where('seniority', $validated['seniority']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $this->paginatedResponse(PositionResource::collection($query->latest()->paginate(15)));
    }

    public function store(PositionStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->ensureCompany((int) $validated['company_id']);

        $position = Position::create($validated);

        return response()->json([
            'data' => new PositionResource($position->load('company')),
        ], 201);
    }

    public function show(Position $position): JsonResponse
    {
        $this->ensurePosition($position);

        return response()->json([
            'data' => new PositionResource($position->load('company')),
        ]);
    }
}
