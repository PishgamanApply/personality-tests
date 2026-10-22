<?php

namespace App\Services\AssessmentScoring;

use App\Models\AssessmentAttempt;
use Illuminate\Support\Collection;

interface AssessmentScoringStrategyInterface
{
    /**
     * محاسبه نمرات و گزارش نهایی برای یک نوبت آزمون.
     *
     * @param  AssessmentAttempt  $attempt  نوبت آزمون
     * @param  Collection  $questions  سؤالات نسخه آزمون
     * @param  Collection  $answers  پاسخ‌های ثبت‌شده کاربر (کلیدشده بر اساس question_id)
     * @param  bool  $timedOut  آیا پایان آزمون ناشی از اتمام زمان است؟
     * @return array{scores: array, primary_type: ?string, report: array}
     */
    public function calculate(
        AssessmentAttempt $attempt,
        Collection $questions,
        Collection $answers,
        bool $timedOut
    ): array;
}
