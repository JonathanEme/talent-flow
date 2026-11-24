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
use Illuminate\Http\JsonResponse;

class InterviewController extends Controller
{
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

        if (isset($validated['scheduled_date'])) {
            $query->whereDate('scheduled_at', $validated['scheduled_date']);
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

        $interview->update($request->validated());

        return response()->json([
            'data' => new InterviewResource($interview->fresh(['application.candidate', 'application.position', 'interviewer', 'feedbacks'])),
        ]);
    }
}
