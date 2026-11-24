<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeInterviews(Interview::query())->with([
            'application.candidate',
            'application.position',
            'interviewer',
            'feedbacks',
        ]);

        if ($request->filled('application_id')) {
            $query->where('application_id', $request->query('application_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_at', $request->query('scheduled_date'));
        }

        return response()->json([
            'data' => $query->orderBy('scheduled_at', 'asc')->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'in:screening,hr,technical,client,final'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'location_or_link' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->ensureApplication(Application::findOrFail($validated['application_id']));

        $interview = Interview::create(array_merge($validated, [
            'interviewer_id' => $validated['interviewer_id'] ?? auth()->id(),
        ]));

        return response()->json([
            'data' => $interview->load(['application.candidate', 'application.position', 'interviewer']),
        ], 201);
    }

    public function show(Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        return response()->json([
            'data' => $interview->load(['application.candidate', 'application.position', 'interviewer', 'feedbacks.evaluator']),
        ]);
    }

    public function update(Request $request, Interview $interview): JsonResponse
    {
        $this->ensureInterview($interview);

        $validated = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'location_or_link' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:scheduled,completed,canceled,no_show'],
            'notes' => ['nullable', 'string'],
        ]);

        $interview->update($validated);

        return response()->json([
            'data' => $interview->fresh(['application.candidate', 'interviewer', 'feedbacks']),
        ]);
    }
}
