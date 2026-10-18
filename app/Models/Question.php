<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'assessment_version_id',
        'question_key',
        'prompt',
        'type',
        'sort_order',
        'is_required',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'assessment_version_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }
}
