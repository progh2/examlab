<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'tenant_id',
        'code',
        'name_localized',
        'primary_locale',
        'blueprint_type',
    ];

    protected function casts(): array
    {
        return [
            'name_localized' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
