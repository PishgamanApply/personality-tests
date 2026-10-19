<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * ورود یا ثبت‌نام یکپارچه (SSO) با توکن امضاشده از سامانه اصلی شرکت.
     */
    public function sso(Request $request, SsoService $ssoService): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $authData = $ssoService->authenticate($validated['token']);

        return response()->json([
            'message' => 'ورود با موفقیت انجام شد.',
            'token' => $authData['token'],
            'token_type' => 'Bearer',
            'user' => $authData['user'],
        ], 200);
    }

    /**
     * مشخصات کاربر جاری احراز هویت شده.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('externalUserMapping');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'external_user_id' => $user->externalUserMapping?->external_user_id,
                'source_system' => $user->externalUserMapping?->source_system,
            ],
        ]);
    }

    /**
     * خروج و ابطال توکن جاری.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'خروج با موفقیت انجام شد.',
        ]);
    }
}
