<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TokenServiceInterface;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TokenService implements TokenServiceInterface
{
    private string $secretKey;
    private int $accessTtl;
    private int $refreshTtl;
    private int $maxActiveTokens;

    public function __construct()
    {
        $this->secretKey = config('app.key');
        $this->accessTtl = (int) env('ACCESS_TOKEN_TTL', 60);
        $this->refreshTtl = (int) env('REFRESH_TOKEN_TTL', 10080);
        $this->maxActiveTokens = (int) env('MAX_ACTIVE_TOKENS', 5);
    }

    public function generateTokens(User $user): array
    {
        // Проверяем лимит, если превышен — отзываем самый старый токен
        if (!$this->checkTokenLimit($user->id)) {
            $this->revokeOldestToken($user->id);
        }

        $tokenId = (string) Str::uuid();
        $now = Carbon::now();
        $accessExpiresAt = $now->copy()->addMinutes($this->accessTtl);
        $refreshExpiresAt = $now->copy()->addMinutes($this->refreshTtl);

        $accessPayload = [
            'token_id' => $tokenId,
            'user_id' => $user->id,
            'type' => 'access',
            'exp' => $accessExpiresAt->timestamp,
        ];

        $refreshPayload = [
            'token_id' => $tokenId,
            'user_id' => $user->id,
            'type' => 'refresh',
            'exp' => $refreshExpiresAt->timestamp,
        ];

        $accessToken = $this->encodeToken($accessPayload);
        $refreshToken = $this->encodeToken($refreshPayload);

        UserToken::create([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'access_token_hash' => Hash::make($accessToken),
            'refresh_token_hash' => Hash::make($refreshToken),
            'access_expires_at' => $accessExpiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function validateAccessToken(string $token): ?array
    {
        $payload = $this->decodeToken($token);

        if (!$payload || $payload['type'] !== 'access') {
            return null;
        }

        $tokenRecord = UserToken::where('token_id', $payload['token_id'])
            ->where('is_revoked', false)
            ->first();

        if (!$tokenRecord) {
            return null;
        }

        $tokenRecord->update(['last_used_at' => Carbon::now()]);

        return $payload;
    }

    public function validateRefreshToken(string $token): ?array
    {
        $payload = $this->decodeToken($token);

        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }

        $tokenRecord = UserToken::where('token_id', $payload['token_id'])
            ->where('is_revoked', false)
            ->first();

        return $tokenRecord ? $payload : null;
    }

    public function revokeToken(string $tokenId, int $userId): void
    {
        UserToken::where('token_id', $tokenId)
            ->where('user_id', $userId)
            ->update(['is_revoked' => true]);
    }

    public function revokeAllTokens(int $userId): void
    {
        UserToken::where('user_id', $userId)
            ->update(['is_revoked' => true]);
    }

    public function getActiveTokens(int $userId): array
    {
        $tokens = UserToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return $tokens->map(function ($token) {
            return [
                'id' => $token->token_id,
                'createdAt' => $token->created_at->toISOString(),
                'expiresAt' => $token->access_expires_at->toISOString(),
                'lastUsedAt' => $token->last_used_at?->toISOString(),
            ];
        })->toArray();
    }

    public function checkTokenLimit(int $userId): bool
    {
        $count = UserToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->count();

        return $count < $this->maxActiveTokens;
    }

    private function revokeOldestToken(int $userId): void
    {
        $oldest = UserToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($oldest) {
            $oldest->update(['is_revoked' => true]);
        }
    }

    private function encodeToken(array $payload): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secretKey, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$base64Header.$base64Payload.$base64Signature";
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $payloadJson = base64_decode(strtr($base64Payload, '-_', '+/'));
        if (!$payloadJson) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!$payload) {
            return null;
        }

        // Проверяем подпись
        $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secretKey, true);
        $expectedBase64 = rtrim(strtr(base64_encode($expectedSignature), '+/', '-_'), '=');

        if (!hash_equals($expectedBase64, $base64Signature)) {
            return null;
        }

        // Проверяем срок действия
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}