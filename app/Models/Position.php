<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'seniority',
        'modality',
        'location',
        'employment_type',
        'salary_min',
        'salary_max',
        'vacancies',
        'opened_at',
        'closes_at',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'date',
        'closes_at' => 'date',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}