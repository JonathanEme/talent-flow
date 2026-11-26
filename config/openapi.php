<?php

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\OfferStatus;
use App\Support\DomainValues;

$string = fn (array $extra = []): array => array_merge(['type' => 'string'], $extra);
$nullableString = fn (array $extra = []): array => array_merge(['type' => 'string', 'nullable' => true], $extra);
$integer = fn (array $extra = []): array => array_merge(['type' => 'integer'], $extra);
$nullableInteger = fn (array $extra = []): array => array_merge(['type' => 'integer', 'nullable' => true], $extra);
$boolean = fn (): array => ['type' => 'boolean'];
$number = fn (array $extra = []): array => array_merge(['type' => 'number'], $extra);
$numeric = fn (array $extra = []): array => array_merge(['oneOf' => [['type' => 'number'], ['type' => 'string']]], $extra);
$nullableNumeric = fn (array $extra = []): array => array_merge(['oneOf' => [['type' => 'number', 'nullable' => true], ['type' => 'string', 'nullable' => true]]], $extra);
$date = fn (array $extra = []): array => array_merge(['type' => 'string', 'format' => 'date'], $extra);
$nullableDate = fn (): array => ['type' => 'string', 'format' => 'date', 'nullable' => true];
$dateTime = fn (array $extra = []): array => array_merge(['type' => 'string', 'format' => 'date-time'], $extra);
$nullableDateTime = fn (): array => ['type' => 'string', 'format' => 'date-time', 'nullable' => true];
$enum = fn (array $values, array $extra = []): array => array_merge(['type' => 'string', 'enum' => $values], $extra);
$nullableEnum = fn (array $values): array => ['type' => 'string', 'enum' => $values, 'nullable' => true];
$ref = fn (string $schema): array => ['$ref' => '#/components/schemas/'.$schema];
$arrayOf = fn (string $schema, array $extra = []): array => array_merge(['type' => 'array', 'items' => $ref($schema)], $extra);
$nullableArrayOf = fn (string $schema): array => ['type' => 'array', 'nullable' => true, 'items' => $ref($schema)];

