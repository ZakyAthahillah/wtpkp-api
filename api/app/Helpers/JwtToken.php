<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JwtToken
{
    public static function issue(array $user, int $ttlMinutes = 480): array
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes($ttlMinutes);
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user['user_id'],
            'jti' => $jti,
            'iat' => $now->timestamp,
            'exp' => $expiresAt->timestamp,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'] ?? null,
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role_id' => $user['role_id'],
            ],
        ];

        return [
            'token_type' => 'Bearer',
            'access_token' => self::encode($payload),
            'expires_at' => $expiresAt->toDateTimeString(),
        ];
    }

    public static function parseBearer(?string $authorizationHeader): ?string
    {
        if (! $authorizationHeader || ! str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        return trim(substr($authorizationHeader, 7));
    }

    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expectedSignature = self::base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, self::secret(), true));

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        if (! is_array($decodedPayload) || ! isset($decodedPayload['exp'], $decodedPayload['jti'])) {
            return null;
        }

        if ((int) $decodedPayload['exp'] < Carbon::now()->timestamp) {
            return null;
        }

        if (self::isRevoked($decodedPayload['jti'])) {
            return null;
        }

        return $decodedPayload;
    }

    public static function revoke(array $payload): void
    {
        DB::table('jwt_revoked_tokens')->updateOrInsert(
            ['jti' => $payload['jti']],
            [
                'user_id' => (int) $payload['sub'],
                'expires_at' => Carbon::createFromTimestamp((int) $payload['exp'])->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
        );
    }

    private static function encode(array $payload): string
    {
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, self::secret(), true));

        return $header.'.'.$body.'.'.$signature;
    }

    private static function isRevoked(string $jti): bool
    {
        DB::table('jwt_revoked_tokens')->where('expires_at', '<', Carbon::now())->delete();

        return DB::table('jwt_revoked_tokens')->where('jti', $jti)->exists();
    }

    private static function secret(): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('APP_KEY is required for JWT signing.');
        }

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true) ?: $key;
        }

        return $key;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4)) ?: '';
    }
}
