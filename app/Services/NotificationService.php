<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\User;
use App\Notifications\AtsEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function applicationCreated(Application $application, ?int $actorId = null): void
    {
        $application->loadMissing('candidate', 'position.company');

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId),
            NotificationCategory::ApplicationCreated,
            'Nueva postulación',
            sprintf('%s se postuló a %s.', $this->candidateName($application), $application->position->title),
            $this->pipelineUrl($application),
            $this->applicationData($application, $actorId)
        );
    }

    public function applicationStageChanged(Application $application, string $fromStage, string $toStage, ?int $actorId = null): void
    {
        $application->loadMissing('candidate', 'position.company');

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId),
            NotificationCategory::ApplicationStageChanged,
            'Cambio de etapa',
            sprintf('%s pasó de %s a %s.', $this->candidateName($application), $fromStage, $toStage),
            $this->pipelineUrl($application),
            array_merge($this->applicationData($application, $actorId), [
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
            ])
        );
    }

    public function interviewScheduled(Interview $interview, ?int $actorId = null): void
    {
        $interview->loadMissing('application.candidate', 'application.position.company', 'interviewer');

        $application = $interview->application;

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId, [$interview->interviewer_id], true),
            NotificationCategory::InterviewScheduled,
            'Entrevista programada',
            sprintf('Se programó una entrevista de %s para %s.', $interview->type, $this->candidateName($application)),
            '/interviews',
            array_merge($this->applicationData($application, $actorId), [
                'interview_id' => $interview->id,
                'interview_type' => $interview->type,
                'scheduled_at' => optional($interview->scheduled_at)->toAtomString(),
            ])
        );
    }

    public function interviewStatusChanged(Interview $interview, string $fromStatus, string $toStatus, ?int $actorId = null): void
    {
        $interview->loadMissing('application.candidate', 'application.position.company', 'interviewer');

        $application = $interview->application;

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId, [$interview->interviewer_id]),
            NotificationCategory::InterviewStatusChanged,
            'Estado de entrevista actualizado',
            sprintf('La entrevista de %s cambió de %s a %s.', $this->candidateName($application), $fromStatus, $toStatus),
            '/interviews',
            array_merge($this->applicationData($application, $actorId), [
                'interview_id' => $interview->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ])
        );
    }

    public function interviewFeedbackSubmitted(Interview $interview, ?int $actorId = null): void
    {
        $interview->loadMissing('application.candidate', 'application.position.company', 'interviewer');

        $application = $interview->application;

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId, [$interview->interviewer_id]),
            NotificationCategory::InterviewFeedbackSubmitted,
            'Feedback de entrevista recibido',
            sprintf('Se registró feedback para la entrevista de %s.', $this->candidateName($application)),
            "/applications/{$application->id}/timeline",
            array_merge($this->applicationData($application, $actorId), [
                'interview_id' => $interview->id,
            ])
        );
    }

    public function offerCreated(Offer $offer, ?int $actorId = null): void
    {
        $offer->loadMissing('application.candidate', 'application.position.company');

        $application = $offer->application;

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId),
            NotificationCategory::OfferCreated,
            'Oferta creada',
            sprintf('Se creó una oferta para %s.', $this->candidateName($application)),
            '/offers',
            array_merge($this->applicationData($application, $actorId), $this->offerData($offer))
        );
    }

    public function offerStatusChanged(Offer $offer, string $fromStatus, string $toStatus, ?int $actorId = null): void
    {
        $offer->loadMissing('application.candidate', 'application.position.company');

        $application = $offer->application;

        $this->send(
            $this->companyRecipients($application->position->company_id, $actorId),
            NotificationCategory::OfferStatusChanged,
            'Estado de oferta actualizado',
            sprintf('La oferta de %s cambió de %s a %s.', $this->candidateName($application), $fromStatus, $toStatus),
            '/offers',
            array_merge($this->applicationData($application, $actorId), $this->offerData($offer), [
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ])
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $data
     */
    private function send(Collection $recipients, NotificationCategory $category, string $title, string $message, string $url, array $data): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AtsEventNotification($category, $title, $message, $url, $data));
    }

    /**
     * @param  array<int, int|null>  $extraUserIds
     * @return Collection<int, User>
     */
    private function companyRecipients(int $companyId, ?int $actorId, array $extraUserIds = [], bool $includeCompanyMembers = false): Collection
    {
        $recipientIds = User::query()
            ->where(function ($query) use ($companyId, $includeCompanyMembers): void {
                $query->where('company_id', $companyId)
                    ->whereIn('role', $includeCompanyMembers ? ['company_admin', 'member'] : ['company_admin'])
                    ->orWhere('role', 'global_admin');
            })
            ->pluck('id')
            ->all();

        $recipientIds = array_merge($recipientIds, array_filter($extraUserIds));

        $recipientIds = array_values(array_unique(array_filter(
            $recipientIds,
            fn ($id): bool => $id !== null && $id !== $actorId
        )));

        return User::query()->whereIn('id', $recipientIds)->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationData(Application $application, ?int $actorId): array
    {
        return [
            'company_id' => $application->position->company_id,
            'application_id' => $application->id,
            'candidate_name' => $this->candidateName($application),
            'position_title' => $application->position->title,
            'actor_id' => $actorId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offerData(Offer $offer): array
    {
        return [
            'offer_id' => $offer->id,
            'offer_status' => $offer->status instanceof \BackedEnum ? $offer->status->value : $offer->status,
            'salary_offered' => $offer->salary_offered,
            'currency' => $offer->currency,
        ];
    }

    private function candidateName(Application $application): string
    {
        return trim(($application->candidate->first_name ?? '').' '.($application->candidate->last_name ?? ''));
    }

    private function pipelineUrl(Application $application): string
    {
        return "/pipeline?position={$application->position_id}&application={$application->id}";
    }
}
