<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StartAssessmentService
{
    public function start(
        Assessment $assessment,
        User $user
    ): AssessmentAttempt {
        if (! $assessment->is_active) {
            throw ValidationException::withMessages([
                'assessment' => 'این آزمون در حال حاضر فعال نیست.',
            ]);
        }

        $version = $assessment->versions()
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->first();

        if (! $version) {
            throw ValidationException::withMessages([
                'assessment' => 'نسخه منتشرشده‌ای برای این آزمون وجود ندارد.',
            ]);
        }

        $hasQuestions = $version->questions()
            ->whereHas('options')
            ->exists();

        if (! $hasQuestions) {
            throw ValidationException::withMessages([
                'assessment' => 'این نسخه هنوز سؤال و گزینه قابل پاسخ ندارد.',
            ]);
        }

        $startedAt = now();

        $timeLimitMinutes = $version->time_limit_minutes ?? 20;

        $expiresAt = $startedAt->copy()
            ->addMinutes($timeLimitMinutes);

        return AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
        ]);
    }
}
