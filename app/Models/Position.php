<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'applications')
            ->withPivot(['id', 'status', 'stage', 'applied_at'])
            ->withTimestamps();
    }
}