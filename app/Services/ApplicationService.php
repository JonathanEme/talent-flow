<?php

namespace App\Services;

use App\Enums\ApplicationStage;
use App\Exceptions\InvalidTransitionException;
use App\Models\Application;
use App\Models\ApplicationStageLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    public function initialize(Application $application, ?int $userId = null, ?string $comment = null): Application
    {
        return DB::transaction(function () use ($application, $userId, $comment): Application {
            $locked = $this->lockApplication($application);

            $alreadyLogged = $locked->stageLogs()
                ->whereNull('from_stage')
                ->where('to_stage', $locked->stage->value)
                ->exists();

            if (! $alreadyLogged) {
                $this->createLog($locked, null, $locked->stage, $userId, $comment);
            }

            return $this->freshApplication($locked);
        });
    }

    public function transitionStage(
        Application $application,
        ApplicationStage|string $newStage,
        ?int $userId = null,
        ?string $comment = null,
        array $attributes = []
    ): Application {
        $newStage = $newStage instanceof ApplicationStage ? $newStage : ApplicationStage::from($newStage);

        return DB::transaction(function () use ($application, $newStage, $userId, $comment, $attributes): Application {
            $locked = $this->lockApplication($application);
            $currentStage = $locked->stage;

            if ($currentStage === $newStage) {
                return $this->freshApplication($locked);
            }

            if (! $currentStage->canTransitionTo($newStage)) {
                throw InvalidTransitionException::for('la postulación', $currentStage->value, $newStage->value);
            }

            $locked->update(array_merge($attributes, [
                'stage' => $newStage->value,
                'status' => $newStage->status()->value,
            ]));

            $this->createLog($locked, $currentStage, $newStage, $userId, $comment);

            return $this->freshApplication($locked);
        });
    }

    public function logEvent(Application $application, string $event, ?int $userId = null, ?string $comment = null): Application
    {
        return DB::transaction(function () use ($application, $event, $userId, $comment): Application {
            $locked = $this->lockApplication($application);

            $this->createLog($locked, $locked->stage, $event, $userId, $comment);

            return $this->freshApplication($locked);
        });
    }

    public function getUnifiedTimeline(Application $application): array
    {
        $application->load([
            'stageLogs.user',
            'interviews.feedbacks.evaluator',
            'interviews.interviewer',
            'offers',
        ]);

        $timeline = [];

        foreach ($application->stageLogs as $log) {
            $timeline[] = [
                'type' => str_starts_with((string) $log->to_stage, 'offer_') ? 'offer_event' : 'stage_change',
                'title' => $this->stageLogTitle($log->to_stage),
                'details' => [
                    'from_stage' => $log->from_stage,
                    'to_stage' => $log->to_stage,
                    'comment' => $log->comment,
                    'actor' => $log->user?->name ?? 'Sistema / Reclutador',
                ],
                'timestamp' => $log->created_at->toIso8601String(),
            ];
        }

        foreach ($application->interviews as $interview) {
            $interviewLabel = ucfirst(str_replace('_', ' ', $interview->type));

            $timeline[] = [
                'type' => 'interview',
                'title' => $this->interviewTitle($interview->status, $interviewLabel),
                'details' => [
                    'type' => $interview->type,
                    'scheduled_at' => $interview->scheduled_at->toIso8601String(),
                    'interviewer' => $interview->interviewer?->name,
                    'status' => $interview->status,
                ],
                'timestamp' => $interview->scheduled_at->toIso8601String(),
            ];

            foreach ($interview->feedbacks as $feedback) {
                $timeline[] = [
                    'type' => 'interview_feedback',
                    'title' => "Feedback de entrevista cargado: Score {$feedback->score}/10",
                    'details' => [
                        'score' => $feedback->score,
                        'recommendation' => $feedback->recommendation,
                        'notes' => $feedback->general_notes,
                        'evaluator' => $feedback->evaluator?->name,
                    ],
                    'timestamp' => $feedback->created_at->toIso8601String(),
                ];
            }
        }

        foreach ($application->offers as $offer) {
            $timeline[] = [
                'type' => 'offer',
                'title' => 'Oferta '.strtolower($offer->status->value)." ({$offer->currency} {$offer->salary_offered})",
                'details' => [
                    'offer_id' => $offer->id,
                    'status' => $offer->status->value,
                    'salary_offered' => $offer->salary_offered,
                    'currency' => $offer->currency,
                    'start_date' => $offer->start_date?->toDateString(),
                    'decline_reason' => $offer->decline_reason,
                ],
                'timestamp' => ($offer->responded_at ?? $offer->created_at)->toIso8601String(),
            ];
        }

        if (filled($application->notes)) {
            $timeline[] = [
                'type' => 'note',
                'title' => 'Nota de seguimiento',
                'details' => [
                    'notes' => $application->notes,
                ],
                'timestamp' => $application->updated_at->toIso8601String(),
            ];
        }

        usort($timeline, fn (array $a, array $b): int => Carbon::parse($b['timestamp'])->getTimestamp() <=> Carbon::parse($a['timestamp'])->getTimestamp());

        return $timeline;
    }

    private function lockApplication(Application $application): Application
    {
        return Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
    }

    private function freshApplication(Application $application): Application
    {
        return $application->fresh(['candidate', 'position', 'stageLogs.user']);
    }

    private function createLog(
        Application $application,
        ApplicationStage|string|null $from,
        ApplicationStage|string $to,
        ?int $userId,
        ?string $comment
    ): void {
        ApplicationStageLog::create([
            'application_id' => $application->id,
            'user_id' => $userId,
            'from_stage' => $from instanceof ApplicationStage ? $from->value : $from,
            'to_stage' => $to instanceof ApplicationStage ? $to->value : $to,
            'comment' => $comment,
        ]);
    }

    private function stageLogTitle(?string $stage): string
    {
        return match ($stage) {
            null => 'Postulación inicial registrada',
            'offer_created' => 'Oferta formal generada',
            'offer_sent' => 'Oferta enviada al candidato',
            'offer_accepted' => 'Oferta aceptada por el candidato',
            'offer_declined' => 'Oferta declinada por el candidato',
            default => 'Movido a etapa: '.ucfirst(str_replace('_', ' ', $stage)),
        };
    }

    private function interviewTitle(string $status, string $label): string
    {
        return match ($status) {
            'completed' => "Entrevista realizada ({$label})",
            'canceled' => "Entrevista cancelada ({$label})",
            'no_show' => "Entrevista no asistida ({$label})",
            default => "Entrevista agendada ({$label})",
        };
    }
}
