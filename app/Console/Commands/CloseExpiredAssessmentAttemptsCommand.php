<?php

namespace App\Console\Commands;

use App\Models\AssessmentAttempt;
use App\Services\CalculateAssessmentResultService;
use Illuminate\Console\Command;

class CloseExpiredAssessmentAttemptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessments:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'بستن خودکار آزمون‌های در جریان که مهلت زمانی آنها به پایان رسیده است و محاسبه نتایج آنها';

    /**
     * Execute the console command.
     */
    public function handle(CalculateAssessmentResultService $service): int
    {
        $expiredAttempts = AssessmentAttempt::query()
            ->where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredAttempts as $attempt) {
            try {
                $service->calculate($attempt, timedOut: true);
                $count++;
            } catch (\Throwable $e) {
                $this->error("خطا در بستن آزمون شماره {$attempt->id}: {$e->getMessage()}");
            }
        }

        $this->info("تعداد {$count} آزمون منقضی‌شده با موفقیت بسته و امتیازدهی شدند.");

        return Command::SUCCESS;
    }
}

