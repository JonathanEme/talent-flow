<?php

namespace Tests\Feature\Api;

use App\Enums\NotificationCategory;
use App\Models\User;
use App\Notifications\AtsEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAtsData;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use CreatesAtsData;
    use RefreshDatabase;

    public function test_application_creation_notifies_company_and_global_admins_but_not_actor(): void
    {
        Notification::fake();

        $company = $this->makeCompany();
        $actor = User::factory()->companyAdmin($company)->create();
        $otherAdmin = User::factory()->companyAdmin($company)->create();
        $globalAdmin = User::factory()->globalAdmin()->create();
        $member = User::factory()->member($company)->create();

        Sanctum::actingAs($actor);

        $position = $this->makePosition($company);
        $candidate = $this->makeCandidate();

        $this->postJson('/api/v1/applications', [
            'candidate_id' => $candidate->id,
            'position_id' => $position->id,
        ])->assertCreated();

        Notification::assertSentTo($otherAdmin, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::ApplicationCreated);
        Notification::assertSentTo($globalAdmin, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::ApplicationCreated);
        Notification::assertNotSentTo($actor, AtsEventNotification::class);
        Notification::assertNotSentTo($member, AtsEventNotification::class);
    }

    public function test_stage_change_sends_notification_only_when_stage_changes(): void
    {
        Notification::fake();

        $company = $this->makeCompany();
        $actor = User::factory()->companyAdmin($company)->create();
        $otherAdmin = User::factory()->companyAdmin($company)->create();
        Sanctum::actingAs($actor);

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());

        $this->patchJson("/api/v1/applications/{$application->id}/stage", [
            'stage' => 'screening',
        ])->assertOk();

        Notification::assertSentTo($otherAdmin, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::ApplicationStageChanged
            && ($n->data['from_stage'] ?? null) === 'new'
            && ($n->data['to_stage'] ?? null) === 'screening');
    }

    public function test_notifications_index_returns_unread_count_and_marks_read(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->companyAdmin($company)->create();
        Sanctum::actingAs($user);

        $user->notify(new AtsEventNotification(
            NotificationCategory::ApplicationCreated,
            'Nueva postulación',
            'Mensaje de prueba',
            '/pipeline?position=1&application=1',
            ['company_id' => $company->id]
        ));

        $response = $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.data.0.category', NotificationCategory::ApplicationCreated->value);

        $notificationId = $response->json('data.data.0.id');

        $this->patchJson("/api/v1/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('data.read_at', fn ($value) => ! is_null($value));

        $this->patchJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->companyAdmin($company)->create();
        $other = User::factory()->companyAdmin($company)->create();

        $other->notify(new AtsEventNotification(
            NotificationCategory::OfferCreated,
            'Oferta',
            'Mensaje',
            '/offers'
        ));

        $notificationId = $other->notifications()->firstOrFail()->id;

        Sanctum::actingAs($user);
        $this->patchJson("/api/v1/notifications/{$notificationId}/read")->assertNotFound();
    }

    public function test_interview_and_offer_events_create_notifications(): void
    {
        Notification::fake();

        $company = $this->makeCompany();
        $actor = User::factory()->companyAdmin($company)->create();
        $interviewer = User::factory()->member($company)->create();
        $otherAdmin = User::factory()->companyAdmin($company)->create();
        Sanctum::actingAs($actor);

        $application = $this->makeApplication($this->makePosition($company), $this->makeCandidate());

        $interviewResponse = $this->postJson('/api/v1/interviews', [
            'application_id' => $application->id,
            'interviewer_id' => $interviewer->id,
            'type' => 'technical',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 60,
        ])->assertCreated();

        Notification::assertSentTo($interviewer, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::InterviewScheduled);

        $interviewId = $interviewResponse->json('data.id');

        Sanctum::actingAs($interviewer);
        $this->postJson("/api/v1/interviews/{$interviewId}/feedback", [
            'score' => 8,
            'recommendation' => 'approved',
        ])->assertCreated();

        Notification::assertSentTo($otherAdmin, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::InterviewFeedbackSubmitted);

        Sanctum::actingAs($actor);
        $this->patchJson("/api/v1/applications/{$application->id}/stage", ['stage' => 'screening'])->assertOk();
        $this->patchJson("/api/v1/applications/{$application->id}/stage", ['stage' => 'technical_interview'])->assertOk();
        $this->patchJson("/api/v1/applications/{$application->id}/stage", ['stage' => 'final_interview'])->assertOk();

        $this->postJson('/api/v1/offers', [
            'application_id' => $application->id,
            'salary_offered' => 5000,
            'currency' => 'USD',
            'start_date' => now()->addWeek()->toDateString(),
        ])->assertCreated();

        Notification::assertSentTo($otherAdmin, AtsEventNotification::class, fn (AtsEventNotification $n): bool => $n->category === NotificationCategory::OfferCreated);
    }
}
