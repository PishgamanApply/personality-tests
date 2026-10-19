<?php

namespace Tests\Feature;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentVersion;
use App\Models\ExternalUserMapping;
use App\Models\User;
use App\Services\CalculateAssessmentResultService;
use App\Services\SsoService;
use Database\Seeders\DiscAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoAuthenticationAndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DiscAssessmentSeeder::class);
    }

    public function test_sso_login_provisions_new_user_and_returns_sanctum_token(): void
    {
        $ssoService = app(SsoService::class);

        $payload = [
            'external_user_id' => 'emp_1001',
            'name' => 'سهراب سپهری',
            'email' => 'sohrab@company.com',
            'source_system' => 'main_system',
        ];

        $token = $ssoService->generateToken($payload, expiresInSeconds: 600);

        $response = $this->postJson('/api/auth/sso', [
            'token' => $token,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'external_user_id',
                'source_system',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'سهراب سپهری',
            'email' => 'sohrab@company.com',
        ]);

        $this->assertDatabaseHas('external_user_mappings', [
            'external_user_id' => 'emp_1001',
            'source_system' => 'main_system',
        ]);
    }

    public function test_sso_login_with_existing_user_reuses_same_user(): void
    {
        $ssoService = app(SsoService::class);

        $existingUser = User::factory()->create([
            'name' => 'نام قدیمی',
            'email' => 'old@company.com',
        ]);

        ExternalUserMapping::create([
            'user_id' => $existingUser->id,
            'source_system' => 'main_system',
            'external_user_id' => 'emp_2002',
        ]);

        $payload = [
            'external_user_id' => 'emp_2002',
            'name' => 'نام جدید به‌روزرسانی‌شده',
            'email' => 'updated@company.com',
            'source_system' => 'main_system',
        ];

        $token = $ssoService->generateToken($payload);

        $response = $this->postJson('/api/auth/sso', [
            'token' => $token,
        ]);

        $response->assertOk();
        $this->assertSame($existingUser->id, $response->json('user.id'));

        $existingUser->refresh();
        $this->assertSame('نام جدید به‌روزرسانی‌شده', $existingUser->name);
        $this->assertSame('updated@company.com', $existingUser->email);

        // تعداد کل کاربران نباید افزایش یافته باشد
        $this->assertSame(1, User::where('id', $existingUser->id)->count());
    }

    public function test_sso_login_fails_with_tampered_token_signature(): void
    {
        $ssoService = app(SsoService::class);

        $token = $ssoService->generateToken([
            'external_user_id' => 'emp_3003',
        ]);

        // تغییر کاراکتر پایانی توکن برای خراب کردن امضا
        $tamperedToken = substr($token, 0, -2).'xx';

        $response = $this->postJson('/api/auth/sso', [
            'token' => $tamperedToken,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    public function test_sso_login_fails_with_expired_token(): void
    {
        $ssoService = app(SsoService::class);

        // توکنی که ۱۰ ثانیه پیش منقضی شده است
        $token = $ssoService->generateToken([
            'external_user_id' => 'emp_4004',
            'expires_at' => time() - 10,
        ], expiresInSeconds: null);

        $response = $this->postJson('/api/auth/sso', [
            'token' => $token,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $ssoService = app(SsoService::class);
        $token = $ssoService->generateToken([
            'external_user_id' => 'emp_5005',
            'name' => 'کاربر آزمایشی',
            'email' => 'test@company.com',
        ]);

        $loginResponse = $this->postJson('/api/auth/sso', ['token' => $token]);
        $loginResponse->assertOk();
        $sanctumToken = $loginResponse->json('token');

        // فراخوانی /api/auth/me
        $meResponse = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->getJson('/api/auth/me');

        $meResponse->assertOk();
        $meResponse->assertJson([
            'user' => [
                'name' => 'کاربر آزمایشی',
                'email' => 'test@company.com',
                'external_user_id' => 'emp_5005',
                'source_system' => 'main_system',
            ],
        ]);

        // خروج
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->postJson('/api/auth/logout');

        $logoutResponse->assertOk();

        // بررسی حذف توکن از دیتابیس
        $this->assertDatabaseEmpty('personal_access_tokens');

        // پس از خروج و حذف توکن، با همان توکن دسترسی 401 می‌دهد
        auth()->forgetGuards();

        $afterLogoutResponse = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->getJson('/api/auth/me');

        $afterLogoutResponse->assertStatus(401);
    }

    public function test_end_to_end_sso_to_assessment_lifecycle(): void
    {
        $ssoService = app(SsoService::class);
        $token = $ssoService->generateToken([
            'external_user_id' => 'emp_e2e_99',
            'name' => 'کاربر آزمون کامل',
            'email' => 'e2e@company.com',
        ]);

        // ۱. ورود از طریق Next.js
        $loginRes = $this->postJson('/api/auth/sso', ['token' => $token]);
        $loginRes->assertOk();
        $sanctumToken = $loginRes->json('token');

        // ۲. شروع آزمون DISC
        $startRes = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->postJson('/api/assessments/disc/attempts');

        $startRes->assertCreated();
        $attemptId = $startRes->json('data.attempt_id');
        $this->assertNotNull($attemptId);
        $questions = $startRes->json('data.questions');
        $this->assertCount(16, $questions);

        // ۳. ثبت پاسخ‌ها
        $answers = [];
        foreach ($questions as $q) {
            $answers[] = [
                'question_id' => $q['id'],
                'option_id' => $q['options'][0]['id'],
            ];
        }

        $answersRes = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->putJson("/api/attempts/{$attemptId}/answers", [
                'answers' => $answers,
            ]);

        $answersRes->assertOk();
        $this->assertSame(16, $answersRes->json('saved_count'));

        // ۴. تکمیل آزمون
        $completeRes = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->postJson("/api/attempts/{$attemptId}/complete");

        $completeRes->assertOk();
        $this->assertSame('completed', $completeRes->json('data.status'));

        // ۵. دریافت گزارش
        $resultRes = $this->withHeader('Authorization', "Bearer {$sanctumToken}")
            ->getJson("/api/attempts/{$attemptId}/result");

        $resultRes->assertOk();
        $this->assertNotEmpty($resultRes->json('data.result.report'));
    }

    public function test_external_main_system_can_query_user_results_with_server_key(): void
    {
        $user = User::factory()->create();
        ExternalUserMapping::create([
            'user_id' => $user->id,
            'source_system' => 'main_system',
            'external_user_id' => 'emp_query_77',
        ]);

        $version = AssessmentVersion::first();
        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now()->addMinutes(15),
        ]);

        // بستن و امتیازدهی آزمون
        app(CalculateAssessmentResultService::class)->calculate($attempt, timedOut: true);

        $validServerKey = (string) config('services.sso.server_key');

        // استعلام موفقیت‌آمیز توسط سامانه اصلی با کلید سرور
        $response = $this->withHeader('X-Server-Key', $validServerKey)
            ->getJson('/api/external/users/emp_query_77/results');

        $response->assertOk();
        $response->assertJsonStructure([
            'external_user_id',
            'user' => ['name', 'email'],
            'total_attempts',
            'data' => [
                '*' => [
                    'attempt_id',
                    'status',
                    'assessment',
                    'result',
                ],
            ],
        ]);
        $this->assertSame('emp_query_77', $response->json('external_user_id'));
        $this->assertSame(1, $response->json('total_attempts'));

        // رد درخواست با کلید سرور اشتباه (403)
        $forbiddenRes = $this->withHeader('X-Server-Key', 'wrong-server-key')
            ->getJson('/api/external/users/emp_query_77/results');

        $forbiddenRes->assertStatus(403);
    }
}
