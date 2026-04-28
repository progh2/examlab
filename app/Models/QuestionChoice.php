<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionChoice extends Model
{
    protected $fillable = [
        'question_id',
        'choice_index',
        'text_localized',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'text_localized' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
