<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStage;
use App\Enums\OfferStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Offer;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeOffers(Offer::query())->with([
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
            'salary_offered' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'benefits_summary' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
        ]);

        $this->ensureApplication(Application::findOrFail($validated['application_id']));

        $offer = DB::transaction(function () use ($validated) {
            $offer = Offer::create(array_merge($validated, [
                'created_by' => auth()->id(),
                'status' => OfferStatus::Draft->value,
                'currency' => $validated['currency'] ?? 'USD',
            ]));

            $application = Application::findOrFail($validated['application_id']);
            $comment = 'Generada oferta formal por '.($validated['currency'] ?? 'USD').' '.number_format($validated['salary_offered'], 2);

            if ($application->stage !== ApplicationStage::Offer) {
                $application = $this->applicationService->transitionStage(
                    $application,
                    ApplicationStage::Offer,
                    auth()->id(),
                    $comment
                );
            }

            $this->applicationService->logEvent($application, 'offer_created', auth()->id(), $comment);

            return $offer;
        });

        return response()->json([
            'message' => 'Offer created successfully.',
            'data' => $offer->load(['application.candidate', 'application.position', 'creator']),
        ], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        $this->ensureOffer($offer);

        return response()->json([
            'data' => $offer->load(['application.candidate', 'application.position.company', 'creator']),
        ]);
    }

    public function updateStatus(Request $request, Offer $offer): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(OfferStatus::transitionalValues())],
            'decline_reason' => ['nullable', 'required_if:status,declined', 'string'],
        ]);

        $this->ensureOffer($offer);

        DB::transaction(function () use ($offer, $validated) {
            $currentStatus = $offer->status instanceof OfferStatus
                ? $offer->status
                : OfferStatus::from($offer->status);
            $newStatus = OfferStatus::from($validated['status']);

            if (! $currentStatus->canTransitionTo($newStatus)) {
                throw InvalidTransitionException::for('la oferta', $currentStatus->value, $newStatus->value);
            }

            $offer->update([
                'status' => $newStatus->value,
                'decline_reason' => $validated['decline_reason'] ?? null,
                'responded_at' => in_array($newStatus, [OfferStatus::Accepted, OfferStatus::Declined], true) ? now() : null,
            ]);

            $application = $offer->application;

            match ($newStatus) {
                OfferStatus::Sent => $this->applicationService->logEvent(
                    $application,
                    'offer_sent',
                    auth()->id(),
                    'Oferta formal enviada al candidato.'
                ),
                OfferStatus::Accepted => $this->completeOffer(
                    $application,
                    ApplicationStage::Hired,
                    'offer_accepted',
                    '¡Oferta aceptada por el candidato! Contratación confirmada.'
                ),
                OfferStatus::Declined => $this->completeOffer(
                    $application,
                    ApplicationStage::Rejected,
                    'offer_declined',
                    'Oferta declinada por el candidato. Motivo: '.($validated['decline_reason'] ?? 'No especificado'),
                    'Oferta declinada: '.($validated['decline_reason'] ?? 'Sin motivo especificado')
                ),
                OfferStatus::Draft => null,
            };
        });

        return response()->json([
            'message' => 'Offer status updated successfully.',
            'data' => $offer->fresh(['application.candidate', 'application.position']),
        ]);
    }

    private function completeOffer(
        Application $application,
        ApplicationStage $stage,
        string $event,
        string $comment,
        ?string $rejectionReason = null
    ): void {
        $attributes = $rejectionReason === null ? [] : ['rejection_reason' => $rejectionReason];

        $application = $this->applicationService->transitionStage(
            $application,
            $stage,
            auth()->id(),
            $comment,
            $attributes
        );

        $this->applicationService->logEvent($application, $event, auth()->id(), $comment);
    }
}
