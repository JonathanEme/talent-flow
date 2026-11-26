<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class InterviewAndOfferQualityTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_interview_rejects_invalid_schedule_type_and_duration(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        Sanctum::actingAs($user);

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());

        $this->postJson('/api/v1/interviews', [
            'application_id' => $application->id,
            'type' => 'wrong',
            'scheduled_at' => now()->subDay()->toDateTimeString(),
            'duration_minutes' => 10,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'scheduled_at', 'duration_minutes']);
    }

    public function test_interview_rejects_interviewer_from_another_company(): void
    {
        $company = $this->makeCompany();
        $foreignCompany = $this->makeCompany(['name' => 'Otra empresa']);
        $foreignUser = $this->makeUser($foreignCompany);

        Sanctum::actingAs($this->makeUser($company));

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());

        $this->postJson('/api/v1/interviews', [
            'application_id' => $application->id,
            'interviewer_id' => $foreignUser->id,
            'type' => 'technical',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_feedback_is_upserted_per_evaluator_and_completes_interview(): void
    {
        $company = $this->makeCompany();
        $evaluator = $this->makeUser($company);
        Sanctum::actingAs($evaluator);

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());
        $interview = $this->makeInterview($application, $evaluator);

        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 8,
            'recommendation' => 'approved',
        ])->assertCreated();

        $this->assertDatabaseCount('interview_feedbacks', 1);
        $this->assertSame('completed', $interview->fresh()->status);

        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 9,
            'recommendation' => 'approved',
        ])->assertCreated();

        $this->assertDatabaseCount('interview_feedbacks', 1);
        $this->assertDatabaseHas('interview_feedbacks', [
            'interview_id' => $interview->id,
            'user_id' => $evaluator->id,
            'score' => 9,
        ]);

        Sanctum::actingAs($this->makeUser($company));

        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 6,
            'recommendation' => 'on_hold',
        ])->assertCreated();

        $this->assertDatabaseCount('interview_feedbacks', 2);
    }

    public function test_feedback_rejects_invalid_score_and_recommendation(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        Sanctum::actingAs($user);

        $interview = $this->makeInterview(
            $this->makeApplication($this->makePosition($company), $this->makeCandidate()),
            $user
        );

        $this->postJson("/api/v1/interviews/{$interview->id}/feedback", [
            'score' => 11,
            'recommendation' => 'maybe',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['score', 'recommendation']);
    }

    public function test_accepted_offer_cannot_go_back_to_sent(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());
        $offer = $this->makeOffer($application, null, ['status' => 'accepted']);

        $this->patchJson("/api/v1/offers/{$offer->id}/status", ['status' => 'sent'])
            ->assertUnprocessable();

        $this->assertSame('accepted', $offer->fresh()->status->value);
    }

    public function test_declining_offer_requires_a_reason_and_syncs_application(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());
        $offer = $this->makeOffer($application, null, ['status' => 'sent']);

        $this->patchJson("/api/v1/offers/{$offer->id}/status", ['status' => 'declined'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['decline_reason']);

        $this->patchJson("/api/v1/offers/{$offer->id}/status", [
            'status' => 'declined',
            'decline_reason' => 'El candidato aceptó otra posición.',
        ])->assertOk();

        $this->assertSame('declined', $offer->fresh()->status->value);
        $this->assertSame('rejected', $application->fresh()->stage->value);
    }

    public function test_offer_store_validates_money_and_dates(): void
    {
        $company = $this->makeCompany();
        Sanctum::actingAs($this->makeUser($company));

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());

        $this->postJson('/api/v1/offers', [
            'application_id' => $application->id,
            'salary_offered' => 0,
            'currency' => 'XXX',
            'start_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->subMonth()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['salary_offered', 'currency', 'start_date', 'expiration_date']);
    }
}
