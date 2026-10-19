<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateAssessmentResultService
{
    public function __construct(
        protected DiscReportService $reportService
    ) {}

    public function calculate(
        AssessmentAttempt $attempt,
        bool $timedOut = false
    ): AssessmentResult {
        return DB::transaction(function () use ($attempt, $timedOut) {
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

            // مدیریت تساوی‌ها:
            // اگر هیچ پاسخی ثبت نشده باشد یا همه ابعاد دقیقاً برابر باشند، null در نظر گرفته می‌شود.
            // در تساوی بین ۲ یا ۳ بعد، ترکیب آنها به عنوان تیپ غالب درج می‌گردد (مثلاً D/I).
            if ($maxScore === 0 || count($topDimensions) === 4) {
                $primaryType = null;
            } elseif (count($topDimensions) === 1) {
                $primaryType = $topDimensions[0];
            } else {
                $primaryType = implode('/', $topDimensions);
            }

            $report = $this->reportService->generate(
                scores: $scores,
                primaryType: $primaryType,
                topDimensions: $topDimensions,
                isTimedOut: $timedOut,
                totalQuestions: $questions->count(),
                answeredCount: $answers->count()
            );

            $result = AssessmentResult::updateOrCreate(
                [
                    'assessment_attempt_id' => $attempt->id,
                ],
                [
                    'scores' => $scores,
                    'primary_type' => $primaryType,
                    'report' => $report,
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
}
