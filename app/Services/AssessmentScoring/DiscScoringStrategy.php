<?php

namespace App\Services\AssessmentScoring;

use App\Models\AssessmentAttempt;
use App\Services\DiscReportService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiscScoringStrategy implements AssessmentScoringStrategyInterface
{
    public function __construct(
        protected DiscReportService $reportService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculate(
        AssessmentAttempt $attempt,
        Collection $questions,
        Collection $answers,
        bool $timedOut
    ): array {
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

                $scores[$dimension] += (int) $value;
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

        return [
            'scores' => $scores,
            'primary_type' => $primaryType,
            'report' => $report,
        ];
    }
}
