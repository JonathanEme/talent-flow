<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_service_returns_explainable_weighted_factors(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $position = $this->makePosition($company, [
            'seniority' => 'SR',
            'modality' => 'remote',
            'salary_min' => 1000,
            'salary_max' => 5000,
        ]);

        $mandatory = Skill::create(['name' => 'PHP', 'category' => 'technical']);
        $optional = Skill::create(['name' => 'Vue', 'category' => 'technical']);

        $position->skills()->attach($mandatory->id, [
            'is_mandatory' => true,
            'min_years_experience' => 3,
            'expected_level' => 'advanced',
        ]);
        $position->skills()->attach($optional->id, [
            'is_mandatory' => false,
            'min_years_experience' => 2,
            'expected_level' => 'intermediate',
        ]);

        $candidate = $this->makeCandidate($company, [
            'salary_expectation' => 4000,
            'location' => 'Remoto',
        ]);
        $candidate->skills()->attach($mandatory->id, [
            'years_experience' => 4,
            'level' => 'advanced',
        ]);

        $result = app(MatchingService::class)->calculateMatch($candidate, $position);

        $this->assertTrue($result['meets_all_mandatory']);
        $this->assertSame(1, $result['mandatory_matched']);
        $this->assertSame(0, $result['optional_matched']);
        $this->assertCount(5, $result['factors']);
        $this->assertSame('mandatory_skills', $result['factors'][0]['key']);
        $this->assertSame(100.0, $result['factors'][0]['score']);
        $this->assertGreaterThan(60, $result['overall_score']);
    }

    public function test_salary_and_location_change_the_score(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $position = $this->makePosition($company, [
            'seniority' => 'SR',
            'modality' => 'on_site',
            'location' => 'Madrid',
            'salary_min' => 1000,
            'salary_max' => 5000,
        ]);

        $skill = Skill::create(['name' => 'PHP', 'category' => 'technical']);
        $position->skills()->attach($skill->id, [
            'is_mandatory' => true,
            'min_years_experience' => 3,
            'expected_level' => 'intermediate',
        ]);

        $aligned = $this->makeCandidate($company, [
            'salary_expectation' => 4000,
            'location' => 'Madrid',
        ]);
        $misaligned = $this->makeCandidate($company, [
            'salary_expectation' => 9000,
            'location' => 'Lima',
        ]);

        foreach ([$aligned, $misaligned] as $candidate) {
            $candidate->skills()->attach($skill->id, [
                'years_experience' => 4,
                'level' => 'advanced',
            ]);
        }

        $service = app(MatchingService::class);

        $alignedScore = $service->calculateMatch($aligned, $position)['overall_score'];
        $misalignedScore = $service->calculateMatch($misaligned, $position)['overall_score'];

        $this->assertGreaterThan($misalignedScore, $alignedScore);
    }

    public function test_recommendations_are_scoped_by_company_and_flag_existing_applications(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $otherCompany = Company::create(['name' => 'Otra empresa']);

        $target = $this->makePosition($company);
        $sibling = $this->makePosition($company, ['title' => 'Position sibling']);
        $foreign = $this->makePosition($otherCompany);

        $eligible = $this->makeCandidate($company);
        $foreignCandidate = $this->makeCandidate($otherCompany);
        $alreadyApplied = $this->makeCandidate($company);

        $this->makeApplicationRecord($sibling, $eligible);
        $this->makeApplicationRecord($foreign, $foreignCandidate);
        $this->makeApplicationRecord($target, $alreadyApplied);

        $recommendations = app(MatchingService::class)->getRecommendationsForPosition($target);

        $this->assertCount(2, $recommendations);
        $this->assertContains($eligible->id, $recommendations->pluck('candidate_id')->all());
        $this->assertContains($alreadyApplied->id, $recommendations->pluck('candidate_id')->all());
        $this->assertFalse($recommendations->firstWhere('candidate_id', $eligible->id)['already_applied']);
        $this->assertTrue($recommendations->firstWhere('candidate_id', $alreadyApplied->id)['already_applied']);
        $this->assertSame('new', $recommendations->firstWhere('candidate_id', $alreadyApplied->id)['application_stage']);
    }

    public function test_recommendations_include_unapplied_candidates_and_flag_applied_ones(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $otherCompany = Company::create(['name' => 'Otra empresa']);

        $target = $this->makePosition($company);
        $unapplied = $this->makeCandidate($company);
        $engagedElsewhere = $this->makeCandidate($otherCompany);
        $alreadyApplied = $this->makeCandidate($company);

        $this->makeApplicationRecord($this->makePosition($otherCompany), $engagedElsewhere);
        $this->makeApplicationRecord($target, $alreadyApplied);

        $recommendations = app(MatchingService::class)->getRecommendationsForPosition($target);

        $this->assertCount(2, $recommendations);
        $this->assertContains($unapplied->id, $recommendations->pluck('candidate_id')->all());
        $this->assertFalse($recommendations->firstWhere('candidate_id', $unapplied->id)['already_applied']);
        $this->assertTrue($recommendations->firstWhere('candidate_id', $alreadyApplied->id)['already_applied']);
    }

    public function test_match_endpoints_expose_the_explainable_factor_contract(): void
    {
        Sanctum::actingAs(User::factory()->create(['company_id' => null]));

        $company = Company::create(['name' => 'TalentFlow']);
        $position = $this->makePosition($company);
        $candidate = $this->makeCandidate($company);

        $skill = Skill::create(['name' => 'PHP', 'category' => 'technical']);
        $position->skills()->attach($skill->id, [
            'is_mandatory' => true,
            'min_years_experience' => 2,
            'expected_level' => 'intermediate',
        ]);
        $candidate->skills()->attach($skill->id, [
            'years_experience' => 3,
            'level' => 'advanced',
        ]);

        $this->getJson("/api/v1/positions/{$position->id}/candidates/{$candidate->id}/match")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'candidate_id',
                    'email',
                    'overall_score',
                    'meets_all_mandatory',
                    'details',
                    'factors' => ['*' => ['key', 'label', 'score', 'weight', 'points']],
                ],
            ]);

        $this->makeApplicationRecord($this->makePosition($company, ['title' => 'Otra vacante']), $candidate);

        $this->getJson("/api/v1/positions/{$position->id}/recommendations")
            ->assertOk()
            ->assertJsonStructure([
                'position' => ['id', 'title', 'total_requirements'],
                'data' => ['*' => ['candidate_id', 'overall_score', 'factors']],
            ]);
    }

    public function test_recommendations_reject_invalid_limits(): void
    {
        Sanctum::actingAs(User::factory()->create(['company_id' => null]));

        $company = Company::create(['name' => 'TalentFlow']);
        $position = $this->makePosition($company);

        $this->getJson("/api/v1/positions/{$position->id}/recommendations?limit=0")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);

        $this->getJson("/api/v1/positions/{$position->id}/recommendations?limit=51")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    private function makePosition(Company $company, array $attributes = []): Position
    {
        return $company->positions()->create(array_merge([
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'SR',
            'modality' => 'remote',
            'employment_type' => 'full-time',
            'opened_at' => now()->toDateString(),
            'status' => 'open',
        ], $attributes));
    }

    private function makeCandidate(Company $company, array $attributes = []): Candidate
    {
        return Candidate::create(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => fake()->unique()->safeEmail(),
        ], $attributes));
    }

    private function makeApplicationRecord(Position $position, Candidate $candidate): Application
    {
        return Application::create([
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'stage' => 'new',
            'status' => 'active',
            'applied_at' => now()->toDateString(),
        ]);
    }
}
