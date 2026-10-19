<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalUserMapping;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalIntegrationController extends Controller
{
    /**
     * استعلام نتایج آزمون‌های یک کاربر توسط سامانه اصلی با استفاده از کلید سرور-به-سرور.
     */
    public function getUserResults(
        Request $request,
        string $externalUserId,
        SsoService $ssoService
    ): JsonResponse {
        $serverKey = $request->header('X-Server-Key');

        if (! $ssoService->isValidServerKey($serverKey)) {
            return response()->json([
                'message' => 'کلید احراز هویت سرور (X-Server-Key) نامعتبر است.',
            ], 403);
        }

        $sourceSystem = (string) $request->query('source_system', config('services.sso.source_system', 'main_system'));

        $mapping = ExternalUserMapping::query()
            ->with(['user.attempts' => function ($query) {
                $query->whereIn('status', ['completed', 'timed_out'])
                    ->with(['result', 'version.assessment'])
                    ->latest('id');
            }])
            ->where('source_system', $sourceSystem)
            ->where('external_user_id', $externalUserId)
            ->first();

        if (! $mapping || ! $mapping->user) {
            return response()->json([
                'message' => 'کاربری با این شناسه در سامانه آزمون یافت نشد.',
                'external_user_id' => $externalUserId,
                'data' => [],
            ], 404);
        }

        $results = $mapping->user->attempts->map(function ($attempt) {
            return [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'assessment' => [
                    'slug' => $attempt->version->assessment->slug,
                    'name' => $attempt->version->assessment->name,
                    'version' => $attempt->version->version,
                ],
                'started_at' => $attempt->started_at?->timezone(config('app.timezone'))->toIso8601String(),
                'completed_at' => $attempt->completed_at?->timezone(config('app.timezone'))->toIso8601String(),
                'result' => $attempt->result ? [
                    'scores' => $attempt->result->scores,
                    'primary_type' => $attempt->result->primary_type,
                    'report' => $attempt->result->report,
                    'calculated_at' => $attempt->result->calculated_at?->timezone(config('app.timezone'))->toIso8601String(),
                ] : null,
            ];
        });

        return response()->json([
            'external_user_id' => $externalUserId,
            'user' => [
                'name' => $mapping->user->name,
                'email' => $mapping->user->email,
            ],
            'total_attempts' => $results->count(),
            'data' => $results,
        ]);
    }
}
