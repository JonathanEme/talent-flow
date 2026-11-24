<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStage;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $openPositions = $this->scopePositions(Position::query())->where('status', 'open')->count();
        $totalCandidates = $this->scopeCandidates(Candidate::query())->count();
        $activeApplications = $this->scopeApplications(Application::query())->where('status', 'active')->count();

        $upcomingInterviews = $this->scopeInterviews(Interview::query())
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->count();

        // Conteo por etapa
        $stageDistribution = $this->scopeApplications(Application::query())->select('stage', DB::raw('count(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->mapWithKeys(fn (mixed $total, mixed $stage): array => [
                $stage instanceof ApplicationStage ? $stage->value : $stage => $total,
            ])
            ->toArray();

        $stageStats = [
            'new' => $stageDistribution['new'] ?? 0,
            'screening' => $stageDistribution['screening'] ?? 0,
            'technical_interview' => $stageDistribution['technical_interview'] ?? 0,
            'final_interview' => $stageDistribution['final_interview'] ?? 0,
            'offer' => $stageDistribution['offer'] ?? 0,
            'hired' => $stageDistribution['hired'] ?? 0,
            'rejected' => $stageDistribution['rejected'] ?? 0,
        ];

        // Próximas entrevistas
        $nextInterviews = $this->scopeInterviews(Interview::query())->with(['application.candidate', 'application.position'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(5)
            ->get();

        // Vacantes con candidatos asociados
        $recentPositions = $this->scopePositions(Position::query())->with('company')
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'kpis' => [
                'open_positions' => $openPositions,
                'total_candidates' => $totalCandidates,
                'active_applications' => $activeApplications,
                'upcoming_interviews' => $upcomingInterviews,
            ],
            'stage_distribution' => $stageStats,
            'upcoming_interviews' => $nextInterviews,
            'recent_positions' => $recentPositions,
        ]);
    }
}
