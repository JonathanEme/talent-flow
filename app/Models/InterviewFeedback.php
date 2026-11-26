<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewFeedback extends Model
{
    protected $table = 'interview_feedbacks';

    protected $fillable = [
        'interview_id',
        'user_id',
        'score',
        'strengths',
        'weaknesses',
        'general_notes',
        'recommendation',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
