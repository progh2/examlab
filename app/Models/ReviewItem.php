<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'question_id',
        'stage',
        'due_at',
        'last_result',
        'last_answered_at',
        'wrong_streak',
        'suspended',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_answered_at' => 'datetime',
            'last_result' => 'boolean',
            'suspended' => 'boolean',
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
