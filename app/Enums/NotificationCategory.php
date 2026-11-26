<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case ApplicationCreated = 'application.created';
    case ApplicationStageChanged = 'application.stage_changed';
    case InterviewScheduled = 'interview.scheduled';
    case InterviewStatusChanged = 'interview.status_changed';
    case InterviewFeedbackSubmitted = 'interview.feedback_submitted';
    case OfferCreated = 'offer.created';
    case OfferStatusChanged = 'offer.status_changed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
