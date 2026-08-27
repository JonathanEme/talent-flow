<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Position::query()->with('company');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'seniority' => ['required', 'string', 'max:50'],
            'modality' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:150'],
            'employment_type' => ['required', 'string', 'max:50'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'opened_at' => ['required', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $position = Position::create($validated);

        return response()->json([
            'data' => $position->load('company'),
        ], 201);
    }

    public function show(Position $position): JsonResponse
    {
        return response()->json([
            'data' => $position->load('company'),
        ]);
    }
}