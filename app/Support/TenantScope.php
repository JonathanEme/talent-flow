<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait TenantScope
{
    protected function tenantUser(): User
    {
        return auth()->user();
    }

    protected function isGlobal(): bool
    {
        return $this->tenantUser()->isGlobal();
    }

    protected function tenantCompanyId(): int
    {
        return (int) $this->tenantUser()->company_id;
    }

    protected function scopeCompanies(Builder $query): Builder
    {
        return $this->isGlobal() ? $query : $query->whereKey($this->tenantCompanyId());
    }

    protected function scopePositions(Builder $query): Builder
    {
        return $this->isGlobal() ? $query : $query->where('company_id', $this->tenantCompanyId());
    }

    protected function scopeApplications(Builder $query): Builder
    {
        return $this->isGlobal()
            ? $query
            : $query->whereHas('position', fn (Builder $positionQuery): mixed => $positionQuery->where('company_id', $this->tenantCompanyId()));
    }

    protected function scopeCandidates(Builder $query): Builder
    {
        return $this->isGlobal()
            ? $query
            : $query->whereHas('applications.position', fn (Builder $positionQuery): mixed => $positionQuery->where('company_id', $this->tenantCompanyId()));
    }

    protected function scopeInterviews(Builder $query): Builder
    {
        return $this->isGlobal()
            ? $query
            : $query->whereHas('application.position', fn (Builder $positionQuery): mixed => $positionQuery->where('company_id', $this->tenantCompanyId()));
    }

    protected function scopeOffers(Builder $query): Builder
    {
        return $this->isGlobal()
            ? $query
            : $query->whereHas('application.position', fn (Builder $positionQuery): mixed => $positionQuery->where('company_id', $this->tenantCompanyId()));
    }

    protected function ensureCompany(int $companyId): void
    {
        if (! $this->isGlobal() && $this->tenantCompanyId() !== $companyId) {
            abort(403, 'No tienes acceso a esta empresa.');
        }
    }

    protected function ensurePosition(Position $position): void
    {
        $this->ensureCompany((int) $position->company_id);
    }

    protected function ensureApplication(Application $application): void
    {
        $application->loadMissing('position');
        $this->ensurePosition($application->position);
    }

    protected function ensureCandidate(Candidate $candidate): void
    {
        if ($this->isGlobal()) {
            return;
        }

        $candidate->loadMissing('applications.position');

        $allowed = $candidate->applications->contains(
            fn (Application $application): bool => (int) $application->position?->company_id === $this->tenantCompanyId()
        );

        if (! $allowed) {
            abort(403, 'No tienes acceso a este candidato.');
        }
    }

    protected function ensureInterview(Interview $interview): void
    {
        $interview->loadMissing('application.position');
        $this->ensurePosition($interview->application->position);
    }

    protected function ensureOffer(Offer $offer): void
    {
        $offer->loadMissing('application.position');
        $this->ensurePosition($offer->application->position);
    }
}
