<?php

namespace App\Services;

use App\Models\ExternalUserMapping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SsoService
{
    protected string $secret;

    protected string $serverKey;

    protected string $sourceSystem;

    public function __construct()
    {
        $this->secret = (string) config('services.sso.secret', 'default-dev-sso-secret');
        $this->serverKey = (string) config('services.sso.server_key', 'default-dev-server-key');
        $this->sourceSystem = (string) config('services.sso.source_system', 'main_system');
    }

    /**
     * تولید یک توکن امضاشده برای ارسال به فرانت‌اند یا آزمون‌ها.
     * ساختار: payloadBase64Url . '.' . signatureBase64Url
     */
    public function generateToken(array $payload, ?int $expiresInSeconds = 3600): string
    {
        if ($expiresInSeconds !== null && ! isset($payload['expires_at'])) {
            $payload['expires_at'] = time() + $expiresInSeconds;
        }

        $encodedPayload = $this->base64UrlEncode(
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        $rawSignature = hash_hmac('sha256', $encodedPayload, $this->secret, true);
        $encodedSignature = $this->base64UrlEncode($rawSignature);

        return "{$encodedPayload}.{$encodedSignature}";
    }

    /**
     * اعتبارسنجی محلی و مستقل توکن بدون هیچ ارتباط شبکه‌ای.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function verifyToken(string $token): array
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 2) {
            throw ValidationException::withMessages([
                'token' => 'فرمت توکن ارائه‌شده نامعتبر است.',
            ]);
        }

        [$encodedPayload, $encodedSignature] = $parts;

        $expectedRawSignature = hash_hmac('sha256', $encodedPayload, $this->secret, true);
        $expectedSignature = $this->base64UrlEncode($expectedRawSignature);

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            throw ValidationException::withMessages([
                'token' => 'امضای دیجیتال توکن نامعتبر است یا توکن دستکاری شده است.',
            ]);
        }

        $jsonPayload = $this->base64UrlDecode($encodedPayload);
        $payload = json_decode($jsonPayload, true);

        if (! is_array($payload) || empty($payload['external_user_id'])) {
            throw ValidationException::withMessages([
                'token' => 'محتوای توکن فاقد شناسه کاربر سامانه اصلی (external_user_id) است.',
            ]);
        }

        if (isset($payload['expires_at']) && time() > (int) $payload['expires_at']) {
            throw ValidationException::withMessages([
                'token' => 'مهلت زمانی استفاده از این توکن به پایان رسیده است.',
            ]);
        }

        return $payload;
    }

    /**
     * احراز هویت با توکن SSO، اتصال یا ایجاد کاربر محلی و صدور توکن دسترسی Sanctum.
     *
     * @return array{token: string, user: array<string, mixed>}
     */
    public function authenticate(string $token): array
    {
        $payload = $this->verifyToken($token);

        $externalUserId = (string) $payload['external_user_id'];
        $sourceSystem = (string) ($payload['source_system'] ?? $this->sourceSystem);
        $userName = (string) ($payload['name'] ?? "کاربر {$externalUserId}");
        $userEmail = ! empty($payload['email']) ? (string) $payload['email'] : null;

        $user = DB::transaction(function () use ($externalUserId, $sourceSystem, $userName, $userEmail) {
            $mapping = ExternalUserMapping::query()
                ->with('user')
                ->where('source_system', $sourceSystem)
                ->where('external_user_id', $externalUserId)
                ->first();

            if ($mapping && $mapping->user) {
                $existingUser = $mapping->user;
                $hasChanged = false;

                if ($userName && $existingUser->name !== $userName) {
                    $existingUser->name = $userName;
                    $hasChanged = true;
                }

                if ($userEmail && $existingUser->email !== $userEmail) {
                    // اطمینان از اینکه ایمیل متعلق به کاربر دیگری نباشد
                    $emailTaken = User::query()
                        ->where('email', $userEmail)
                        ->where('id', '!=', $existingUser->id)
                        ->exists();

                    if (! $emailTaken) {
                        $existingUser->email = $userEmail;
                        $hasChanged = true;
                    }
                }

                if ($hasChanged) {
                    $existingUser->save();
                }

                return $existingUser;
            }

            // اگر ایمیل ارسال نشده یا تکراری بود، یک ایمیل یکتا و معتبر ایجاد می‌کنیم
            $finalEmail = $userEmail;
            if (! $finalEmail || User::query()->where('email', $finalEmail)->exists()) {
                $finalEmail = "ext_{$sourceSystem}_{$externalUserId}@local.internal";
            }

            $newUser = User::create([
                'name' => $userName,
                'email' => $finalEmail,
                'password' => Hash::make(Str::random(32)),
                'is_admin' => false,
            ]);

            ExternalUserMapping::create([
                'user_id' => $newUser->id,
                'source_system' => $sourceSystem,
                'external_user_id' => $externalUserId,
            ]);

            return $newUser;
        });

        // ایجاد توکن Sanctum برای استفاده در فرانت‌اند Next.js
        $sanctumToken = $user->createToken('nextjs-sso')->plainTextToken;

        return [
            'token' => $sanctumToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'external_user_id' => $externalUserId,
                'source_system' => $sourceSystem,
            ],
        ];
    }

    /**
     * اعتبارسنجی کلید سرور-به-سرور سامانه اصلی.
     */
    public function isValidServerKey(?string $providedKey): bool
    {
        if (empty($providedKey)) {
            return false;
        }

        return hash_equals($this->serverKey, $providedKey);
    }

    /**
     * کدگذاری Base64Url سازگار با استاندارد JWT/RFC 7515.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * رمزگشایی Base64Url.
     */
    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
