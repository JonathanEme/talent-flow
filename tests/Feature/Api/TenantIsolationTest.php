<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_tenant_user_cannot_show_foreign_resources(): void
    {
        $ownCompany = $this->makeCompany(['name' => 'Propia']);
        $foreignCompany = $this->makeCompany(['name' => 'Ajena']);

        $user = $this->makeUser($ownCompany);
        Sanctum::actingAs($user);

        $ownPosition = $this->makePosition($ownCompany);
        $foreignPosition = $this->makePosition($foreignCompany);
        $ownApplication = $this->makeApplication($ownPosition, $this->makeCandidate());
        $foreignApplication = $this->makeApplication($foreignPosition, $this->makeCandidate());
        $foreignCandidate = $foreignApplication->candidate;

        $foreignInterview = $this->makeInterview($foreignApplication, $user);
        $foreignOffer = $this->makeOffer($foreignApplication, $user);

        $this->getJson("/api/v1/companies/{$foreignCompany->id}")->assertForbidden();
        $this->getJson("/api/v1/positions/{$foreignPosition->id}")->assertForbidden();
        $this->getJson("/api/v1/candidates/{$foreignCandidate->id}")->assertForbidden();
        $this->getJson("/api/v1/applications/{$foreignApplication->id}")->assertForbidden();
        $this->getJson("/api/v1/interviews/{$foreignInterview->id}")->assertForbidden();
        $this->getJson("/api/v1/offers/{$foreignOffer->id}")->assertForbidden();

        $this->getJson("/api/v1/positions/{$ownPosition->id}")->assertOk();
    }

    public function test_tenant_user_cannot_create_position_in_foreign_company(): void
    {
        $ownCompany = $this->makeCompany(['name' => 'Propia']);
        $foreignCompany = $this->makeCompany(['name' => 'Ajena']);

        Sanctum::actingAs($this->makeUser($ownCompany));

        $this->postJson('/api/v1/positions', [
            'company_id' => $foreignCompany->id,
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'SR',
            'modality' => 'remote',
            'employment_type' => 'full-time',
            'opened_at' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_tenant_user_cannot_compare_foreign_candidate(): void
    {
        $ownCompany = $this->makeCompany(['name' => 'Propia']);
        $foreignCompany = $this->makeCompany(['name' => 'Ajena']);

        Sanctum::actingAs($this->makeUser($ownCompany));

        $position = $this->makePosition($ownCompany);
        $foreignCandidate = $this->makeApplication($this->makePosition($foreignCompany), $this->makeCandidate())->candidate;

        $this->getJson("/api/v1/positions/{$position->id}/candidates/{$foreignCandidate->id}/match")
            ->assertForbidden();
    }

    public function test_indexes_only_return_tenant_rows(): void
    {
        $ownCompany = $this->makeCompany(['name' => 'Propia']);
        $foreignCompany = $this->makeCompany(['name' => 'Ajena']);

        Sanctum::actingAs($this->makeUser($ownCompany));

        $ownPosition = $this->makePosition($ownCompany, ['title' => 'Vacante propia']);
        $foreignPosition = $this->makePosition($foreignCompany, ['title' => 'Vacante ajena']);
        $ownApplication = $this->makeApplication($ownPosition, $this->makeCandidate(['email' => 'own@example.test']));
        $foreignApplication = $this->makeApplication($foreignPosition, $this->makeCandidate(['email' => 'foreign@example.test']));
        $ownInterview = $this->makeInterview($ownApplication, null, ['notes' => 'entrevista-propia']);
        $foreignInterview = $this->makeInterview($foreignApplication, null, ['notes' => 'entrevista-ajena']);
        $ownOffer = $this->makeOffer($ownApplication, null, ['benefits_summary' => 'beneficios-propios']);
        $foreignOffer = $this->makeOffer($foreignApplication, null, ['benefits_summary' => 'beneficios-ajenos']);

        $this->getJson('/api/v1/positions')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Vacante propia'])
            ->assertJsonMissing(['title' => 'Vacante ajena']);

        $this->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonFragment(['email' => 'own@example.test'])
            ->assertJsonMissing(['email' => 'foreign@example.test']);

        $this->getJson('/api/v1/interviews')
            ->assertOk()
            ->assertJsonFragment(['notes' => 'entrevista-propia'])
            ->assertJsonMissing(['notes' => 'entrevista-ajena']);

        $this->getJson('/api/v1/offers')
            ->assertOk()
            ->assertJsonFragment(['benefits_summary' => 'beneficios-propios'])
            ->assertJsonMissing(['benefits_summary' => 'beneficios-ajenos']);
    }

    public function test_global_user_can_access_every_company(): void
    {
        $ownCompany = $this->makeCompany(['name' => 'Propia']);
        $foreignCompany = $this->makeCompany(['name' => 'Ajena']);
        $foreignPosition = $this->makePosition($foreignCompany);

        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonFragment(['id' => $ownCompany->id])
            ->assertJsonFragment(['id' => $foreignCompany->id]);

        $this->getJson("/api/v1/positions/{$foreignPosition->id}")->assertOk();
    }
}
