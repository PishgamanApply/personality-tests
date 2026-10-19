<?php

namespace App\Filament\Widgets;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssessmentsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalAssessments = Assessment::where('is_active', true)->count();
        $totalAttempts = AssessmentAttempt::count();
        $completedAttempts = AssessmentAttempt::where('status', 'completed')->count();
        $timedOutAttempts = AssessmentAttempt::where('status', 'timed_out')->count();
        $totalResults = AssessmentResult::count();
        $totalUsers = User::count();

        // Calculate most common personality type
        $mostCommonType = AssessmentResult::selectRaw('primary_type, count(*) as count')
            ->whereNotNull('primary_type')
            ->groupBy('primary_type')
            ->orderByDesc('count')
            ->first();

        $topTypeDisplay = $mostCommonType ? "تیپ {$mostCommonType->primary_type} ({$mostCommonType->count} نفر)" : '—';

        $attemptsDescription = "{$completedAttempts} تکمیل‌شده";
        if ($timedOutAttempts > 0) {
            $attemptsDescription .= " | {$timedOutAttempts} منقضی‌شده";
        }

        return [
            Stat::make('آزمون‌های فعال', (string) $totalAssessments)
                ->description('تعداد آزمون‌های فعال در سامانه')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('کل آزمون‌های ثبت‌شده', (string) $totalAttempts)
                ->description($attemptsDescription)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('تیپ شخصیتی غالب', $topTypeDisplay)
                ->description('بیشترین فراوانی در نتایج')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('warning'),

            Stat::make('کاربران سامانه', (string) $totalUsers)
                ->description('مجموع کاربران ثبت‌شده')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
