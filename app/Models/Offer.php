<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $fillable = [
        'application_id',
        'created_by',
        'salary_offered',
        'currency',
        'start_date',
        'expiration_date',
        'benefits_summary',
        'terms_and_conditions',
        'status',
        'decline_reason',
        'responded_at',
    ];

    protected $casts = [
        'salary_offered' => 'decimal:2',
        'start_date' => 'date',
        'expiration_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}