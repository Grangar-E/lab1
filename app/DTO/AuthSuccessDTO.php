<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

readonly class AuthSuccessDTO implements JsonSerializable
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public UserDTO $user
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'user' => $this->user,
        ];
    }
}