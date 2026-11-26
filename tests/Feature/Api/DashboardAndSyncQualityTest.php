<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class DashboardAndSyncQualityTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_dashboard_kpis_are_isolated_by_company(): void
    {
        $ownCompany = $this->makeCompany();
        $foreignCompany = $this->makeCompany(['name' => 'Otra empresa']);
        $user = $this->makeUser($ownCompany);
        Sanctum::actingAs($user);

        $ownPosition = $this->makePosition($ownCompany, ['title' => 'Vacante propia']);
        $foreignPosition = $this->makePosition($foreignCompany, ['title' => 'Vacante ajena']);

        $ownApplication = $this->makeApplication($ownPosition, $this->makeCandidate());
        $foreignApplication = $this->makeApplication($foreignPosition, $this->makeCandidate());

        $this->makeInterview($ownApplication, $user, [
            'scheduled_at' => now()->addDay(),
            'notes' => 'entrevista-propia',
        ]);
        $this->makeInterview($foreignApplication, $user, [
            'scheduled_at' => now()->addDay(),
            'notes' => 'entrevista-ajena',
        ]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('kpis.open_positions', 1)
            ->assertJsonPath('kpis.total_candidates', 1)
            ->assertJsonPath('kpis.active_applications', 1)
            ->assertJsonPath('kpis.upcoming_interviews', 1)
            ->assertJsonPath('kpis.hired_applications', 0)
            ->assertJsonPath('kpis.rejected_applications', 0)
            ->assertJsonPath('stage_distribution.new', 1)
            ->assertJsonPath('status_distribution.active', 1)
            ->assertJsonFragment(['title' => 'Vacante propia'])
            ->assertJsonMissing(['title' => 'Vacante ajena'])
            ->assertJsonMissing(['notes' => 'entrevista-ajena']);
    }

    public function test_position_requirements_sync_replaces_previous_relations(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $position = $this->makePosition($company);
        $php = $this->makeSkill('PHP');
        $vue = $this->makeSkill('Vue');

        $this->postJson("/api/v1/positions/{$position->id}/requirements", [
            'skills' => [
                [
                    'skill_id' => $php->id,
                    'is_mandatory' => true,
                    'min_years_experience' => 3,
                    'expected_level' => 'advanced',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('position_requirements', [
            'position_id' => $position->id,
            'skill_id' => $php->id,
            'is_mandatory' => 1,
            'min_years_experience' => 3,
            'expected_level' => 'advanced',
        ]);

        $this->postJson("/api/v1/positions/{$position->id}/requirements", [
            'skills' => [
                [
                    'skill_id' => $vue->id,
                    'is_mandatory' => false,
                    'min_years_experience' => 1,
                    'expected_level' => 'beginner',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('position_requirements', [
            'position_id' => $position->id,
            'skill_id' => $php->id,
        ]);
        $this->assertDatabaseHas('position_requirements', [
            'position_id' => $position->id,
            'skill_id' => $vue->id,
            'expected_level' => 'beginner',
        ]);
    }

    public function test_position_requirements_sync_rejects_duplicate_skills(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $position = $this->makePosition($company);
        $php = $this->makeSkill('PHP');

        $this->postJson("/api/v1/positions/{$position->id}/requirements", [
            'skills' => [
                ['skill_id' => $php->id, 'is_mandatory' => true],
                ['skill_id' => $php->id, 'is_mandatory' => false],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['skills.1.skill_id']);
    }

    public function test_candidate_skills_sync_replaces_previous_relations_and_validates_level(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $candidate = $this->makeApplication($this->makePosition($company), $this->makeCandidate())->candidate;
        $php = $this->makeSkill('PHP');
        $vue = $this->makeSkill('Vue');

        $this->postJson("/api/v1/candidates/{$candidate->id}/skills", [
            'skills' => [
                [
                    'skill_id' => $php->id,
                    'years_experience' => 4,
                    'level' => 'expert',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('candidate_skills', [
            'candidate_id' => $candidate->id,
            'skill_id' => $php->id,
            'years_experience' => 4,
            'level' => 'expert',
        ]);

        $this->postJson("/api/v1/candidates/{$candidate->id}/skills", [
            'skills' => [
                [
                    'skill_id' => $vue->id,
                    'years_experience' => 2,
                    'level' => 'wizard',
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['skills.0.level']);

        $this->assertDatabaseHas('candidate_skills', [
            'candidate_id' => $candidate->id,
            'skill_id' => $php->id,
        ]);
    }
}
