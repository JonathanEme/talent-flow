<?php

namespace App\Enums;

enum UserRole: string
{
    case GlobalAdmin = 'global_admin';
    case CompanyAdmin = 'company_admin';
    case Member = 'member';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
