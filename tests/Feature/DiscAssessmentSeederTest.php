<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\DiscAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscAssessmentSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * بررسی ایجاد آزمون DISC و نسخه ۱.۰ منتشرشده همراه با متادیتای ODAT.
     */
    public function test_seeder_creates_disc_assessment_and_published_version_1_0(): void
    {
        $this->seed(DiscAssessmentSeeder::class);

        $assessment = Assessment::where('slug', 'disc')->first();

        $this->assertNotNull($assessment);
        $this->assertTrue($assessment->is_active);
        $this->assertSame('DISC', $assessment->name);

        $version = $assessment->versions()->where('version', '1.0')->first();

        $this->assertNotNull($version);
        $this->assertNotNull($version->published_at);
        $this->assertSame(15, $version->time_limit_minutes);
        $this->assertSame('Open Source Psychometrics Project (ODAT)', $version->scoring_config['source'] ?? null);
        $this->assertSame('Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)', $version->scoring_config['license'] ?? null);
    }

    /**
     * بررسی ایجاد دقیقاً ۱۶ سؤال ODAT با تفکیک ۴ سؤال برای هر بُعد.
     */
    public function test_seeder_populates_16_odat_questions_with_4_items_per_dimension(): void
    {
        $this->seed(DiscAssessmentSeeder::class);

        $version = AssessmentVersion::where('version', '1.0')->firstOrFail();
        $questions = $version->questions()->get();

        $this->assertCount(16, $questions);

        $dimensionCounts = [
            'D' => 0,
            'I' => 0,
            'S' => 0,
            'C' => 0,
        ];

        foreach ($questions as $question) {
            $this->assertSame('single_choice', $question->type);
            $this->assertTrue($question->is_required);
            $this->assertNotEmpty($question->prompt);

            $dim = $question->metadata['dimension'] ?? null;
            $this->assertContains($dim, ['D', 'I', 'S', 'C']);
            $dimensionCounts[$dim]++;
        }

        $this->assertSame(4, $dimensionCounts['D']);
        $this->assertSame(4, $dimensionCounts['I']);
        $this->assertSame(4, $dimensionCounts['S']);
        $this->assertSame(4, $dimensionCounts['C']);
    }

    /**
     * بررسی گزینه‌های پنج‌درجه‌ای لیکرت و مقادیر امتیازدهی هر گزینه.
     */
    public function test_each_question_has_5_likert_options_with_accurate_scoring(): void
    {
        $this->seed(DiscAssessmentSeeder::class);

        $version = AssessmentVersion::where('version', '1.0')->firstOrFail();
        $questions = $version->questions()->with('options')->get();

        foreach ($questions as $question) {
            $options = $question->options->sortBy('sort_order')->values();
            $this->assertCount(5, $options);

            $targetDim = $question->metadata['dimension'];

            foreach ($options as $index => $option) {
                $expectedValue = $index + 1;
                $this->assertSame("likert_{$expectedValue}", $option->option_key);
                $this->assertSame($expectedValue, $option->score_values[$targetDim]);

                foreach (['D', 'I', 'S', 'C'] as $dim) {
                    if ($dim !== $targetDim) {
                        $this->assertSame(0, $option->score_values[$dim]);
                    }
                }
            }
        }
    }

    /**
     * بررسی Idempotent بودن کامل سیدر در اجرای مکرر.
     */
    public function test_seeder_is_completely_idempotent_on_repeated_runs(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $this->seed(DiscAssessmentSeeder::class);
        $this->seed(DiscAssessmentSeeder::class);

        $version = AssessmentVersion::where('version', '1.0')->firstOrFail();

        $this->assertSame(16, $version->questions()->count());
        $this->assertSame(80, Question::where('assessment_version_id', $version->id)->withCount('options')->get()->sum('options_count'));
    }

    /**
     * بررسی چرخه کامل آزمون از طریق API با سؤالات تولیدشده توسط سیدر.
     */
    public function test_complete_api_assessment_flow_using_seeded_odat_version(): void
    {
        $this->seed(DiscAssessmentSeeder::class);

        $user = User::factory()->create();

        // ۱. شروع آزمون
        $startResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/assessments/disc/attempts');

        $startResponse->assertStatus(201);
        $attemptId = $startResponse->json('data.attempt_id');
        $this->assertNotNull($attemptId);

        $questions = $startResponse->json('data.questions');
        $this->assertCount(16, $questions);

        // ۲. ثبت پاسخ‌ها: امتیاز ۵ برای سؤالات D و امتیاز ۱ برای سایر سؤالات
        $answersPayload = [];
        $attempt = AssessmentAttempt::with('version.questions.options')->findOrFail($attemptId);

        foreach ($attempt->version->questions as $question) {
            $dim = $question->metadata['dimension'];
            $selectedKey = $dim === 'D' ? 'likert_5' : 'likert_1';

            $option = $question->options->firstWhere('option_key', $selectedKey);
            $this->assertNotNull($option);

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
        $answersResponse->assertJson(['saved_count' => 16]);

        // ۳. پایان آزمون و محاسبه نتیجه
        $completeResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/attempts/{$attemptId}/complete");

        $completeResponse->assertStatus(200);
        $completeResponse->assertJson([
            'data' => [
                'status' => 'completed',
                'result' => [
                    'primary_type' => 'D',
                    'scores' => [
                        'D' => 20,
                        'I' => 4,
                        'S' => 4,
                        'C' => 4,
                    ],
                ],
            ],
        ]);

        $result = AssessmentResult::where('assessment_attempt_id', $attemptId)->first();
        $this->assertNotNull($result);
        $this->assertSame('D', $result->primary_type);
        $this->assertSame(20, $result->scores['D']);
    }
}
