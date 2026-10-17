<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateAssessmentResultService
{
    public function calculate(AssessmentAttempt $attempt): AssessmentResult
    {
        return DB::transaction(function () use ($attempt) {
            $attempt = AssessmentAttempt::query()
                ->with([
                    'version.questions.options',
                    'answers.selectedOption',
                    'result',
                ])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'attempt' => 'این آزمون قبلاً پایان یافته یا قابل تکمیل نیست.',
                ]);
            }

            $questions = $attempt->version->questions;

            $answers = $attempt->answers->keyBy('question_id');

            // بررسی پاسخ سؤال‌های اجباری
            foreach ($questions as $question) {
                if (
                    $question->is_required &&
                    ! $answers->has($question->id)
                ) {
                    throw ValidationException::withMessages([
                        'answers' => 'لطفاً به تمام سؤال‌های اجباری پاسخ دهید.',
                    ]);
                }
            }

            $scores = [
                'D' => 0,
                'I' => 0,
                'S' => 0,
                'C' => 0,
            ];

            foreach ($answers as $answer) {
                $option = $answer->selectedOption;

                if (! $option) {
                    throw ValidationException::withMessages([
                        'answers' => 'یکی از پاسخ‌های ثبت‌شده معتبر نیست.',
                    ]);
                }

                $values = $option->score_values ?? [];

                foreach ($scores as $dimension => $currentScore) {
                    $value = $values[$dimension] ?? 0;

                    if (! is_numeric($value)) {
                        throw ValidationException::withMessages([
                            'scoring' => 'تنظیم امتیازدهی یکی از گزینه‌ها معتبر نیست.',
                        ]);
                    }

                    $scores[$dimension] += $value;
                }
            }

            // تعیین بُعد دارای بیشترین امتیاز
            $maxScore = max($scores);

            $topDimensions = array_keys(
                array_filter(
                    $scores,
                    fn ($score) => $score === $maxScore
                )
            );

            // در صورت تساوی، فعلاً primary_type را null می‌گذاریم.
            $primaryType = count($topDimensions) === 1
                ? $topDimensions[0]
                : null;

            $result = AssessmentResult::updateOrCreate(
                [
                    'assessment_attempt_id' => $attempt->id,
                ],
                [
                    'scores' => $scores,
                    'primary_type' => $primaryType,
                    'report' => [
                        'scores' => $scores,
                        'primary_type' => $primaryType,
                    ],
                    'calculated_at' => now(),
                ]
            );

            $attempt->status = 'completed';
            $attempt->completed_at = now();
            $attempt->save();

            return $result;
        });
    }
}