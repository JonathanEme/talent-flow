<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_the_version_header(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertHeader('X-API-Version', '1');
    }

    public function test_paginated_resources_keep_the_stable_envelope(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        Sanctum::actingAs(User::factory()->create(['company_id' => $company->id]));

        $company->positions()->create([
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'SR',
            'modality' => 'remote',
            'employment_type' => 'full-time',
            'opened_at' => now()->toDateString(),
            'status' => 'open',
        ]);

        $this->getJson('/api/v1/positions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'seniority', 'modality', 'company' => ['id', 'name']],
                    ],
                    'links' => ['first', 'last', 'prev', 'next'],
                    'meta' => ['current_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_form_requests_return_consistent_validation_errors(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        Sanctum::actingAs(User::factory()->create(['company_id' => $company->id]));

        $this->postJson('/api/v1/positions', [
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'unknown',
            'modality' => 'unknown',
            'employment_type' => 'unknown',
            'opened_at' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['seniority', 'modality', 'employment_type']);
    }

    public function test_dashboard_and_timeline_keep_their_flat_contract(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $position = $company->positions()->create([
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'SR',
            'modality' => 'remote',
            'employment_type' => 'full-time',
            'opened_at' => now()->toDateString(),
            'status' => 'open',
        ]);

        $candidate = Candidate::create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => fake()->unique()->safeEmail(),
        ]);

        $application = Application::create([
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'stage' => 'new',
            'status' => 'active',
            'applied_at' => now()->toDateString(),
        ]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => ['open_positions', 'total_candidates', 'active_applications', 'upcoming_interviews'],
                'stage_distribution' => ['new', 'screening', 'technical_interview', 'final_interview', 'offer', 'hired', 'rejected'],
                'upcoming_interviews',
                'recent_positions',
            ]);

        $this->getJson("/api/v1/applications/{$application->id}/timeline")
            ->assertOk()
            ->assertJsonStructure([
                'application_id',
                'candidate',
                'position',
                'current_stage',
                'timeline' => ['*' => ['type', 'title', 'details', 'timestamp']],
            ]);
    }

    public function test_match_contract_always_exposes_the_candidate_email(): void
    {
        $company = Company::create(['name' => 'TalentFlow']);
        Sanctum::actingAs(User::factory()->create(['company_id' => null]));

        $position = $company->positions()->create([
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'SR',
            'modality' => 'remote',
            'employment_type' => 'full-time',
            'opened_at' => now()->toDateString(),
            'status' => 'open',
        ]);

        $candidate = Candidate::create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
        ]);

        $this->getJson("/api/v1/positions/{$position->id}/candidates/{$candidate->id}/match")
            ->assertOk()
            ->assertJsonPath('data.email', 'ana@example.com');
    }
}
