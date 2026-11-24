<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InterviewFeedbackStoreRequest;
use App\Http\Resources\Api\V1\InterviewFeedbackResource;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use Illuminate\Http\JsonResponse;

class InterviewFeedbackController extends Controller
{
    public function store(InterviewFeedbackStoreRequest $request, Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        $feedback = InterviewFeedback::updateOrCreate(
            [
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
            ],
            $request->validated()
        );

        $interview->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => new InterviewFeedbackResource($feedback->load('evaluator')),
        ], 201);
    }
}
