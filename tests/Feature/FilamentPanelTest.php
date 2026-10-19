<?php

namespace Tests\Feature;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentResult;
use App\Models\AssessmentVersion;
use App\Models\User;
use Database\Seeders\DiscAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/panel');

        $response->assertRedirect('/panel/login');
    }

    public function test_non_admin_user_cannot_access_panel(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/panel');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel');

        $response->assertOk();
        $response->assertSee('مدیریت آزمون‌های روان‌شناختی');
        $response->assertSee('fonts/iransansx/font.css');
        $response->assertSee('IRANSansX');
    }

    public function test_admin_can_view_assessments_list(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/assessments');

        $response->assertOk();
        $response->assertSee('DISC');
    }

    public function test_admin_can_view_assessment_versions_list(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/assessment-versions');

        $response->assertOk();
        $response->assertSee('1.0');
    }

    public function test_admin_can_view_questions_list(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/questions');

        $response->assertOk();
    }

    public function test_admin_can_view_attempts_and_results_list(): void
    {
        $admin = User::factory()->admin()->create();

        $attemptsResponse = $this->actingAs($admin)->get('/panel/assessment-attempts');
        $attemptsResponse->assertOk();

        $resultsResponse = $this->actingAs($admin)->get('/panel/assessment-results');
        $resultsResponse->assertOk();
    }

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/users');

        $response->assertOk();
        $response->assertSee($admin->email);
    }

    public function test_admin_can_view_edit_question_page(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/questions/1/edit');

        $response->assertOk();
        $response->assertSee('مشخصات اصلی سؤال');
        $response->assertSee('گزینه‌های سؤال');
    }

    public function test_admin_can_view_edit_assessment_page(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/assessments/1/edit');

        $response->assertOk();
        $response->assertSee('نام آزمون');
    }

    public function test_admin_can_view_attempt_and_result_details(): void
    {
        $this->seed(DiscAssessmentSeeder::class);
        $admin = User::factory()->admin()->create();
        $version = AssessmentVersion::first();

        $attempt = AssessmentAttempt::create([
            'user_id' => $admin->id,
            'assessment_version_id' => $version->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $result = AssessmentResult::create([
            'assessment_attempt_id' => $attempt->id,
            'scores' => ['D' => 18, 'I' => 12, 'S' => 8, 'C' => 14],
            'primary_type' => 'D',
            'report' => 'تحلیل رفتار شناسی نمونه',
            'calculated_at' => now(),
        ]);

        $attemptRes = $this->actingAs($admin)->get("/panel/assessment-attempts/{$attempt->id}");
        $attemptRes->assertOk();
        $attemptRes->assertSee('مشخصات آزمون و کاربر');
        $attemptRes->assertSee('نتیجه و تحلیل روان‌سنجی');

        $resultRes = $this->actingAs($admin)->get("/panel/assessment-results/{$result->id}");
        $resultRes->assertOk();
        $resultRes->assertSee('اطلاعات عمومی ارزیابی');
        $resultRes->assertSee('نتایج تحلیل و نمرات روان‌سنجی');
    }

    public function test_admin_can_view_api_documentation_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/panel/api-documentation');

        $response->assertOk();
        $response->assertSee('مستندات تعاملی وب‌سرویس‌ها');
        $response->assertSee('کنسول تعاملی تست و مستندات Swagger');
        $response->assertSee('/docs/swagger-ui');
    }

    public function test_openapi_yaml_and_swagger_ui_routes_work(): void
    {
        $yamlResponse = $this->get('/docs/openapi.yaml');
        $yamlResponse->assertOk();
        $this->assertStringContainsString('openapi: 3.0.3', $yamlResponse->getContent());

        $swaggerUiResponse = $this->get('/docs/swagger-ui');
        $swaggerUiResponse->assertOk();
        $this->assertStringContainsString('SwaggerUIBundle', $swaggerUiResponse->getContent());
        $this->assertStringContainsString('/docs/openapi.yaml', $swaggerUiResponse->getContent());
    }
}
