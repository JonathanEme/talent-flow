<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MatchingService
{
    public function calculateMatch(Candidate $candidate, Position $position): array
    {
        $requirements = $position->skills;
        $candidateSkills = $candidate->skills->keyBy('id');

        [$mandatoryTotal, $mandatoryMatched, $optionalTotal, $optionalMatched, $skillDetails] = $this->evaluateSkills($requirements, $candidateSkills);

        $scores = [
            'mandatory_skills' => $this->ratio($mandatoryMatched, $mandatoryTotal),
            'optional_skills' => $this->ratio($optionalMatched, $optionalTotal),
            'seniority' => $this->seniorityScore($position, $candidateSkills),
            'salary' => $this->salaryScore($position, $candidate),
            'location' => $this->locationScore($position, $candidate),
        ];

        $factors = [];
        $overallScore = 0.0;

        foreach (config('matching.weights', []) as $key => $weight) {
            $score = $scores[$key] ?? 0.5;
            $points = $score * (float) $weight;
            $overallScore += $points;

            $factors[] = [
                'key' => $key,
                'label' => config("matching.labels.{$key}", $key),
                'score' => round($score * 100, 2),
                'weight' => (float) $weight,
                'points' => round($points, 2),
            ];
        }

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => "{$candidate->first_name} {$candidate->last_name}",
            'email' => $candidate->email,
            'overall_score' => round($overallScore, 2),
            'meets_all_mandatory' => $mandatoryTotal === 0 || $mandatoryMatched === $mandatoryTotal,
            'mandatory_matched' => $mandatoryMatched,
            'mandatory_total' => $mandatoryTotal,
            'optional_matched' => $optionalMatched,
            'optional_total' => $optionalTotal,
            'details' => $skillDetails,
            'factors' => $factors,
        ];
    }

    public function getRecommendationsForPosition(Position $position, int $limit = 20): Collection
    {
        $position->load('skills');

        $candidates = Candidate::query()
            ->whereHas('applications.position', fn (Builder $query): mixed => $query->where('company_id', $position->company_id))
            ->whereDoesntHave('applications', fn (Builder $query): mixed => $query->where('position_id', $position->id))
            ->with('skills')
            ->get();

        return $candidates->map(fn (Candidate $candidate): array => $this->calculateMatch($candidate, $position))
            ->sort(function (array $a, array $b): int {
                if ($a['meets_all_mandatory'] === $b['meets_all_mandatory']) {
                    return $b['overall_score'] <=> $a['overall_score'];
                }

                return $b['meets_all_mandatory'] <=> $a['meets_all_mandatory'];
            })
            ->values()
            ->take($limit);
    }

    private function evaluateSkills(Collection $requirements, Collection $candidateSkills): array
    {
        $mandatoryTotal = 0;
        $mandatoryMatched = 0;
        $optionalTotal = 0;
        $optionalMatched = 0;
        $details = [];

        foreach ($requirements as $requirement) {
            $isMandatory = (bool) $requirement->pivot->is_mandatory;
            $minYears = (int) $requirement->pivot->min_years_experience;
            $requiredLevel = $requirement->pivot->expected_level;
            $hasSkill = $candidateSkills->has($requirement->id);
            $candidateSkill = $hasSkill ? $candidateSkills->get($requirement->id) : null;
            $candidateYears = $hasSkill ? (int) $candidateSkill->pivot->years_experience : 0;
            $candidateLevel = $hasSkill ? $candidateSkill->pivot->level : null;
            $levelMet = $requiredLevel === null
                || ($hasSkill && $this->levelRank($candidateLevel) >= $this->levelRank($requiredLevel));
            $isMet = $hasSkill && $candidateYears >= $minYears && $levelMet;

            if ($isMandatory) {
                $mandatoryTotal++;
                $mandatoryMatched += $isMet ? 1 : 0;
            } else {
                $optionalTotal++;
                $optionalMatched += $isMet ? 1 : 0;
            }

            $details[] = [
                'skill_id' => $requirement->id,
                'skill_name' => $requirement->name,
                'is_mandatory' => $isMandatory,
                'required_years' => $minYears,
                'candidate_years' => $candidateYears,
                'required_level' => $requiredLevel,
                'candidate_level' => $candidateLevel,
                'is_met' => $isMet,
            ];
        }

        return [$mandatoryTotal, $mandatoryMatched, $optionalTotal, $optionalMatched, $details];
    }

    private function ratio(int $matched, int $total): float
    {
        return $total === 0 ? 1.0 : $matched / $total;
    }

    private function levelRank(?string $level): int
    {
        return (int) (config('matching.levels.'.Str::lower((string) $level), 0));
    }

    private function seniorityScore(Position $position, Collection $candidateSkills): float
    {
        $years = (float) ($candidateSkills->max(fn ($skill): int => (int) $skill->pivot->years_experience) ?? 0);
        $band = config('matching.seniority_bands.'.Str::lower((string) $position->seniority));

        if (! is_array($band)) {
            return 0.5;
        }

        [$minimum, $maximum] = $band;
        $span = max(1, $maximum - $minimum);

        if ($years >= $maximum) {
            return 1.0;
        }

        if ($years >= $minimum) {
            return 0.75 + 0.25 * (($years - $minimum) / $span);
        }

        return min(1.0, $years / max(1, $minimum)) * 0.75;
    }

    private function salaryScore(Position $position, Candidate $candidate): float
    {
        $maximum = (float) $position->salary_max;
        $expectation = $candidate->salary_expectation === null
            ? null
            : (float) $candidate->salary_expectation;

        if ($maximum <= 0 || $expectation === null) {
            return 0.5;
        }

        if ($expectation <= $maximum) {
            return 1.0;
        }

        $tolerance = $maximum * 0.2;

        return max(0.0, 1.0 - (($expectation - $maximum) / $tolerance) * 0.5);
    }

    private function locationScore(Position $position, Candidate $candidate): float
    {
        if (Str::lower((string) $position->modality) === 'remote') {
            return 1.0;
        }

        $positionLocation = Str::lower(trim((string) $position->location));
        $candidateLocation = Str::lower(trim((string) $candidate->location));

        if ($positionLocation === '' || $candidateLocation === '') {
            return 0.5;
        }

        return Str::contains($positionLocation, $candidateLocation)
            || Str::contains($candidateLocation, $positionLocation)
                ? 1.0
                : 0.25;
    }
}
