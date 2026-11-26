<?php

namespace Tests\Concerns;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;

trait CreatesAtsData
{
    protected function makeCompany(array $attributes = []): Company
    {
        return Company::create(array_merge([
            'name' => 'TalentFlow',
        ], $attributes));
    }

    protected function makeUser(?Company $company = null, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'company_id' => $company?->id,
        ], $attributes));
    }

    protected function makePosition(Company $company, array $attributes = []): Position
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

    protected function makeCandidate(array $attributes = []): Candidate
    {
        return Candidate::create(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => fake()->unique()->safeEmail(),
        ], $attributes));
    }

    protected function makeApplication(Position $position, Candidate $candidate, array $attributes = []): Application
    {
        return Application::create(array_merge([
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'stage' => 'new',
            'status' => 'active',
            'applied_at' => now()->toDateString(),
        ], $attributes));
    }

    protected function makeInterview(Application $application, ?User $interviewer = null, array $attributes = []): Interview
    {
        return Interview::create(array_merge([
            'application_id' => $application->id,
            'interviewer_id' => $interviewer?->id ?? auth()->id(),
            'type' => 'technical',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ], $attributes));
    }

    protected function makeOffer(Application $application, ?User $creator = null, array $attributes = []): Offer
    {
        return Offer::create(array_merge([
            'application_id' => $application->id,
            'created_by' => $creator?->id ?? auth()->id(),
            'salary_offered' => 5000,
            'currency' => 'USD',
            'start_date' => now()->toDateString(),
            'status' => 'draft',
        ], $attributes));
    }

    protected function makeSkill(string $name, array $attributes = []): Skill
    {
        return Skill::create(array_merge([
            'name' => $name,
            'category' => 'technical',
        ], $attributes));
    }
}
