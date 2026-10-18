<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    protected $fillable = [
        'assessment_attempt_id',
        'scores',
        'primary_type',
        'report',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'report' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }
}
