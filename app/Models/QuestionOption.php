<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_key',
        'label',
        'score_values',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'score_values' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
