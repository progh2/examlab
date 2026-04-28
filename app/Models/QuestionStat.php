<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionStat extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'question_id',
        'attempts',
        'correct_count',
        'wrong_count',
        'last_seen_at',
        'last_result',
        'wrong_streak',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_result' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
