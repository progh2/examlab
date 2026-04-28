<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAnswer extends Model
{
    protected $fillable = [
        'practice_session_id',
        'question_id',
        'selected_choice_index',
        'is_correct',
        'choice_order',
        'wrong_reason',
        'confidence',
        'time_spent_seconds',
        'checked',
        'explanation_viewed',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'choice_order' => 'array',
            'checked' => 'boolean',
            'explanation_viewed' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
