<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Support\Collection;

class MatchingService
{
    /**
     * Calcula la compatibilidad detallada entre un candidato y una posición.
     */
    public function calculateMatch(Candidate $candidate, Position $position): array
    {
        $requirements = $position->skills;
        $candidateSkills = $candidate->skills->keyBy('id');

        if ($requirements->isEmpty()) {
            return [
                'candidate_id' => $candidate->id,
                'candidate_name' => "{$candidate->first_name} {$candidate->last_name}",
                'overall_score' => 100,
                'meets_all_mandatory' => true,
                'mandatory_matched' => 0,
                'mandatory_total' => 0,
                'optional_matched' => 0,
                'optional_total' => 0,
                'details' => [],
            ];
        }

        $mandatoryTotal = 0;
        $mandatoryMatched = 0;
        $optionalTotal = 0;
        $optionalMatched = 0;
        $details = [];

        foreach ($requirements as $requirement) {
            $isMandatory = (bool) $requirement->pivot->is_mandatory;
            $minYears = (int) $requirement->pivot->min_years_experience;
            $hasSkill = $candidateSkills->has($requirement->id);

            $candidateYears = $hasSkill ? (int) $candidateSkills->get($requirement->id)->pivot->years_experience : 0;
            $isRequirementMet = $hasSkill && ($candidateYears >= $minYears);

            if ($isMandatory) {
                $mandatoryTotal++;
                if ($isRequirementMet) {
                    $mandatoryMatched++;
                }
            } else {
                $optionalTotal++;
                if ($isRequirementMet) {
                    $optionalMatched++;
                }
            }

            $details[] = [
                'skill_id' => $requirement->id,
                'skill_name' => $requirement->name,
                'is_mandatory' => $isMandatory,
                'required_years' => $minYears,
                'candidate_years' => $candidateYears,
                'is_met' => $isRequirementMet,
            ];
        }

        $mandatoryScore = $mandatoryTotal > 0 ? ($mandatoryMatched / $mandatoryTotal) : 1.0;
        $optionalScore = $optionalTotal > 0 ? ($optionalMatched / $optionalTotal) : 1.0;

        // Ponderación: 75% obligatorios, 25% deseables (ajustable si no hay deseables/obligatorios)
        if ($mandatoryTotal > 0 && $optionalTotal > 0) {
            $overallScore = round(($mandatoryScore * 0.75 + $optionalScore * 0.25) * 100, 2);
        } elseif ($mandatoryTotal > 0) {
            $overallScore = round($mandatoryScore * 100, 2);
        } else {
            $overallScore = round($optionalScore * 100, 2);
        }

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => "{$candidate->first_name} {$candidate->last_name}",
            'email' => $candidate->email,
            'overall_score' => $overallScore,
            'meets_all_mandatory' => $mandatoryTotal === 0 || $mandatoryMatched === $mandatoryTotal,
            'mandatory_matched' => $mandatoryMatched,
            'mandatory_total' => $mandatoryTotal,
            'optional_matched' => $optionalMatched,
            'optional_total' => $optionalTotal,
            'details' => $details,
        ];
    }

    /**
     * Obtiene el ranking de candidatos para una posición ordenados por score.
     */
    public function getRecommendationsForPosition(Position $position, int $limit = 20): Collection
    {
        $position->load('skills');

        // Traemos todos los candidatos con sus skills cargadas para evitar N+1 queries
        $candidates = Candidate::with('skills')->get();

        return $candidates->map(function (Candidate $candidate) use ($position) {
            return $this->calculateMatch($candidate, $position);
        })
        // Ordenamos: primero los que cumplen todos los obligatorios, luego por score descendente
        ->sort(function ($a, $b) {
            if ($a['meets_all_mandatory'] === $b['meets_all_mandatory']) {
                return $b['overall_score'] <=> $a['overall_score'];
            }
            return $b['meets_all_mandatory'] <=> $a['meets_all_mandatory'];
        })
        ->values()
        ->take($limit);
    }
}