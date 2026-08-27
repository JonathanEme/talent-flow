<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Application::query()->with(['candidate', 'position.company']);

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->query('position_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->query('stage'));
        }

        return response()->json([
            'data' => $query->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
            'stage' => ['nullable', 'string', 'in:new,screening,technical_interview,final_interview,offer'],
            'notes' => ['nullable', 'string'],
            'applied_at' => ['nullable', 'date'],
        ]);

        // Evitar duplicados a nivel validación
        $exists = Application::where('position_id', $validated['position_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'The candidate is already applied to this position.',
            ], 422);
        }

        $validated['applied_at'] = $validated['applied_at'] ?? now()->toDateString();
        $validated['status'] = 'active';

        $application = Application::create($validated);

        return response()->json([
            'data' => $application->load(['candidate', 'position.company']),
        ], 201);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json([
            'data' => $application->load(['candidate', 'position.company']),
        ]);
    }

    public function updateStage(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,screening,technical_interview,final_interview,offer'],
            'notes' => ['nullable', 'string'],
        ]);

        $application->update($validated);

        return response()->json([
            'data' => $application->load(['candidate', 'position.company']),
            'message' => 'Stage updated successfully.',
        ]);
    }
}