<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Offer;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Offer::query()->with([
            'application.candidate',
            'application.position.company',
            'creator',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('application_id')) {
            $query->where('application_id', $request->query('application_id'));
        }

        return response()->json([
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
            'created_by' => ['required', 'exists:users,id'],
            'salary_offered' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'benefits_summary' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
        ]);

        $offer = DB::transaction(function () use ($validated) {
            $offer = Offer::create(array_merge($validated, [
                'status' => 'draft',
                'currency' => $validated['currency'] ?? 'USD',
            ]));

            // Movemos la candidatura a la etapa 'offer' si aún no estaba
            $application = Application::findOrFail($validated['application_id']);
            if ($application->stage !== 'offer') {
                $this->applicationService->transitionStage(
                    $application,
                    'offer',
                    $validated['created_by'],
                    'Generada oferta formal por ' . ($validated['currency'] ?? 'USD') . ' ' . number_format($validated['salary_offered'], 2)
                );
            }

            return $offer;
        });

        return response()->json([
            'message' => 'Offer created successfully.',
            'data' => $offer->load(['application.candidate', 'application.position', 'creator']),
        ], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        return response()->json([
            'data' => $offer->load(['application.candidate', 'application.position.company', 'creator']),
        ]);
    }

    public function updateStatus(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:sent,accepted,declined'],
            'decline_reason' => ['nullable', 'required_if:status,declined', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($offer, $validated) {
            $offer->update([
                'status' => $validated['status'],
                'decline_reason' => $validated['decline_reason'] ?? null,
                'responded_at' => in_array($validated['status'], ['accepted', 'declined']) ? now() : null,
            ]);

            $application = $offer->application;

            if ($validated['status'] === 'accepted') {
                $application->update(['status' => 'hired']);
                $this->applicationService->transitionStage(
                    $application,
                    'offer',
                    $validated['user_id'] ?? null,
                    '¡Oferta aceptada por el candidato! Contratación confirmada.'
                );
            } elseif ($validated['status'] === 'declined') {
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Oferta declinada: ' . ($validated['decline_reason'] ?? 'Sin motivo especificado'),
                ]);
                $this->applicationService->transitionStage(
                    $application,
                    'offer',
                    $validated['user_id'] ?? null,
                    'Oferta declinada por el candidato. Motivo: ' . ($validated['decline_reason'] ?? 'No especificado')
                );
            } elseif ($validated['status'] === 'sent') {
                $this->applicationService->transitionStage(
                    $application,
                    'offer',
                    $validated['user_id'] ?? null,
                    'Oferta formal enviada al candidato.'
                );
            }
        });

        return response()->json([
            'message' => 'Offer status updated successfully.',
            'data' => $offer->fresh(['application.candidate', 'application.position']),
        ]);
    }
}