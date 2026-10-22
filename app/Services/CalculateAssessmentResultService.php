<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use App\Services\AssessmentScoring\AssessmentScoringStrategyInterface;
use App\Services\AssessmentScoring\DiscScoringStrategy;
use App\Services\AssessmentScoring\MbtiScoringStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateAssessmentResultService
{
    public function __construct(
        protected DiscScoringStrategy $discStrategy,
        protected MbtiScoringStrategy $mbtiStrategy
    ) {}

    public function calculate(
        AssessmentAttempt $attempt,
        bool $timedOut = false
    ): AssessmentResult {
        return DB::transaction(function () use ($attempt, $timedOut) {
            $attempt = AssessmentAttempt::query()
                ->with([
                    'version.assessment',
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

            // در پایان عادی، پاسخ سؤال‌های اجباری الزامی است.
            // در پایان ناشی از اتمام زمان، پاسخ‌های موجود محاسبه می‌شوند.
            if (! $timedOut) {
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
            }

            $slug = strtolower($attempt->version?->assessment?->slug ?? 'disc');
            $strategy = $this->resolveStrategy($slug);

            $scoringResult = $strategy->calculate(
                attempt: $attempt,
                questions: $questions,
                answers: $answers,
                timedOut: $timedOut
            );

            $result = AssessmentResult::updateOrCreate(
                [
                    'assessment_attempt_id' => $attempt->id,
                ],
                [
                    'scores' => $scoringResult['scores'],
                    'primary_type' => $scoringResult['primary_type'],
                    'report' => $scoringResult['report'],
                    'calculated_at' => now(),
                ]
            );

            $attempt->status = $timedOut
                ? 'timed_out'
                : 'completed';

            $attempt->completed_at = now();
            $attempt->save();

            return $result;
        });
    }

    /**
     * انتخاب استراتژی محاسبه امتیاز بر اساس اسلاگ آزمون.
     */
    protected function resolveStrategy(string $slug): AssessmentScoringStrategyInterface
    {
        return match ($slug) {
            'mbti' => $this->mbtiStrategy,
            default => $this->discStrategy,
        };
    }
}
