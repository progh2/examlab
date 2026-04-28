<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'exam_id',
        'key',
        'name_localized',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name_localized' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
