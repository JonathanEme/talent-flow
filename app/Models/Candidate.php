<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Candidate extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'linkedin_url',
        'portfolio_url',
        'cv_path',
        'location',
        'salary_expectation',
        'summary',
    ];

    protected $casts = [
        'salary_expectation' => 'decimal:2',
    ];

    // Relación directa con sus postulaciones
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    // Posiciones en las que participó/participa
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'applications')
            ->withPivot(['id', 'status', 'stage', 'applied_at'])
            ->withTimestamps();
    }
}