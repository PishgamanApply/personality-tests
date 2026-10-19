<?php

namespace Tests\Feature;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentVersion;
use App\Models\User;
use App\Services\CalculateAssessmentResultService;
use Database\Seeders\DiscAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentScoringAndManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DiscAssessmentSeeder::class);
    }

    public function test_scoring_accurately_calculates_scores_for_single_dominant_type(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        // انتخاب گزینه‌هایی که به نفع D باشد
        foreach ($version->questions as $question) {
            $isTop = ($question->metadata['dimension'] ?? null) === 'D';
            $optionKey = $isTop ? 'likert_5' : 'likert_1';

            $option = $question->options()->where('option_key', $optionKey)->first();

            AssessmentAnswer::create([
                'assessment_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt);

        $attempt->refresh();

        $this->assertSame('completed', $attempt->status);
        $this->assertNotNull($attempt->completed_at);
        $this->assertSame('D', $result->primary_type);
        $this->assertSame(20, $result->scores['D']);
        $this->assertSame(4, $result->scores['I']);
        $this->assertSame(4, $result->scores['S']);
        $this->assertSame(4, $result->scores['C']);

        // بررسی ساختار گزارش
        $report = $result->report;
        $this->assertIsArray($report);
        $this->assertSame('D', $report['primary_type']);
        $this->assertStringContainsString('تیپ D', $report['type_title']);
        $this->assertNotEmpty($report['traits']);
        $this->assertNotEmpty($report['strengths']);
        $this->assertNotEmpty($report['growth_areas']);
        $this->assertNotEmpty($report['limitations']);
        $this->assertFalse($report['is_incomplete']);
    }

    public function test_scoring_handles_ties_between_top_dimensions_as_compound_style(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
        ]);

        // انتخاب گزینه ۵ هم برای D و هم برای I
        foreach ($version->questions as $question) {
            $isTop = in_array($question->metadata['dimension'] ?? null, ['D', 'I'], true);

            $optionKey = $isTop ? 'likert_5' : 'likert_1';
            $option = $question->options()->where('option_key', $optionKey)->first();

            AssessmentAnswer::create([
                'assessment_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt);

        $this->assertSame('D/I', $result->primary_type);
        $this->assertSame(20, $result->scores['D']);
        $this->assertSame(20, $result->scores['I']);
        $this->assertSame(4, $result->scores['S']);
        $this->assertSame(4, $result->scores['C']);

        $report = $result->report;
        $this->assertSame('D/I', $report['primary_type']);
        $this->assertStringContainsString('تیپ ترکیبی', $report['type_title']);
        $this->assertStringContainsString('برابر است', $report['summary']);
    }

    public function test_incomplete_assessment_on_timeout_calculates_scores_and_flags_incomplete(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(10), // منقضی شده
        ]);

        // فقط به ۴ سوال اول پاسخ داده شده (سوالات D)
        $questions = $version->questions()->take(4)->get();
        foreach ($questions as $question) {
            $option = $question->options()->where('option_key', 'likert_5')->first();
            AssessmentAnswer::create([
                'assessment_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);
        }

        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt, timedOut: true);

        $attempt->refresh();
        $this->assertSame('timed_out', $attempt->status);
        $this->assertNotNull($attempt->completed_at);
        $this->assertSame(20, $result->scores['D']);
        $this->assertSame(0, $result->scores['I']);
        $this->assertSame(0, $result->scores['S']);
        $this->assertSame(0, $result->scores['C']);

        $report = $result->report;
        $this->assertTrue($report['is_incomplete']);
        $this->assertSame(4, $report['answered_count']);
        $this->assertSame(16, $report['total_questions']);
        $this->assertStringContainsString('اتمام زمان', $report['limitations'][0]);
    }

    public function test_zero_answers_on_timeout_produces_zero_scores_and_null_primary_type(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(10),
        ]);

        // هیچ پاسخی ثبت نشده است
        $service = app(CalculateAssessmentResultService::class);
        $result = $service->calculate($attempt, timedOut: true);

        $attempt->refresh();
        $this->assertSame('timed_out', $attempt->status);
        $this->assertNull($result->primary_type);
        $this->assertSame(0, $result->scores['D']);
        $this->assertSame(0, $result->scores['I']);
        $this->assertSame(0, $result->scores['S']);
        $this->assertSame(0, $result->scores['C']);
        $this->assertTrue($result->report['is_incomplete']);
    }

    public function test_close_expired_command_closes_all_timed_out_attempts(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        // تلاشی که منقضی شده است
        $expiredAttempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(5),
        ]);

        // تلاشی که هنوز منقضی نشده است
        $activeAttempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->artisan('assessments:close-expired')
            ->expectsOutputToContain('تعداد 1 آزمون منقضی‌شده با موفقیت بسته و امتیازدهی شدند.')
            ->assertSuccessful();

        $expiredAttempt->refresh();
        $activeAttempt->refresh();

        $this->assertSame('timed_out', $expiredAttempt->status);
        $this->assertNotNull($expiredAttempt->result);

        $this->assertSame('in_progress', $activeAttempt->status);
        $this->assertNull($activeAttempt->result);
    }

    public function test_answers_cannot_be_modified_or_added_after_completion(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(5),
        ]);

        $question = $version->questions()->first();
        $option = $question->options()->first();

        $response = $this->actingAs($user)->putJson("/api/attempts/{$attempt->id}/answers", [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attempt']);
    }

    public function test_answers_cannot_be_modified_when_attempt_has_timed_out(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(5), // منقضی شده
        ]);

        $question = $version->questions()->first();
        $option = $question->options()->first();

        $response = $this->actingAs($user)->putJson("/api/attempts/{$attempt->id}/answers", [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                ],
            ],
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'مهلت آزمون به پایان رسیده است.',
            'status' => 'timed_out',
        ]);

        $attempt->refresh();
        $this->assertSame('timed_out', $attempt->status);
    }

    public function test_show_result_api_auto_closes_expired_attempt_and_returns_result(): void
    {
        $user = User::factory()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(5), // منقضی شده ولی هنوز باز است
        ]);

        $response = $this->actingAs($user)->getJson("/api/attempts/{$attempt->id}/result");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attempt_id',
                'status',
                'assessment',
                'result' => [
                    'scores',
                    'primary_type',
                    'report' => [
                        'title',
                        'primary_type',
                        'type_title',
                        'summary',
                        'dimensions',
                        'traits',
                        'strengths',
                        'growth_areas',
                        'limitations',
                    ],
                    'calculated_at',
                ],
            ],
        ]);

        $this->assertSame('timed_out', $response->json('data.status'));
    }

    public function test_filament_displays_timed_out_status_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $admin->id,
            'assessment_version_id' => $version->id,
            'status' => 'timed_out',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/panel/assessment-attempts');
        $response->assertOk();
        $response->assertSee('پایان مهلت (منقضی‌شده)');

        $detailResponse = $this->actingAs($admin)->get("/panel/assessment-attempts/{$attempt->id}");
        $detailResponse->assertOk();
        $detailResponse->assertSee('پایان مهلت (منقضی‌شده)');
    }
}