return [
    'schemas' => [
        'ApiError' => [
            'type' => 'object',
            'required' => ['message'],
            'properties' => [
                'message' => $string(),
                'errors' => [
                    'type' => 'object',
                    'nullable' => true,
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => $string(),
                    ],
                ],
            ],
        ],

        'MessageSchema' => [
            'type' => 'object',
            'required' => ['message'],
            'properties' => [
                'message' => $string(),
            ],
        ],

        'PaginationLinksSchema' => [
            'type' => 'object',
            'required' => ['first', 'last', 'prev', 'next'],
            'properties' => [
                'first' => $nullableString(['format' => 'uri']),
                'last' => $nullableString(['format' => 'uri']),
                'prev' => $nullableString(['format' => 'uri']),
                'next' => $nullableString(['format' => 'uri']),
            ],
        ],

        'PaginationMetaSchema' => [
            'type' => 'object',
            'required' => ['current_page', 'from', 'last_page', 'links', 'per_page', 'to', 'total'],
            'properties' => [
                'current_page' => $integer(),
                'from' => $nullableInteger(),
                'last_page' => $integer(),
                'links' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['url', 'label', 'active'],
                        'properties' => [
                            'url' => $nullableString(),
                            'label' => $string(),
                            'active' => $boolean(),
                        ],
                    ],
                ],
                'per_page' => $integer(),
                'to' => $nullableInteger(),
                'total' => $integer(),
            ],
        ],

        'LoginRequestSchema' => [
            'type' => 'object',
            'required' => ['email', 'password'],
            'properties' => [
                'email' => $string(['format' => 'email', 'maxLength' => 255]),
                'password' => $string(['minLength' => 1]),
            ],
        ],

        'LoginResponseSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['user', 'token', 'token_type'],
                    'properties' => [
                        'user' => $ref('UserResourceSchema'),
                        'token' => $string(),
                        'token_type' => $string(['enum' => ['Bearer']]),
                    ],
                ],
            ],
        ],

        'MeResponseSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => $ref('UserResourceSchema'),
            ],
        ],

        'CompanyStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => $string(['maxLength' => 255]),
            ],
        ],

        'PositionIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'status' => $enum(DomainValues::POSITION_STATUSES),
                'seniority' => $enum(DomainValues::SENIORITIES),
                'search' => $string(['maxLength' => 255]),
            ],
        ],

        'PositionStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['company_id', 'title', 'description', 'status', 'seniority', 'location', 'modality', 'employment_type'],
            'properties' => [
                'company_id' => $integer(['description' => 'ID de empresa existente.']),
                'title' => $string(['maxLength' => 255]),
                'description' => $string(),
                'status' => $enum(DomainValues::POSITION_STATUSES),
                'seniority' => $enum(DomainValues::SENIORITIES),
                'location' => $string(['maxLength' => 255]),
                'modality' => $enum(DomainValues::MODALITIES),
                'employment_type' => $enum(DomainValues::EMPLOYMENT_TYPES),
                'salary_min' => $nullableNumeric(),
                'salary_max' => $nullableNumeric(),
                'currency' => $nullableEnum(DomainValues::CURRENCIES),
            ],
        ],

        'CandidateIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'search' => $string(['maxLength' => 255]),
                'skill' => $string(['maxLength' => 255]),
                'location' => $string(['maxLength' => 255]),
            ],
        ],

        'CandidateStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['full_name', 'email'],
            'properties' => [
                'full_name' => $string(['maxLength' => 255]),
                'email' => $string(['format' => 'email', 'maxLength' => 255]),
                'phone' => $nullableString(['maxLength' => 50]),
                'location' => $nullableString(['maxLength' => 255]),
                'resume_summary' => $nullableString(),
                'years_experience' => $nullableInteger(['minimum' => 0]),
                'desired_role' => $nullableString(['maxLength' => 255]),
            ],
        ],

        'ApplicationIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'position_id' => $integer(),
                'stage' => $enum(ApplicationStage::values()),
                'status' => $enum(ApplicationStatus::values()),
            ],
        ],

        'ApplicationStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['candidate_id', 'position_id'],
            'properties' => [
                'candidate_id' => $integer(['description' => 'ID de candidato existente en la empresa activa.']),
                'position_id' => $integer(['description' => 'ID de posición existente en la empresa activa.']),
            ],
        ],

        'UpdateApplicationStageRequestSchema' => [
            'type' => 'object',
            'required' => ['stage'],
            'properties' => [
                'stage' => $enum(ApplicationStage::values(), ['description' => 'Destino permitido según la etapa actual.']),
            ],
        ],

        'SkillIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'category' => $enum(DomainValues::SKILL_CATEGORIES),
                'search' => $string(['maxLength' => 255]),
            ],
        ],

        'SkillStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['name', 'category'],
            'properties' => [
                'name' => $string(['maxLength' => 255]),
                'category' => $enum(DomainValues::SKILL_CATEGORIES),
            ],
        ],

        'InterviewIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'application_id' => $integer(),
                'type' => $enum(DomainValues::INTERVIEW_TYPES),
                'status' => $enum(DomainValues::INTERVIEW_STATUSES),
                'from' => $date(),
                'to' => $date(),
            ],
        ],

        'InterviewStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['application_id', 'type', 'scheduled_at', 'duration_minutes'],
            'properties' => [
                'application_id' => $integer(['description' => 'ID de aplicación existente en la empresa activa.']),
                'type' => $enum(DomainValues::INTERVIEW_TYPES),
                'scheduled_at' => $dateTime(),
                'duration_minutes' => $integer(['minimum' => 1]),
                'location' => $nullableString(['maxLength' => 255]),
                'notes' => $nullableString(),
            ],
        ],

        'InterviewUpdateRequestSchema' => [
            'type' => 'object',
            'properties' => [
                'type' => $enum(DomainValues::INTERVIEW_TYPES),
                'scheduled_at' => $dateTime(),
                'duration_minutes' => $integer(['minimum' => 1]),
                'status' => $enum(DomainValues::INTERVIEW_STATUSES),
                'location' => $nullableString(['maxLength' => 255]),
                'notes' => $nullableString(),
            ],
        ],

        'InterviewFeedbackStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['score', 'comments', 'recommendation'],
            'properties' => [
                'score' => $number(['minimum' => 0, 'maximum' => 10]),
                'comments' => $string(),
                'recommendation' => $enum(DomainValues::RECOMMENDATIONS),
            ],
        ],

        'OfferIndexQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'application_id' => $integer(),
                'status' => $enum(OfferStatus::values()),
            ],
        ],

        'OfferStoreRequestSchema' => [
            'type' => 'object',
            'required' => ['application_id', 'salary', 'currency', 'start_date'],
            'properties' => [
                'application_id' => $integer(['description' => 'ID de aplicación existente en la empresa activa.']),
                'salary' => $numeric(['description' => 'Monto ofrecido; debe ser numérico o string numérico.']),
                'currency' => $enum(DomainValues::CURRENCIES),
                'start_date' => $date(),
                'notes' => $nullableString(),
            ],
        ],

        'OfferUpdateStatusRequestSchema' => [
            'oneOf' => [
                [
                    'type' => 'object',
                    'required' => ['status'],
                    'properties' => [
                        'status' => $enum(['sent', 'accepted']),
                    ],
                ],
                [
                    'type' => 'object',
                    'required' => ['status', 'decline_reason'],
                    'properties' => [
                        'status' => $enum(['declined']),
                        'decline_reason' => $string(['maxLength' => 2000]),
                    ],
                ],
            ],
        ],

        'PositionRequirementItemSchema' => [
            'type' => 'object',
            'required' => ['skill_id', 'required_level', 'priority'],
            'properties' => [
                'skill_id' => $integer(),
                'required_level' => $enum(DomainValues::SKILL_LEVELS),
                'priority' => $boolean(),
            ],
        ],

        'SyncPositionRequirementsRequestSchema' => [
            'type' => 'object',
            'required' => ['skills'],
            'properties' => [
                'skills' => $arrayOf('PositionRequirementItemSchema', ['uniqueItems' => true]),
            ],
        ],

        'CandidateSkillItemSchema' => [
            'type' => 'object',
            'required' => ['skill_id', 'level'],
            'properties' => [
                'skill_id' => $integer(),
                'level' => $enum(DomainValues::SKILL_LEVELS),
                'years_experience' => $nullableInteger(['minimum' => 0]),
            ],
        ],

        'SyncCandidateSkillsRequestSchema' => [
            'type' => 'object',
            'required' => ['skills'],
            'properties' => [
                'skills' => $arrayOf('CandidateSkillItemSchema', ['uniqueItems' => true]),
            ],
        ],

        'PositionRecommendationsQuerySchema' => [
            'type' => 'object',
            'properties' => [
                'limit' => $integer(['default' => 10, 'minimum' => 1, 'maximum' => 100]),
            ],
        ],

        'UserResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'name', 'email', 'role'],
            'properties' => [
                'id' => $integer(),
                'name' => $string(),
                'email' => $string(['format' => 'email']),
                'role' => $string(['description' => 'Rol global del usuario: global_admin, company_admin o member.']),
            ],
        ],

        'CompanyResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'name', 'slug'],
            'properties' => [
                'id' => $integer(),
                'name' => $string(),
                'slug' => $string(),
            ],
        ],

        'SkillPivotSchema' => [
            'type' => 'object',
            'properties' => [
                'level' => $enum(DomainValues::SKILL_LEVELS),
                'required_level' => $enum(DomainValues::SKILL_LEVELS),
                'years_experience' => $nullableInteger(),
                'priority' => $boolean(),
            ],
        ],

        'SkillResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'name', 'category'],
            'properties' => [
                'id' => $integer(),
                'name' => $string(),
                'category' => $enum(DomainValues::SKILL_CATEGORIES),
                'pivot' => $ref('SkillPivotSchema'),
            ],
        ],

        'PositionResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'title', 'description', 'status', 'seniority', 'location', 'modality', 'employment_type'],
            'properties' => [
                'id' => $integer(),
                'title' => $string(),
                'description' => $string(),
                'status' => $enum(DomainValues::POSITION_STATUSES),
                'seniority' => $enum(DomainValues::SENIORITIES),
                'location' => $string(),
                'modality' => $enum(DomainValues::MODALITIES),
                'employment_type' => $enum(DomainValues::EMPLOYMENT_TYPES),
                'salary_min' => $nullableString(),
                'salary_max' => $nullableString(),
                'currency' => $nullableEnum(DomainValues::CURRENCIES),
                'company' => $ref('CompanyResourceSchema'),
                'skills' => $arrayOf('SkillResourceSchema'),
            ],
        ],

        'CandidateResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'full_name', 'email'],
            'properties' => [
                'id' => $integer(),
                'full_name' => $string(),
                'email' => $string(['format' => 'email']),
                'phone' => $nullableString(),
                'location' => $nullableString(),
                'resume_summary' => $nullableString(),
                'years_experience' => $nullableInteger(),
                'desired_role' => $nullableString(),
                'skills' => $arrayOf('SkillResourceSchema'),
            ],
        ],

        'ApplicationResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'stage', 'status'],
            'properties' => [
                'id' => $integer(),
                'stage' => $enum(ApplicationStage::values()),
                'status' => $enum(ApplicationStatus::values()),
                'candidate' => $ref('CandidateResourceSchema'),
                'position' => $ref('PositionResourceSchema'),
                'interviews' => $arrayOf('InterviewResourceSchema'),
                'offers' => $arrayOf('OfferResourceSchema'),
                'stageLogs' => $arrayOf('ApplicationStageLogResourceSchema'),
            ],
        ],

        'ApplicationStageLogResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'from_stage', 'to_stage', 'changed_at'],
            'properties' => [
                'id' => $integer(),
                'from_stage' => $nullableEnum(ApplicationStage::values()),
                'to_stage' => $enum(ApplicationStage::values()),
                'changed_at' => $dateTime(),
                'changedBy' => $ref('UserResourceSchema'),
            ],
        ],

        'InterviewResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'scheduled_at', 'duration_minutes', 'status'],
            'properties' => [
                'id' => $integer(),
                'type' => $enum(DomainValues::INTERVIEW_TYPES),
                'scheduled_at' => $dateTime(),
                'duration_minutes' => $integer(),
                'status' => $enum(DomainValues::INTERVIEW_STATUSES),
                'location' => $nullableString(),
                'notes' => $nullableString(),
                'application' => $ref('ApplicationResourceSchema'),
                'interviewer' => $ref('UserResourceSchema'),
                'feedback' => $ref('InterviewFeedbackResourceSchema'),
            ],
        ],

        'InterviewFeedbackResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'score', 'comments', 'recommendation'],
            'properties' => [
                'id' => $integer(),
                'score' => $number(),
                'comments' => $string(),
                'recommendation' => $enum(DomainValues::RECOMMENDATIONS),
                'interviewer' => $ref('UserResourceSchema'),
            ],
        ],

        'OfferResourceSchema' => [
            'type' => 'object',
            'required' => ['id', 'salary', 'currency', 'start_date', 'status'],
            'properties' => [
                'id' => $integer(),
                'salary' => $string(),
                'currency' => $enum(DomainValues::CURRENCIES),
                'start_date' => $date(),
                'status' => $enum(OfferStatus::values()),
                'notes' => $nullableString(),
                'decline_reason' => $nullableString(),
                'application' => $ref('ApplicationResourceSchema'),
            ],
        ],

        'MatchFactorSchema' => [
            'type' => 'object',
            'required' => ['key', 'label', 'score', 'weight', 'reason'],
            'properties' => [
                'key' => $string(),
                'label' => $string(),
                'score' => $number(),
                'weight' => $number(),
                'reason' => $string(),
            ],
        ],

        'MatchDetailSchema' => [
            'type' => 'object',
            'properties' => [
                'skills' => $ref('MatchFactorSchema'),
                'seniority' => $ref('MatchFactorSchema'),
                'location' => $ref('MatchFactorSchema'),
                'experience' => $ref('MatchFactorSchema'),
            ],
        ],

        'MatchSchema' => [
            'type' => 'object',
            'required' => ['score', 'recommendation'],
            'properties' => [
                'score' => $number(['minimum' => 0, 'maximum' => 100]),
                'recommendation' => $enum(DomainValues::RECOMMENDATIONS),
                'candidate' => $ref('CandidateResourceSchema'),
                'position' => $ref('PositionResourceSchema'),
                'matched_skills' => $arrayOf('SkillResourceSchema'),
                'missing_skills' => $arrayOf('SkillResourceSchema'),
                'details' => $ref('MatchDetailSchema'),
            ],
        ],

        'MatchResponseSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => $ref('MatchSchema'),
            ],
        ],

        'RecommendationsResponseSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['position', 'data'],
                    'properties' => [
                        'position' => $ref('PositionResourceSchema'),
                        'data' => $arrayOf('MatchSchema'),
                    ],
                ],
            ],
        ],

        'DashboardKpisSchema' => [
            'type' => 'object',
            'required' => ['active_positions', 'total_candidates', 'active_applications', 'scheduled_interviews', 'open_offers'],
            'properties' => [
                'active_positions' => $integer(),
                'total_candidates' => $integer(),
                'active_applications' => $integer(),
                'scheduled_interviews' => $integer(),
                'open_offers' => $integer(),
            ],
        ],

        'StageDistributionSchema' => [
            'type' => 'object',
            'required' => ['stage', 'label', 'total'],
            'properties' => [
                'stage' => $enum(ApplicationStage::values()),
                'label' => $string(),
                'total' => $integer(),
            ],
        ],

        'DashboardSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'required' => ['kpis', 'stage_distribution', 'top_skills', 'recent_applications'],
                    'properties' => [
                        'kpis' => $ref('DashboardKpisSchema'),
                        'stage_distribution' => $arrayOf('StageDistributionSchema'),
                        'top_skills' => $arrayOf('SkillResourceSchema'),
                        'recent_applications' => $arrayOf('ApplicationResourceSchema'),
                    ],
                ],
            ],
        ],

        'TimelineStageEventDetailsSchema' => [
            'type' => 'object',
            'properties' => [
                'from_stage' => $nullableEnum(ApplicationStage::values()),
                'to_stage' => $enum(ApplicationStage::values()),
            ],
        ],

        'TimelineInterviewEventDetailsSchema' => [
            'type' => 'object',
            'properties' => [
                'type' => $enum(DomainValues::INTERVIEW_TYPES),
                'scheduled_at' => $dateTime(),
                'status' => $enum(DomainValues::INTERVIEW_STATUSES),
            ],
        ],

        'TimelineFeedbackEventDetailsSchema' => [
            'type' => 'object',
            'properties' => [
                'score' => $number(),
                'recommendation' => $enum(DomainValues::RECOMMENDATIONS),
            ],
        ],

        'TimelineOfferEventDetailsSchema' => [
            'type' => 'object',
            'properties' => [
                'status' => $enum(OfferStatus::values()),
                'salary' => $string(),
                'currency' => $enum(DomainValues::CURRENCIES),
            ],
        ],

        'TimelineNoteEventDetailsSchema' => [
            'type' => 'object',
            'properties' => [
                'notes' => $string(),
            ],
        ],

        'TimelineStageEventSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'title', 'details', 'timestamp'],
            'properties' => [
                'id' => $string(),
                'type' => $enum(['stage_change']),
                'title' => $string(),
                'details' => $ref('TimelineStageEventDetailsSchema'),
                'timestamp' => $dateTime(),
            ],
        ],

        'TimelineInterviewEventSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'title', 'details', 'timestamp'],
            'properties' => [
                'id' => $string(),
                'type' => $enum(['interview_event']),
                'title' => $string(),
                'details' => $ref('TimelineInterviewEventDetailsSchema'),
                'timestamp' => $dateTime(),
            ],
        ],

        'TimelineFeedbackEventSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'title', 'details', 'timestamp'],
            'properties' => [
                'id' => $string(),
                'type' => $enum(['feedback_event']),
                'title' => $string(),
                'details' => $ref('TimelineFeedbackEventDetailsSchema'),
                'timestamp' => $dateTime(),
            ],
        ],

        'TimelineOfferEventSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'title', 'details', 'timestamp'],
            'properties' => [
                'id' => $string(),
                'type' => $enum(['offer_event']),
                'title' => $string(),
                'details' => $ref('TimelineOfferEventDetailsSchema'),
                'timestamp' => $dateTime(),
            ],
        ],

        'TimelineNoteEventSchema' => [
            'type' => 'object',
            'required' => ['id', 'type', 'title', 'details', 'timestamp'],
            'properties' => [
                'id' => $string(),
                'type' => $enum(['note_event']),
                'title' => $string(),
                'details' => $ref('TimelineNoteEventDetailsSchema'),
                'timestamp' => $dateTime(),
            ],
        ],

        'TimelineEventSchema' => [
            'oneOf' => [
                $ref('TimelineStageEventSchema'),
                $ref('TimelineInterviewEventSchema'),
                $ref('TimelineFeedbackEventSchema'),
                $ref('TimelineOfferEventSchema'),
                $ref('TimelineNoteEventSchema'),
            ],
            'discriminator' => [
                'propertyName' => 'type',
            ],
        ],

        'TimelineSchema' => [
            'type' => 'object',
            'required' => ['data'],
            'properties' => [
                'data' => $arrayOf('TimelineEventSchema'),
            ],
        ],
    ],

    'resources' => [
        'User' => 'UserResourceSchema',
        'Company' => 'CompanyResourceSchema',
        'Position' => 'PositionResourceSchema',
        'Candidate' => 'CandidateResourceSchema',
        'Application' => 'ApplicationResourceSchema',
        'Skill' => 'SkillResourceSchema',
        'Interview' => 'InterviewResourceSchema',
        'InterviewFeedback' => 'InterviewFeedbackResourceSchema',
        'Offer' => 'OfferResourceSchema',
    ],

    'operations' => [
        'post /auth/login' => [
            'request' => 'LoginRequestSchema',
            'response' => 'LoginResponseSchema',
        ],
        'post /auth/logout' => [
            'response' => 'MessageSchema',
        ],
        'get /auth/me' => [
            'response' => 'MeResponseSchema',
        ],
        'get /dashboard' => [
            'response' => 'DashboardSchema',
        ],
        'get /companies' => [
            'response' => 'PaginatedCompanySchema',
        ],
        'post /companies' => [
            'request' => 'CompanyStoreRequestSchema',
            'response' => 'DataCompanySchema',
            'status' => 201,
            'forbidden' => true,
        ],
        'get /companies/{company}' => [
            'response' => 'DataCompanySchema',
            'not_found' => true,
        ],
        'get /positions' => [
            'query' => 'PositionIndexQuerySchema',
            'response' => 'PaginatedPositionSchema',
        ],
        'post /positions' => [
            'request' => 'PositionStoreRequestSchema',
            'response' => 'DataPositionSchema',
            'status' => 201,
            'not_found' => true,
        ],
        'get /positions/{position}' => [
            'response' => 'DataPositionSchema',
            'not_found' => true,
        ],
        'get /candidates' => [
            'query' => 'CandidateIndexQuerySchema',
            'response' => 'PaginatedCandidateSchema',
        ],
        'post /candidates' => [
            'request' => 'CandidateStoreRequestSchema',
            'response' => 'DataCandidateSchema',
            'status' => 201,
        ],
        'get /candidates/{candidate}' => [
            'response' => 'DataCandidateSchema',
            'not_found' => true,
        ],
        'get /applications' => [
            'query' => 'ApplicationIndexQuerySchema',
            'response' => 'PaginatedApplicationSchema',
        ],
        'post /applications' => [
            'request' => 'ApplicationStoreRequestSchema',
            'response' => 'DataApplicationSchema',
            'status' => 201,
            'not_found' => true,
        ],
        'get /applications/{application}' => [
            'response' => 'DataApplicationSchema',
            'not_found' => true,
        ],
        'get /applications/{application}/timeline' => [
            'response' => 'TimelineSchema',
            'not_found' => true,
        ],
        'patch /applications/{application}/stage' => [
            'request' => 'UpdateApplicationStageRequestSchema',
            'response' => 'MessageAndApplicationResponseSchema',
            'not_found' => true,
        ],
        'get /skills' => [
            'query' => 'SkillIndexQuerySchema',
            'response' => 'PaginatedSkillSchema',
        ],
        'post /skills' => [
            'request' => 'SkillStoreRequestSchema',
            'response' => 'DataSkillSchema',
            'status' => 201,
        ],
        'get /interviews' => [
            'query' => 'InterviewIndexQuerySchema',
            'response' => 'PaginatedInterviewSchema',
        ],
        'post /interviews' => [
            'request' => 'InterviewStoreRequestSchema',
            'response' => 'DataInterviewSchema',
            'status' => 201,
            'not_found' => true,
        ],
        'get /interviews/{interview}' => [
            'response' => 'DataInterviewSchema',
            'not_found' => true,
        ],
        'put /interviews/{interview}' => [
            'request' => 'InterviewUpdateRequestSchema',
            'response' => 'DataInterviewSchema',
            'not_found' => true,
        ],
        'patch /interviews/{interview}' => [
            'request' => 'InterviewUpdateRequestSchema',
            'response' => 'DataInterviewSchema',
            'not_found' => true,
        ],
        'post /interviews/{interview}/feedback' => [
            'request' => 'InterviewFeedbackStoreRequestSchema',
            'response' => 'MessageAndInterviewFeedbackResponseSchema',
            'status' => 201,
            'not_found' => true,
        ],
        'get /offers' => [
            'query' => 'OfferIndexQuerySchema',
            'response' => 'PaginatedOfferSchema',
        ],
        'post /offers' => [
            'request' => 'OfferStoreRequestSchema',
            'response' => 'MessageAndOfferResponseSchema',
            'status' => 201,
            'not_found' => true,
        ],
        'get /offers/{offer}' => [
            'response' => 'DataOfferSchema',
            'not_found' => true,
        ],
        'patch /offers/{offer}/status' => [
            'request' => 'OfferUpdateStatusRequestSchema',
            'response' => 'MessageAndOfferResponseSchema',
            'not_found' => true,
        ],
        'post /positions/{position}/requirements' => [
            'request' => 'SyncPositionRequirementsRequestSchema',
            'response' => 'MessageAndPositionResponseSchema',
            'not_found' => true,
        ],
        'post /candidates/{candidate}/skills' => [
            'request' => 'SyncCandidateSkillsRequestSchema',
            'response' => 'MessageAndCandidateResponseSchema',
            'not_found' => true,
        ],
        'get /positions/{position}/recommendations' => [
            'query' => 'PositionRecommendationsQuerySchema',
            'response' => 'RecommendationsResponseSchema',
            'not_found' => true,
        ],
        'get /positions/{position}/candidates/{candidate}/match' => [
            'response' => 'MatchResponseSchema',
            'not_found' => true,
        ],
    ],
];
