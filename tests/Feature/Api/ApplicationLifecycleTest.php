<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ?array $context = null;

    public function test_initial_stage_is_logged_when_application_is_created(): void
    {
        $context = $this->createContext();

        $response = $this->postJson('/api/v1/applications', [
            'position_id' => $context['position']->id,
            'candidate_id' => $context['candidate']->id,
        ])->assertCreated();

        $this->assertDatabaseHas('application_stage_logs', [
            'application_id' => $response->json('data.id'),
            'user_id' => $context['user']->id,
            'from_stage' => null,
            'to_stage' => 'new',
        ]);
    }

    public function test_invalid_stage_transition_is_rejected(): void
    {
        $application = $this->makeApplication('new');

        $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => 'hired',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'stage' => 'new',
            'status' => 'active',
        ]);
    }

    public function test_valid_stage_transition_synchronizes_status(): void
    {
        $application = $this->makeApplication('final_interview');

        $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => 'offer',
            'comment' => 'Avance a oferta',
        ])->assertOk()
            ->assertJsonPath('data.stage', 'offer')
            ->assertJsonPath('data.status', 'active');

        $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => 'hired',
        ])->assertOk()
            ->assertJsonPath('data.stage', 'hired')
            ->assertJsonPath('data.status', 'hired');
    }

    public function test_backward_stage_transition_is_allowed_and_logged(): void
    {
        $application = $this->makeApplication('screening');

        $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => 'new',
            'comment' => 'Regreso a revisión inicial',
        ])->assertOk()
            ->assertJsonPath('data.stage', 'new')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('application_stage_logs', [
            'application_id' => $application->id,
            'from_stage' => 'screening',
            'to_stage' => 'new',
        ]);
    }

    public function test_offer_acceptance_logs_event_and_hires_application(): void
    {
        $application = $this->makeApplication('offer');
        $offerId = $this->createOffer($application->id);

        $this->patchJson("/api/v1/offers/{$offerId}/status", [
            'status' => 'accepted',
        ])->assertUnprocessable();

        $this->patchJson("/api/v1/offers/{$offerId}/status", [
            'status' => 'sent',
        ])->assertOk();

        $this->patchJson("/api/v1/offers/{$offerId}/status", [
            'status' => 'accepted',
        ])->assertOk();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'stage' => 'hired',
            'status' => 'hired',
        ]);

        $this->assertDatabaseHas('application_stage_logs', [
            'application_id' => $application->id,
            'to_stage' => 'offer_accepted',
        ]);

        $this->assertNotNull(Offer::findOrFail($offerId)->responded_at);
    }

    public function test_timeline_includes_offers_notes_and_every_feedback(): void
    {
        $application = $this->makeApplication('offer', ['notes' => 'Nota de seguimiento']);
        $offerId = $this->createOffer($application->id);

        $this->patchJson("/api/v1/offers/{$offerId}/status", ['status' => 'sent'])->assertOk();
        $this->patchJson("/api/v1/offers/{$offerId}/status", [
            'status' => 'declined',
            'decline_reason' => 'Remuneración insuficiente',
        ])->assertOk();

        $interview = Interview::create([
            'application_id' => $application->id,
            'interviewer_id' => $this->context['user']->id,
            'type' => 'technical',
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $evaluator = User::factory()->create(['company_id' => $application->position->company_id]);

        InterviewFeedback::create([
            'interview_id' => $interview->id,
            'user_id' => $this->context['user']->id,
            'score' => 8,
            'recommendation' => 'approved',
            'general_notes' => 'Buen perfil técnico',
        ]);

        InterviewFeedback::create([
            'interview_id' => $interview->id,
            'user_id' => $evaluator->id,
            'score' => 6,
            'recommendation' => 'on_hold',
            'general_notes' => 'Requiere profundizar en arquitectura',
        ]);

        $response = $this->getJson("/api/v1/applications/{$application->id}/timeline")
            ->assertOk()
            ->assertJsonFragment(['type' => 'offer'])
            ->assertJsonFragment(['to_stage' => 'offer_declined'])
            ->assertJsonFragment(['type' => 'note'])
            ->assertJsonFragment(['notes' => 'Nota de seguimiento'])
            ->assertJsonFragment(['type' => 'interview_feedback'])
            ->assertJsonFragment(['notes' => 'Requiere profundizar en arquitectura']);
    }

    private function createContext(): array
    {
        $company = Company::create(['name' => 'TalentFlow']);
        $user = User::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $position = $company->positions()->create([
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
            'email' => fake()->unique()->safeEmail(),
        ]);

        $this->context = compact('company', 'user', 'position', 'candidate');

        return $this->context;
    }

    private function makeApplication(string $stage = 'new', array $attributes = []): Application
    {
        $context = $this->createContext();

        return Application::create(array_merge([
            'position_id' => $context['position']->id,
            'candidate_id' => $context['candidate']->id,
            'stage' => $stage,
            'status' => 'active',
            'applied_at' => now()->toDateString(),
        ], $attributes));
    }

    private function createOffer(int $applicationId): int
    {
        return $this->postJson('/api/v1/offers', [
            'application_id' => $applicationId,
            'salary_offered' => 50000,
            'currency' => 'USD',
            'start_date' => now()->addWeek()->toDateString(),
        ])->assertCreated()->json('data.id');
    }
}
