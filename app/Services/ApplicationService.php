<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStageLog;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    /**
     * Mueve una postulación a una nueva etapa y genera el registro en el log.
     */
    public function transitionStage(Application $application, string $newStage, ?int $userId = null, ?string $comment = null): Application
    {
        return DB::transaction(function () use ($application, $newStage, $userId, $comment) {
            $previousStage = $application->stage;

            if ($previousStage === $newStage) {
                return $application;
            }

            $application->update([
                'stage' => $newStage,
            ]);

            ApplicationStageLog::create([
                'application_id' => $application->id,
                'user_id' => $userId,
                'from_stage' => $previousStage,
                'to_stage' => $newStage,
                'comment' => $comment,
            ]);

            return $application->fresh(['candidate', 'position', 'stageLogs.user']);
        });
    }

    /**
     * Construye un timeline unificado (movimientos de etapa, entrevistas realizadas y notas).
     */
    public function getUnifiedTimeline(Application $application): array
    {
        $application->load([
            'stageLogs.user',
            'interviews.feedback.evaluator',
            'interviews.interviewer',
        ]);

        $timeline = [];

        // 1. Eventos de cambio de etapa
        foreach ($application->stageLogs as $log) {
            $timeline[] = [
                'type' => 'stage_change',
                'title' => "Movido a etapa: " . ucfirst(str_replace('_', ' ', $log->to_stage)),
                'details' => [
                    'from_stage' => $log->from_stage,
                    'to_stage' => $log->to_stage,
                    'comment' => $log->comment,
                    'actor' => $log->user ? $log->user->name : 'Sistema / Reclutador',
                ],
                'timestamp' => $log->created_at->toIso8601String(),
            ];
        }

        // 2. Eventos de entrevistas y feedback
        foreach ($application->interviews as $interview) {
            $timeline[] = [
                'type' => 'interview_scheduled',
                'title' => "Entrevista agendada (" . ucfirst($interview->type) . ")",
                'details' => [
                    'type' => $interview->type,
                    'scheduled_at' => $interview->scheduled_at->toIso8601String(),
                    'interviewer' => $interview->interviewer?->name,
                    'status' => $interview->status,
                ],
                'timestamp' => $interview->created_at->toIso8601String(),
            ];

            if ($interview->feedback) {
                $timeline[] = [
                    'type' => 'interview_feedback',
                    'title' => "Feedback de entrevista cargado: Score " . $interview->feedback->score . "/10",
                    'details' => [
                        'score' => $interview->feedback->score,
                        'recommendation' => $interview->feedback->recommendation,
                        'notes' => $interview->feedback->general_notes,
                        'evaluator' => $interview->feedback->evaluator?->name,
                    ],
                    'timestamp' => $interview->feedback->created_at->toIso8601String(),
                ];
            }
        }

        // Ordenamos cronológicamente descendente (lo más reciente primero)
        usort($timeline, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $timeline;
    }
}