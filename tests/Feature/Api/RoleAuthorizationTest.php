<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_global_admin_can_create_companies_and_global_skills(): void
    {
        Sanctum::actingAs(User::factory()->globalAdmin()->create());

        $this->postJson('/api/v1/companies', ['name' => 'Nueva empresa'])
            ->assertCreated();

        $this->postJson('/api/v1/skills', ['name' => 'PHP', 'category' => 'technical'])
            ->assertCreated();
    }

    public function test_company_admin_can_manage_tenant_resources_but_not_companies_or_global_skills(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs(User::factory()->companyAdmin($company)->create());

        $this->postJson('/api/v1/companies', ['name' => 'Otra empresa'])
            ->assertForbidden();

        $this->postJson('/api/v1/skills', ['name' => 'PHP', 'category' => 'technical'])
            ->assertForbidden();

        $this->postJson('/api/v1/positions', [
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'status' => 'open',
            'seniority' => 'senior',
            'location' => 'Remoto',
            'modality' => 'remote',
            'employment_type' => 'full_time',
            'opened_at' => now()->toDateString(),
        ])->assertCreated();
    }

    public function test_member_can_read_scoped_data_but_cannot_mutate(): void
    {
        $company = $this->makeCompany();
        $member = User::factory()->member($company)->create();
        Sanctum::actingAs($member);

        $position = $this->makePosition($company);
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($position, $candidate);
        $interview = $this->makeInterview($application, $member);
        $offer = $this->makeOffer($application, $member);

        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/positions')->assertOk();
        $this->getJson('/api/v1/applications')->assertOk();

        $this->postJson('/api/v1/positions', ['company_id' => $company->id])->assertForbidden();
        $this->postJson('/api/v1/candidates', [
            'full_name' => 'Ana Pérez',
            'email' => 'ana@example.com',
        ])->assertForbidden();
        $this->postJson('/api/v1/applications', [
            'candidate_id' => $candidate->id,
            'position_id' => $position->id,
        ])->assertForbidden();
        $this->patchJson("/api/v1/applications/{$application->id}/stage", ['stage' => 'screening'])
            ->assertForbidden();
        $this->postJson('/api/v1/interviews', ['application_id' => $application->id])
            ->assertForbidden();
        $this->putJson("/api/v1/interviews/{$interview->id}", ['type' => 'hr'])
            ->assertForbidden();
        $this->postJson('/api/v1/offers', ['application_id' => $application->id])
            ->assertForbidden();
        $this->patchJson("/api/v1/offers/{$offer->id}/status", ['status' => 'sent'])
            ->assertForbidden();
        $this->postJson('/api/v1/skills', ['name' => 'PHP', 'category' => 'technical'])
            ->assertForbidden();
        $this->postJson("/api/v1/positions/{$position->id}/requirements", ['skills' => []])
            ->assertForbidden();
        $this->postJson("/api/v1/candidates/{$candidate->id}/skills", ['skills' => []])
            ->assertForbidden();

        $this->assertDatabaseCount('positions', 1);
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('offers', 1);
        $this->assertDatabaseCount('skills', 0);
    }

    public function test_member_can_only_submit_feedback_for_assigned_interview(): void
    {
        $company = $this->makeCompany();
        $member = User::factory()->member($company)->create();
        $otherMember = User::factory()->member($company)->create();
        $companyAdmin = User::factory()->companyAdmin($company)->create();

        $interview = $this->makeInterview(
            $this->makeApplication($this->makePosition($company), $this->makeCandidate()),
            $member
        );

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 8,
            'recommendation' => 'approved',
        ])->assertCreated();

        Sanctum::actingAs($otherMember);
        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 7,
            'recommendation' => 'approved',
        ])->assertForbidden();

        Sanctum::actingAs($companyAdmin);
        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 9,
            'recommendation' => 'approved',
        ])->assertCreated();

        $this->assertDatabaseCount('interview_feedbacks', 2);
    }

    public function test_auth_payload_exposes_the_user_role(): void
    {
        $member = User::factory()->member($this->makeCompany())->create([
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $member->email,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.role', UserRole::Member->value);
    }
}
