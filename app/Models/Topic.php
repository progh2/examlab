<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'section_id',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
