<?php

namespace App\Enums;

enum ApplicationStage: string
{
    case New = 'new';
    case Screening = 'screening';
    case TechnicalInterview = 'technical_interview';
    case FinalInterview = 'final_interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function initialValues(): array
    {
        return array_map(
            fn (self $stage): string => $stage->value,
            [self::New, self::Screening, self::TechnicalInterview, self::FinalInterview, self::Offer]
        );
    }

    public function status(): ApplicationStatus
    {
        return match ($this) {
            self::Hired => ApplicationStatus::Hired,
            self::Rejected => ApplicationStatus::Rejected,
            default => ApplicationStatus::Active,
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Screening, self::Rejected],
            self::Screening => [self::TechnicalInterview, self::Rejected],
            self::TechnicalInterview => [self::FinalInterview, self::Rejected],
            self::FinalInterview => [self::Offer, self::Rejected],
            self::Offer => [self::Hired, self::Rejected],
            self::Hired, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $stage): bool
    {
        return in_array($stage, $this->allowedTransitions(), true);
    }
}
