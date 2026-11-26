<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class AdvancedFiltersAndReportsTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_positions_can_be_filtered_by_status_seniority_and_search(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $match = $this->makePosition($company, ['title' => 'Senior Laravel Engineer', 'status' => 'open', 'seniority' => 'SR', 'location' => 'Remote']);
        $this->makePosition($company, ['title' => 'Junior Vue Engineer', 'status' => 'draft', 'seniority' => 'JR']);

        $this->getJson('/api/v1/positions?status=open&seniority=SR&search=Laravel')
            ->assertOk()
            ->assertJsonFragment(['title' => $match->title])
            ->assertJsonMissing(['title' => 'Junior Vue Engineer']);
    }

    public function test_candidates_can_be_filtered_by_skill_and_location(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $position = $this->makePosition($company);
        $php = $this->makeSkill('PHP');
        $match = $this->makeCandidate(['first_name' => 'Ana', 'location' => 'Buenos Aires']);
        $other = $this->makeCandidate(['first_name' => 'Luis', 'location' => 'Cordoba', 'email' => fake()->unique()->safeEmail()]);
        $this->makeApplication($position, $match);
        $this->makeApplication($position, $other);
        $match->skills()->attach($php->id, ['years_experience' => 5, 'level' => 'advanced']);

        $this->getJson('/api/v1/candidates?skill=PHP&location=Buenos')
            ->assertOk()
            ->assertJsonFragment(['email' => $match->email])
            ->assertJsonMissing(['email' => $other->email]);
    }

    public function test_interviews_and_applications_support_date_and_status_filters(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        Sanctum::actingAs($user);

        $position = $this->makePosition($company);
        $app1 = $this->makeApplication($position, $this->makeCandidate(), ['applied_at' => '2026-11-01']);
        $app2 = $this->makeApplication($position, $this->makeCandidate(['email' => fake()->unique()->safeEmail()]), ['applied_at' => '2026-10-01']);
        $matchInterview = $this->makeInterview($app1, $user, ['type' => 'technical', 'status' => 'scheduled', 'scheduled_at' => '2026-11-20 10:00:00']);
        $otherInterview = $this->makeInterview($app2, $user, ['type' => 'hr', 'status' => 'completed', 'scheduled_at' => '2026-10-20 10:00:00']);

        $this->getJson('/api/v1/applications?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonFragment(['id' => $app1->id])
            ->assertJsonMissing(['id' => $app2->id]);

        $this->getJson('/api/v1/interviews?status=scheduled&type=technical&from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonFragment(['id' => $matchInterview->id])
            ->assertJsonMissing(['id' => $otherInterview->id]);
    }

    public function test_dashboard_reports_support_date_range_and_extended_kpis(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $position = $this->makePosition($company);
        $hired = $this->makeApplication($position, $this->makeCandidate(), ['stage' => 'hired', 'status' => 'hired', 'applied_at' => '2026-11-10']);
        $this->makeApplication($position, $this->makeCandidate(['email' => fake()->unique()->safeEmail()]), ['stage' => 'rejected', 'status' => 'rejected', 'applied_at' => '2026-10-10']);
        $this->makeApplication($position, $this->makeCandidate(['email' => fake()->unique()->safeEmail()]), ['stage' => 'new', 'status' => 'active', 'applied_at' => '2026-11-15']);

        $this->getJson('/api/v1/dashboard?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('kpis.hired_applications', 1)
            ->assertJsonPath('kpis.rejected_applications', 0)
            ->assertJsonPath('status_distribution.hired', 1)
            ->assertJsonPath('status_distribution.rejected', 0)
            ->assertJsonPath('stage_distribution.hired', 1);
    }
}
