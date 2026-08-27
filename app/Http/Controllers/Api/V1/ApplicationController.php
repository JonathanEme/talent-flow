<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Application::query()->with(['candidate', 'position.company', 'stageLogs.user']);

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
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $exists = Application::where('position_id', $validated['position_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'The candidate is already applied to this position.',
            ], 422);
        }

        $initialStage = $validated['stage'] ?? 'new';

        $application = Application::create([
            'position_id' => $validated['position_id'],
            'candidate_id' => $validated['candidate_id'],
            'stage' => $initialStage,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
            'applied_at' => $validated['applied_at'] ?? now()->toDateString(),
        ]);

        // Registrar el primer estado en el log
        $this->applicationService->transitionStage(
            $application,
            $initialStage,
            $validated['user_id'] ?? null,
            'Postulación inicial registrada.'
        );

        return response()->json([
            'data' => $application->load(['candidate', 'position.company']),
        ], 201);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json([
            'data' => $application->load(['candidate', 'position.company', 'stageLogs.user', 'interviews.feedback']),
        ]);
    }

    public function updateStage(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,screening,technical_interview,final_interview,offer,rejected,hired'],
            'user_id' => ['nullable', 'exists:users,id'],
            'comment' => ['nullable', 'string'],
        ]);

        $updatedApplication = $this->applicationService->transitionStage(
            $application,
            $validated['stage'],
            $validated['user_id'] ?? null,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Stage updated and logged successfully.',
            'data' => $updatedApplication,
        ]);
    }

    public function timeline(Application $application): JsonResponse
    {
        $timeline = $this->applicationService->getUnifiedTimeline($application);

        return response()->json([
            'application_id' => $application->id,
            'candidate' => "{$application->candidate->first_name} {$application->candidate->last_name}",
            'position' => $application->position->title,
            'current_stage' => $application->stage,
            'timeline' => $timeline,
        ]);
    }
}