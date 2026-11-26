<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/positions')->assertUnauthorized();
        $this->postJson('/api/v1/companies', ['name' => 'Acme'])->assertUnauthorized();
    }

    public function test_user_can_login_and_use_protected_endpoints(): void
    {
        $user = User::factory()->create([
            'email' => 'recruiter@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)->getJson('/api/v1/positions')->assertOk();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        auth()->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_company_user_only_sees_their_company(): void
    {
        $ownCompany = Company::create(['name' => 'Empresa propia']);
        Company::create(['name' => 'Otra empresa']);

        $user = User::factory()->create([
            'company_id' => $ownCompany->id,
            'password' => 'password123',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $ownCompany->id);
    }

    public function test_actor_id_cannot_be_forged_in_stage_transitions(): void
    {
        $company = Company::create(['name' => 'Empresa propia']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'password' => 'password123',
        ]);

        $position = $user->company->positions()->create([
            'title' => 'Backend Developer',
            'description' => 'Desarrollo backend',
            'seniority' => 'senior',
            'modality' => 'remote',
            'employment_type' => 'full_time',
            'opened_at' => now()->toDateString(),
            'status' => 'open',
        ]);

        $candidate = Candidate::create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
        ]);

        $application = Application::create([
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'stage' => 'new',
            'status' => 'active',
            'applied_at' => now()->toDateString(),
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('data.token');

        $this->withToken($token)
            ->patchJson("/api/v1/applications/{$application->id}/stage", [
                'stage' => 'screening',
                'user_id' => 999,
            ])
            ->assertOk();

        $this->assertDatabaseHas('application_stage_logs', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'to_stage' => 'screening',
        ]);
    }
}
