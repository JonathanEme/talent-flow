<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApplicationIndexRequest;
use App\Http\Requests\Api\V1\ApplicationStoreRequest;
use App\Http\Requests\Api\V1\UpdateApplicationStageRequest;
use App\Http\Resources\Api\V1\ApplicationResource;
use App\Http\Resources\Api\V1\TimelineResource;
use App\Models\Application;
use App\Models\Position;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;

class ApplicationController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService
    ) {}

    public function index(ApplicationIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->scopeApplications(Application::query())->with(['candidate', 'position.company', 'stageLogs.user']);

        if (isset($validated['position_id'])) {
            $query->where('position_id', $validated['position_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['stage'])) {
            $query->where('stage', $validated['stage']);
        }

        return $this->paginatedResponse(ApplicationResource::collection($query->latest()->paginate(20)));
    }

    public function store(ApplicationStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->ensureCompany((int) Position::findOrFail($validated['position_id'])->company_id);

        $exists = Application::where('position_id', $validated['position_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'The candidate is already applied to this position.',
            ], 422);
        }

        $application = Application::create([
            'position_id' => $validated['position_id'],
            'candidate_id' => $validated['candidate_id'],
            'stage' => $validated['stage'] ?? ApplicationStage::New->value,
            'status' => ApplicationStatus::Active->value,
            'notes' => $validated['notes'] ?? null,
            'applied_at' => $validated['applied_at'] ?? now()->toDateString(),
        ]);

        $application = $this->applicationService->initialize(
            $application,
            auth()->id(),
            'Postulación inicial registrada.'
        );

        return response()->json([
            'data' => new ApplicationResource($application->load(['candidate', 'position.company'])),
        ], 201);
    }

    public function show(Application $application): JsonResponse
    {
        $this->ensureApplication($application);

        return response()->json([
            'data' => new ApplicationResource($application->load(['candidate', 'position.company', 'stageLogs.user', 'interviews.feedbacks'])),
        ]);
    }

    public function updateStage(UpdateApplicationStageRequest $request, Application $application): JsonResponse
    {
        $validated = $request->validated();

        $this->ensureApplication($application);

        $updatedApplication = $this->applicationService->transitionStage(
            $application,
            $validated['stage'],
            auth()->id(),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Stage updated and logged successfully.',
            'data' => new ApplicationResource($updatedApplication),
        ]);
    }

    public function timeline(Application $application): JsonResponse
    {
        $this->ensureApplication($application);

        return (new TimelineResource([
            'application_id' => $application->id,
            'candidate' => "{$application->candidate->first_name} {$application->candidate->last_name}",
            'position' => $application->position->title,
            'current_stage' => $application->stage->value,
            'timeline' => $this->applicationService->getUnifiedTimeline($application),
        ]))->response();
    }
}
