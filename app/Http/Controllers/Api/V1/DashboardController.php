<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DashboardIndexRequest;
use App\Http\Resources\Api\V1\DashboardResource;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(DashboardIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $applicationsQuery = $this->scopeApplications(Application::query());
        if ($from !== null) {
            $applicationsQuery->whereDate('applied_at', '>=', $from);
        }
        if ($to !== null) {
            $applicationsQuery->whereDate('applied_at', '<=', $to);
        }

        $stageDistribution = (clone $applicationsQuery)
            ->select('stage', DB::raw('count(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->mapWithKeys(fn (mixed $total, mixed $stage): array => [
                $stage instanceof ApplicationStage ? $stage->value : (string) $stage => $total,
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

        $statusDistribution = (clone $applicationsQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn (mixed $total, mixed $status): array => [
                is_object($status) && enum_exists($status::class) ? $status->value : (string) $status => $total,
            ])
            ->toArray();

        $statusStats = [
            'active' => $statusDistribution['active'] ?? 0,
            'hired' => $statusDistribution['hired'] ?? 0,
            'rejected' => $statusDistribution['rejected'] ?? 0,
            'withdrawn' => $statusDistribution['withdrawn'] ?? 0,
        ];

        $nextInterviews = $this->scopeInterviews(Interview::query())
            ->with(['application.candidate', 'application.position'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(5)
            ->get();

        $recentPositions = $this->scopePositions(Position::query())
            ->with('company')
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get();

        return (new DashboardResource([
            'kpis' => [
                'open_positions' => $this->scopePositions(Position::query())->where('status', 'open')->count(),
                'total_candidates' => $this->scopeCandidates(Candidate::query())->count(),
                'active_applications' => (clone $applicationsQuery)->where('status', 'active')->count(),
                'upcoming_interviews' => $this->scopeInterviews(Interview::query())
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '>=', now())
                    ->count(),
                'hired_applications' => $statusStats['hired'],
                'rejected_applications' => $statusStats['rejected'],
            ],
            'stage_distribution' => $stageStats,
            'status_distribution' => $statusStats,
            'upcoming_interviews' => $nextInterviews,
            'recent_positions' => $recentPositions,
        ]))->response();
    }
}
