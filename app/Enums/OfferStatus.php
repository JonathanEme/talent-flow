<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function transitionalValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            [self::Sent, self::Accepted, self::Declined]
        );
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => $status === self::Sent,
            self::Sent => in_array($status, [self::Accepted, self::Declined], true),
            self::Accepted, self::Declined => false,
        };
    }
}
