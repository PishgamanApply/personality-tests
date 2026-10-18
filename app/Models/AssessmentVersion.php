<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentVersion extends Model
{
    protected $fillable = [
        'assessment_id',
        'version',
        'scoring_config',
        'published_at',
        'time_limit_minutes',
    ];

    protected function casts(): array
    {
        return [
            'scoring_config' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}
