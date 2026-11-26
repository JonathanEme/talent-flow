<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InterviewIndexRequest;
use App\Http\Requests\Api\V1\InterviewStoreRequest;
use App\Http\Requests\Api\V1\InterviewUpdateRequest;
use App\Http\Resources\Api\V1\InterviewResource;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class InterviewController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(InterviewIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->scopeInterviews(Interview::query())->with([
            'application.candidate',
            'application.position',
            'interviewer',
            'feedbacks',
        ]);

        if (isset($validated['application_id'])) {
            $query->where('application_id', $validated['application_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['scheduled_date'])) {
            $query->whereDate('scheduled_at', $validated['scheduled_date']);
        }

        if (isset($validated['from'])) {
            $query->whereDate('scheduled_at', '>=', $validated['from']);
        }

        if (isset($validated['to'])) {
            $query->whereDate('scheduled_at', '<=', $validated['to']);
        }

        return $this->paginatedResponse(InterviewResource::collection($query->orderBy('scheduled_at', 'asc')->paginate(20)));
    }

    public function store(InterviewStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->ensureApplication(Application::findOrFail($validated['application_id']));

        if (isset($validated['interviewer_id'])) {
            $this->ensureCompany((int) User::findOrFail($validated['interviewer_id'])->company_id);
        }

        $interview = Interview::create(array_merge($validated, [
            'interviewer_id' => $validated['interviewer_id'] ?? auth()->id(),
        ]));

        $this->notificationService->interviewScheduled($interview, auth()->id());

        return response()->json([
            'data' => new InterviewResource($interview->load(['application.candidate', 'application.position', 'interviewer'])),
        ], 201);
    }

    public function show(Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        return response()->json([
            'data' => new InterviewResource($interview->load(['application.candidate', 'application.position', 'interviewer', 'feedbacks.evaluator'])),
        ]);
    }

    public function update(InterviewUpdateRequest $request, Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        $fromStatus = (string) $interview->status;
        $interview->update($request->validated());
        $interview = $interview->fresh(['application.candidate', 'application.position', 'interviewer', 'feedbacks']);

        $toStatus = (string) $interview->status;
        if ($fromStatus !== $toStatus) {
            $this->notificationService->interviewStatusChanged($interview, $fromStatus, $toStatus, auth()->id());
        }

        return response()->json([
            'data' => new InterviewResource($interview),
        ]);
    }
}
