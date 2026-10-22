<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\User;
use App\Services\CalculateAssessmentResultService;
use Database\Seeders\MbtiAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MbtiAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * بررسی ایجاد آزمون MBTI و نسخه ۱.۰ منتشرشده.
     */
    public function test_mbti_seeder_creates_published_assessment_and_version(): void
    {
        $this->seed(MbtiAssessmentSeeder::class);

        $assessment = Assessment::where('slug', 'mbti')->first();

        $this->assertNotNull($assessment);
        $this->assertTrue($assessment->is_active);
        $this->assertSame('MBTI', $assessment->name);

        $version = $assessment->versions()->where('version', '1.0')->first();

        $this->assertNotNull($version);
        $this->assertNotNull($version->published_at);
        $this->assertSame(20, $version->time_limit_minutes);
        $this->assertSame('Open Extended Jungian Type Scales (OEJTS 1.2)', $version->scoring_config['source'] ?? null);

        $questions = $version->questions()->with('options')->get();
        $this->assertCount(32, $questions);

        $dichotomyCounts = [
            'EI' => 0,
            'SN' => 0,
            'TF' => 0,
            'JP' => 0,
        ];

        foreach ($questions as $question) {
            $this->assertSame('single_choice', $question->type);
            $this->assertTrue($question->is_required);
            $this->assertNotEmpty($question->prompt);

            $dichotomy = $question->metadata['dichotomy'] ?? null;
            $this->assertArrayHasKey($dichotomy, $dichotomyCounts);
            $dichotomyCounts[$dichotomy]++;

            $options = $question->options;
            $this->assertCount(5, $options);
        }

        $this->assertSame(8, $dichotomyCounts['EI']);
        $this->assertSame(8, $dichotomyCounts['SN']);
        $this->assertSame(8, $dichotomyCounts['TF']);
        $this->assertSame(8, $dichotomyCounts['JP']);
    }

    /**
     * بررسی Idempotent بودن سیدر در اجرای مکرر.
     */
    public function test_mbti_seeder_is_idempotent_on_repeated_runs(): void
    {
        $this->seed(MbtiAssessmentSeeder::class);
        $this->seed(MbtiAssessmentSeeder::class);
        $this->seed(MbtiAssessmentSeeder::class);

        $version = AssessmentVersion::where('version', '1.0')
            ->whereHas('assessment', fn ($q) => $q->where('slug', 'mbti'))
            ->firstOrFail();

        $this->assertSame(32, $version->questions()->count());
        $this->assertSame(160, Question::where('assessment_version_id', $version->id)->withCount('options')->get()->sum('options_count'));
    }

    /**
     * بررسی صحت محاسبات و تولید تیپ INTJ با گزارش تفصیلی.
     */
    public function test_mbti_scoring_accurately_determines_intj_profile(): void
    {
        $this->seed(MbtiAssessmentSeeder::class);

        $user = User::factory()->create();
        $version = AssessmentVersion::whereHas('assessment', fn ($q) => $q->where('slug', 'mbti'))->firstOrFail();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        // پاسخ‌دهی به تمام ۳۲ سؤال با اولویت گزینه‌های معطوف به I, N, T, J
        $targetPoles = ['I', 'N', 'T', 'J'];

        foreach ($version->questions as $question) {
            $leftPole = $question->metadata['left_pole'];
            $rightPole = $question->metadata['right_pole'];

            // انتخاب گزینه‌ای که به نفع قطب مد نظر INTJ باشد
            if (in_array($leftPole, $targetPoles, true)) {
                $chosenKey = 'likert_1'; // ۲ امتیاز به قطب چپ
            } elseif (in_array($rightPole, $targetPoles, true)) {
                $chosenKey = 'likert_5'; // ۲ امتیاز به قطب راست
            } else {
                $chosenKey = 'likert_3';
            }

            $option = $question->options()->where('option_key', $chosenKey)->firstOrFail();

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt);

        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertSame('INTJ', $result->primary_type);

        $scores = $result->scores;
        $this->assertSame('I', $scores['dichotomies']['EI']['preference']);
        $this->assertSame('N', $scores['dichotomies']['SN']['preference']);
        $this->assertSame('T', $scores['dichotomies']['TF']['preference']);
        $this->assertSame('J', $scores['dichotomies']['JP']['preference']);

        // بررسی گزارش تفصیلی
        $report = $result->report;
        $this->assertIsArray($report);
        $this->assertSame('INTJ', $report['primary_type']);
        $this->assertStringContainsString('معمار و استراتژیست', $report['type_title']);
        $this->assertNotEmpty($report['traits']);
        $this->assertNotEmpty($report['strengths']);
        $this->assertNotEmpty($report['growth_areas']);
        $this->assertNotEmpty($report['work_environment']);
        $this->assertArrayHasKey('EI', $report['dichotomies']);
    }

    /**
     * بررسی چرخه کامل آزمون MBTI از طریق API.
     */
    public function test_mbti_complete_api_lifecycle(): void
    {
        $this->seed(MbtiAssessmentSeeder::class);

        $user = User::factory()->create();

        // ۱. شروع نوبت آزمون
        $startResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/assessments/mbti/attempts');

        $startResponse->assertStatus(201);
        $attemptId = $startResponse->json('data.attempt_id');
        $this->assertNotNull($attemptId);

        $questions = $startResponse->json('data.questions');
        $this->assertCount(32, $questions);

        // ۲. ارسال پاسخ‌ها
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
        $answersResponse->assertJson(['saved_count' => 32]);

        // ۳. اتمام آزمون و محاسبه
        $completeResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/attempts/{$attemptId}/complete");

        $completeResponse->assertStatus(200);
        $primaryType = $completeResponse->json('data.result.primary_type');
        $this->assertNotNull($primaryType);
        $this->assertSame(4, strlen($primaryType));

        // ۴. دریافت نتیجه آزمون
        $resultResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/attempts/{$attemptId}/result");

        $resultResponse->assertStatus(200);
        $this->assertSame($primaryType, $resultResponse->json('data.result.primary_type'));
        $this->assertNotNull($resultResponse->json('data.result.report'));
    }
}
