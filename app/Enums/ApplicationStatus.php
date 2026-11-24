<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Active = 'active';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
