<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewFeedbackController extends Controller
{
    public function store(Request $request, Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:10'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'general_notes' => ['nullable', 'string'],
            'recommendation' => ['required', 'in:approved,rejected,on_hold'],
        ]);

        $feedback = InterviewFeedback::updateOrCreate(
            [
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
            ],
            $validated
        );

        // Si se carga feedback, marcamos la entrevista como completada automáticamente
        $interview->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback->load('evaluator'),
        ], 201);
    }
}