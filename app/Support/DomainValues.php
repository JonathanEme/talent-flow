<?php

namespace App\Support;

final class DomainValues
{
    public const SKILL_CATEGORIES = ['technical', 'soft_skill', 'language', 'tool'];

    public const SKILL_LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];

    public const POSITION_STATUSES = ['draft', 'open', 'paused', 'closed'];

    public const SENIORITIES = ['JR', 'SSR', 'SR', 'LEAD', 'junior', 'semi_senior', 'senior', 'lead'];

    public const MODALITIES = ['remote', 'hybrid', 'on_site'];

    public const EMPLOYMENT_TYPES = ['full-time', 'full_time', 'part-time', 'part_time', 'contract', 'internship'];

    public const INTERVIEW_TYPES = ['screening', 'hr', 'technical', 'client', 'final'];

    public const INTERVIEW_STATUSES = ['scheduled', 'completed', 'canceled', 'no_show'];

    public const RECOMMENDATIONS = ['approved', 'rejected', 'on_hold'];

    public const CURRENCIES = ['USD', 'EUR', 'ARS'];
}
