<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStage;
use App\Enums\OfferStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OfferIndexRequest;
use App\Http\Requests\Api\V1\OfferStoreRequest;
use App\Http\Requests\Api\V1\OfferUpdateStatusRequest;
use App\Http\Resources\Api\V1\OfferResource;
use App\Models\Application;
use App\Models\Offer;
use App\Services\ApplicationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected NotificationService $notificationService
    ) {}

    public function index(OfferIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->scopeOffers(Offer::query())->with([
            'application.candidate',
            'application.position.company',
            'creator',
        ]);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['application_id'])) {
            $query->where('application_id', $validated['application_id']);
        }

        return $this->paginatedResponse(OfferResource::collection($query->latest()->paginate(15)));
    }

    public function store(OfferStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        $this->notificationService->offerCreated($offer, auth()->id());

        return response()->json([
            'message' => 'Offer created successfully.',
            'data' => new OfferResource($offer->load(['application.candidate', 'application.position', 'creator'])),
        ], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        $this->ensureOffer($offer);

        return response()->json([
            'data' => new OfferResource($offer->load(['application.candidate', 'application.position.company', 'creator'])),
        ]);
    }

    public function updateStatus(OfferUpdateStatusRequest $request, Offer $offer): JsonResponse
    {
        $validated = $request->validated();

        $this->ensureOffer($offer);

        $currentStatus = $offer->status instanceof OfferStatus ? $offer->status->value : (string) $offer->status;

        DB::transaction(function () use ($offer, $validated) {
            $current = $offer->status instanceof OfferStatus
                ? $offer->status
                : OfferStatus::from($offer->status);
            $newStatus = OfferStatus::from($validated['status']);

            if (! $current->canTransitionTo($newStatus)) {
                throw InvalidTransitionException::for('la oferta', $current->value, $newStatus->value);
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

        $offer = $offer->fresh(['application.candidate', 'application.position']);
        $toStatus = $offer->status instanceof OfferStatus ? $offer->status->value : (string) $offer->status;
        if ($currentStatus !== $toStatus) {
            $this->notificationService->offerStatusChanged($offer, $currentStatus, $toStatus, auth()->id());
        }

        return response()->json([
            'message' => 'Offer status updated successfully.',
            'data' => new OfferResource($offer),
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
