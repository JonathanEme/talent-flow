<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    protected $fillable = [
        'position_id',
        'candidate_id',
        'status',
        'stage',
        'rejection_reason',
        'notes',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'date',
        'status' => ApplicationStatus::class,
        'stage' => ApplicationStage::class,
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(ApplicationStageLog::class)->orderBy('created_at', 'desc');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest();
    }

    public function latestOffer(): HasOne
    {
        return $this->hasOne(Offer::class)->latestOfMany();
    }
}
