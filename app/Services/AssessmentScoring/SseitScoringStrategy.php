<?php

namespace App\Services\AssessmentScoring;

use App\Models\AssessmentAttempt;
use App\Services\SseitReportService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SseitScoringStrategy implements AssessmentScoringStrategyInterface
{
    /**
     * شماره سوالاتی که نمره‌گذاری معکوس دارند.
     *
     * @var array<int, int>
     */
    protected const REVERSE_SCORED_ITEMS = [5, 28, 33];

    public function __construct(
        protected SseitReportService $reportService
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
        $subscaleScores = [
            'perception' => 0,
            'managing_self' => 0,
            'managing_others' => 0,
            'utilization' => 0,
        ];

        $totalScore = 0;
        $questionsByKey = $questions->keyBy('id');

        foreach ($answers as $answer) {
            $option = $answer->selectedOption;

            if (! $option) {
                throw ValidationException::withMessages([
                    'answers' => 'یکی از پاسخ‌های ثبت‌شده معتبر نیست.',
                ]);
            }

            $question = $questionsByKey->get($answer->question_id);
            $values = $option->score_values ?? [];

            // استخراج امتیاز سوال؛ در صورت وجود مقدار از پیش محاسبه‌شده در گزینه از آن استفاده می‌شود،
            // در غیر این صورت بر مبنای مقدار لیکرت و معکوس بودن سوال محاسبه صورت می‌پذیرد.
            if (isset($values['points']) && is_numeric($values['points'])) {
                $itemPoints = (int) $values['points'];
            } else {
                $rawVal = (int) ($values['raw'] ?? 3);
                $isReversed = (bool) ($question?->metadata['is_reversed'] ?? false);
                $itemPoints = $isReversed ? (6 - $rawVal) : $rawVal;
            }

            $subscale = $question?->metadata['subscale'] ?? ($values['subscale'] ?? 'perception');

            if (! array_key_exists($subscale, $subscaleScores)) {
                $subscale = 'perception';
            }

            $subscaleScores[$subscale] += $itemPoints;
            $totalScore += $itemPoints;
        }

        // دسته‌بندی سطح هوش هیجانی کل بر اساس نمرات هنجار استاندارد شاته:
        // نمره کل بین ۳۳ تا ۱۶۵ متغیر است.
        $primaryType = match (true) {
            $totalScore >= 139 => 'VERY_HIGH_EI',
            $totalScore >= 124 => 'HIGH_EI',
            $totalScore >= 105 => 'MODERATE_EI',
            default => 'DEVELOPING_EI',
        };

        $report = $this->reportService->generate(
            totalScore: $totalScore,
            subscaleScores: $subscaleScores,
            primaryType: $primaryType,
            isTimedOut: $timedOut,
            totalQuestions: $questions->count(),
            answeredCount: $answers->count()
        );

        $finalScores = array_merge($subscaleScores, [
            'total' => $totalScore,
            'max_score' => 165,
            'percentage' => (int) round(($totalScore / 165) * 100),
            'subscales' => $subscaleScores,
        ]);

        return [
            'scores' => $finalScores,
            'primary_type' => $primaryType,
            'report' => $report,
        ];
    }
}
