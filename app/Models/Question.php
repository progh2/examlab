<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'exam_id',
        'section_id',
        'topic_id',
        'qa_status',
        'stem_localized',
        'explanation_localized',
        'correct_choice_index',
        'difficulty',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'stem_localized' => 'array',
            'explanation_localized' => 'array',
            'source' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class);
    }
}
