<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface TokenServiceInterface
{
    public function generateTokens(User $user): array;
    public function validateAccessToken(string $token): ?array;
    public function validateRefreshToken(string $token): ?array;
    public function revokeToken(string $tokenId, int $userId): void;
    public function revokeAllTokens(int $userId): void;
    public function getActiveTokens(int $userId): array;
    public function checkTokenLimit(int $userId): bool;
}