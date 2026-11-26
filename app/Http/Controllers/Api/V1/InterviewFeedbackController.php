<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InterviewFeedbackStoreRequest;
use App\Http\Resources\Api\V1\InterviewFeedbackResource;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class InterviewFeedbackController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function store(InterviewFeedbackStoreRequest $request, Interview $interview): JsonResponse
    {
        Gate::authorize('submit-interview-feedback', $interview);

        $this->ensureInterview($interview);

        $feedback = InterviewFeedback::updateOrCreate(
            [
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
            ],
            $request->validated()
        );

        $interview->update(['status' => 'completed']);

        $this->notificationService->interviewFeedbackSubmitted($interview, auth()->id());

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => new InterviewFeedbackResource($feedback->load('evaluator')),
        ], 201);
    }
}
