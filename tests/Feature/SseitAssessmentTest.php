<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\User;
use App\Services\CalculateAssessmentResultService;
use Database\Seeders\SseitAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SseitAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * بررسی ایجاد آزمون SSEIT، نسخه ۱.۰ منتشرشده و ۳۳ سؤال استاندارد با گزینه‌ها.
     */
    public function test_sseit_seeder_creates_published_assessment_and_version(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $assessment = Assessment::where('slug', 'sseit')->first();

        $this->assertNotNull($assessment);
        $this->assertTrue($assessment->is_active);
        $this->assertSame('آزمون هوش هیجانی شاته (SSEIT)', $assessment->name);

        $version = $assessment->versions()->where('version', '1.0')->first();

        $this->assertNotNull($version);
        $this->assertNotNull($version->published_at);
        $this->assertSame(20, $version->time_limit_minutes);
        $this->assertSame(33, $version->scoring_config['questions_count'] ?? null);

        $questions = $version->questions()->with('options')->get();
        $this->assertCount(33, $questions);

        $subscaleCounts = [
            'perception' => 0,
            'managing_self' => 0,
            'managing_others' => 0,
            'utilization' => 0,
        ];

        $reversedItemNumbers = [5, 28, 33];

        foreach ($questions as $question) {
            $this->assertSame('single_choice', $question->type);
            $this->assertTrue($question->is_required);
            $this->assertNotEmpty($question->prompt);

            $subscale = $question->metadata['subscale'] ?? null;
            $this->assertArrayHasKey($subscale, $subscaleCounts);
            $subscaleCounts[$subscale]++;

            $itemNum = $question->metadata['item_number'] ?? null;
            $isReversed = $question->metadata['is_reversed'] ?? false;

            if (in_array($itemNum, $reversedItemNumbers, true)) {
                $this->assertTrue($isReversed, "سؤال شماره {$itemNum} باید معکوس نمره‌گذاری شود.");

                $opt1 = $question->options->where('option_key', 'likert_1')->first();
                $opt5 = $question->options->where('option_key', 'likert_5')->first();
                $this->assertSame(5, $opt1->score_values['points']);
                $this->assertSame(1, $opt5->score_values['points']);
            } else {
                $this->assertFalse($isReversed, "سؤال شماره {$itemNum} نباید معکوس نمره‌گذاری شود.");

                $opt1 = $question->options->where('option_key', 'likert_1')->first();
                $opt5 = $question->options->where('option_key', 'likert_5')->first();
                $this->assertSame(1, $opt1->score_values['points']);
                $this->assertSame(5, $opt5->score_values['points']);
            }

            $options = $question->options;
            $this->assertCount(5, $options);
        }

        $this->assertSame(10, $subscaleCounts['perception']);
        $this->assertSame(9, $subscaleCounts['managing_self']);
        $this->assertSame(8, $subscaleCounts['managing_others']);
        $this->assertSame(6, $subscaleCounts['utilization']);
    }

    /**
     * بررسی Idempotent بودن سیدر در اجرای مکرر بدون ایجاد دیتای تکراری.
     */
    public function test_sseit_seeder_is_idempotent_on_repeated_runs(): void
    {
        $this->seed(SseitAssessmentSeeder::class);
        $this->seed(SseitAssessmentSeeder::class);
        $this->seed(SseitAssessmentSeeder::class);

        $version = AssessmentVersion::where('version', '1.0')
            ->whereHas('assessment', fn ($q) => $q->where('slug', 'sseit'))
            ->firstOrFail();

        $this->assertSame(33, $version->questions()->count());
        $this->assertSame(165, Question::where('assessment_version_id', $version->id)->withCount('options')->get()->sum('options_count'));
    }

    /**
     * بررسی صحت نمره‌گذاری و تولید گزارش با بالاترین نمره ممکن (۱۶۵ امتیاز - Very High).
     */
    public function test_sseit_scoring_calculates_very_high_profile_accurately(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $user = User::factory()->create();
        $version = AssessmentVersion::whereHas('assessment', fn ($q) => $q->where('slug', 'sseit'))->firstOrFail();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        // پاسخ با گزینه‌ای که بالاترین امتیاز (۵) را به همراه دارد:
        // برای سوالات عادی likert_5 (امتیاز ۵)، برای سوالات معکوس likert_1 (امتیاز ۵)
        foreach ($version->questions as $question) {
            $isReversed = $question->metadata['is_reversed'] ?? false;
            $targetKey = $isReversed ? 'likert_1' : 'likert_5';

            $option = $question->options()->where('option_key', $targetKey)->firstOrFail();

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt);

        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertSame('VERY_HIGH_EI', $result->primary_type);

        $scores = $result->scores;
        $this->assertSame(165, $scores['total']);
        $this->assertSame(100, $scores['percentage']);
        $this->assertSame(50, $scores['perception']);
        $this->assertSame(45, $scores['managing_self']);
        $this->assertSame(40, $scores['managing_others']);
        $this->assertSame(30, $scores['utilization']);

        $report = $result->report;
        $this->assertIsArray($report);
        $this->assertSame('VERY_HIGH_EI', $report['primary_type']);
        $this->assertStringContainsString('بسیار بالا', $report['type_title']);
        $this->assertNotEmpty($report['traits']);
        $this->assertNotEmpty($report['strengths']);
        $this->assertNotEmpty($report['growth_areas']);
        $this->assertNotEmpty($report['work_environment']);
        $this->assertArrayHasKey('perception', $report['subscales']);
        $this->assertSame(50, $report['subscales']['perception']['score']);
    }

    /**
     * بررسی محاسبه پایین‌ترین نمره ممکن (۳۳ امتیاز - Developing EI).
     */
    public function test_sseit_scoring_calculates_developing_profile_on_lowest_answers(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $user = User::factory()->create();
        $version = AssessmentVersion::whereHas('assessment', fn ($q) => $q->where('slug', 'sseit'))->firstOrFail();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        // پاسخ با گزینه‌ای که کمترین امتیاز (۱) را به همراه دارد:
        // برای سوالات عادی likert_1 (امتیاز ۱)، برای سوالات معکوس likert_5 (امتیاز ۱)
        foreach ($version->questions as $question) {
            $isReversed = $question->metadata['is_reversed'] ?? false;
            $targetKey = $isReversed ? 'likert_5' : 'likert_1';

            $option = $question->options()->where('option_key', $targetKey)->firstOrFail();

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt);

        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertSame('DEVELOPING_EI', $result->primary_type);

        $scores = $result->scores;
        $this->assertSame(33, $scores['total']);
        $this->assertSame(20, $scores['percentage']); // 33 / 165 = 20%
        $this->assertSame(10, $scores['perception']);
        $this->assertSame(9, $scores['managing_self']);
        $this->assertSame(8, $scores['managing_others']);
        $this->assertSame(6, $scores['utilization']);

        $report = $result->report;
        $this->assertStringContainsString('نیازمند بهبود', $report['type_title']);
    }

    /**
     * بررسی سطوح هوش هیجانی متوسط و بالا.
     */
    public function test_sseit_scoring_determines_high_and_moderate_levels(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $user = User::factory()->create();
        $version = AssessmentVersion::whereHas('assessment', fn ($q) => $q->where('slug', 'sseit'))->firstOrFail();

        // تست انتخاب گزینه خنثی (likert_3) برای تمام سوالات: ۳۳ * ۳ = ۹۹ نمره => DEVELOPING_EI (کمتر از ۱۰۵)
        $attemptNeutral = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        foreach ($version->questions as $question) {
            $option = $question->options()->where('option_key', 'likert_3')->firstOrFail();
            $attemptNeutral->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $resultNeutral = $service->calculate($attemptNeutral);
        $this->assertSame(99, $resultNeutral->scores['total']);
        $this->assertSame('DEVELOPING_EI', $resultNeutral->primary_type);

        // تست انتخاب گزینه موافقم (likert_4 برای عادی، likert_2 برای معکوس) => ۳۳ * ۴ = ۱۳۲ نمره => HIGH_EI (بین ۱۲۴ تا ۱۳۸)
        $attemptHigh = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        foreach ($version->questions as $question) {
            $isReversed = $question->metadata['is_reversed'] ?? false;
            $targetKey = $isReversed ? 'likert_2' : 'likert_4';
            $option = $question->options()->where('option_key', $targetKey)->firstOrFail();

            $attemptHigh->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $resultHigh = $service->calculate($attemptHigh);
        $this->assertSame(132, $resultHigh->scores['total']);
        $this->assertSame('HIGH_EI', $resultHigh->primary_type);
        $this->assertSame(80, $resultHigh->scores['percentage']);
    }

    /**
     * بررسی محاسبه آزمون در صورت اتمام زمان (Timed Out) با پاسخ‌های ناقص.
     */
    public function test_sseit_timed_out_calculation(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $user = User::factory()->create();
        $version = AssessmentVersion::whereHas('assessment', fn ($q) => $q->where('slug', 'sseit'))->firstOrFail();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(25),
            'expires_at' => now()->subMinutes(5),
        ]);

        // پاسخ تنها به ۵ سوال
        foreach ($version->questions->take(5) as $question) {
            $option = $question->options->first();
            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt, timedOut: true);

        $this->assertSame('timed_out', $attempt->fresh()->status);
        $this->assertTrue($result->report['is_incomplete']);
        $this->assertSame(5, $result->report['answered_count']);
        $this->assertSame(33, $result->report['total_questions']);
    }

    /**
     * بررسی چرخه کامل وب‌سرویس (API Lifecycle) برای آزمون SSEIT.
     */
    public function test_sseit_complete_api_lifecycle(): void
    {
        $this->seed(SseitAssessmentSeeder::class);

        $user = User::factory()->create();

        // ۱. استعلام لیست آزمون‌ها و وجود sseit
        $listResponse = $this->actingAs($user, 'sanctum')->getJson('/api/assessments');
        $listResponse->assertStatus(200);
        $slugs = collect($listResponse->json('data'))->pluck('slug')->all();
        $this->assertContains('sseit', $slugs);

        // ۲. شروع آزمون
        $startResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/assessments/sseit/attempts');

        $startResponse->assertStatus(201);
        $attemptId = $startResponse->json('data.attempt_id');
        $this->assertNotNull($attemptId);

        $questions = $startResponse->json('data.questions');
        $this->assertCount(33, $questions);

        // ۳. ارسال پاسخ‌ها
        $answersPayload = [];
        $attempt = AssessmentAttempt::with('version.questions.options')->findOrFail($attemptId);

        foreach ($attempt->version->questions as $question) {
            $option = $question->options->first();
            $answersPayload[] = [
                'question_id' => $question->id,
                'option_id' => $option->id,
            ];
        }

        $answersResponse = $this->actingAs($user, 'sanctum')
            ->putJson("/api/attempts/{$attemptId}/answers", [
                'answers' => $answersPayload,
            ]);

        $answersResponse->assertStatus(200);
        $answersResponse->assertJson(['saved_count' => 33]);

        // ۴. اتمام آزمون و محاسبه نتیجه
        $completeResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/attempts/{$attemptId}/complete");

        $completeResponse->assertStatus(200);
        $primaryType = $completeResponse->json('data.result.primary_type');
        $this->assertNotNull($primaryType);

        // ۵. استعلام کارنامه و نتیجه نهایی
        $resultResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/attempts/{$attemptId}/result");

        $resultResponse->assertStatus(200);
        $this->assertSame($primaryType, $resultResponse->json('data.result.primary_type'));
        $this->assertNotNull($resultResponse->json('data.result.report'));
        $this->assertArrayHasKey('subscales', $resultResponse->json('data.result.report'));
    }
}
