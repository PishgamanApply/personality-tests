<?php

namespace App\Services\AssessmentScoring;

use App\Models\AssessmentAttempt;
use App\Services\MbtiReportService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MbtiScoringStrategy implements AssessmentScoringStrategyInterface
{
    public function __construct(
        protected MbtiReportService $reportService
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
        $poles = [
            'E' => 0,
            'I' => 0,
            'S' => 0,
            'N' => 0,
            'T' => 0,
            'F' => 0,
            'J' => 0,
            'P' => 0,
        ];

        foreach ($answers as $answer) {
            $option = $answer->selectedOption;

            if (! $option) {
                throw ValidationException::withMessages([
                    'answers' => 'یکی از پاسخ‌های ثبت‌شده معتبر نیست.',
                ]);
            }

            $values = $option->score_values ?? [];

            foreach ($poles as $pole => $curr) {
                if (isset($values[$pole]) && is_numeric($values[$pole])) {
                    $poles[$pole] += (int) $values[$pole];
                }
            }
        }

        // تحلیل ۴ محور دوقطبی MBTI
        $dichotomies = [
            'EI' => $this->calculatePair($poles['E'], $poles['I'], 'E', 'I'),
            'SN' => $this->calculatePair($poles['S'], $poles['N'], 'S', 'N'),
            'TF' => $this->calculatePair($poles['T'], $poles['F'], 'T', 'F'),
            'JP' => $this->calculatePair($poles['J'], $poles['P'], 'J', 'P'),
        ];

        $primaryType = $dichotomies['EI']['preference']
            .$dichotomies['SN']['preference']
            .$dichotomies['TF']['preference']
            .$dichotomies['JP']['preference'];

        $report = $this->reportService->generate(
            scores: $poles,
            primaryType: $primaryType,
            dichotomies: $dichotomies,
            isTimedOut: $timedOut,
            totalQuestions: $questions->count(),
            answeredCount: $answers->count()
        );

        $finalScores = array_merge($poles, [
            'dichotomies' => $dichotomies,
        ]);

        return [
            'scores' => $finalScores,
            'primary_type' => $primaryType,
            'report' => $report,
        ];
    }

    /**
     * محاسبه جهت‌گیری و درصد شفافیت برای یک جفت دوقطبی.
     *
     * @return array{preference: string, percentage: int, left_score: int, right_score: int}
     */
    protected function calculatePair(int $leftScore, int $rightScore, string $leftCode, string $rightCode): array
    {
        $total = $leftScore + $rightScore;

        if ($total === 0) {
            return [
                'preference' => $leftCode,
                'percentage' => 50,
                'left_score' => 0,
                'right_score' => 0,
            ];
        }

        $pref = ($leftScore >= $rightScore) ? $leftCode : $rightCode;
        $maxScore = max($leftScore, $rightScore);
        $percentage = (int) round(($maxScore / $total) * 100);

        return [
            'preference' => $pref,
            'percentage' => $percentage,
            'left_score' => $leftScore,
            'right_score' => $rightScore,
        ];
    }
}

