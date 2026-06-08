<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

readonly class TokenInfoDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $createdAt,
        public string $expiresAt,
        public ?string $lastUsedAt = null
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->createdAt,
            'expiresAt' => $this->expiresAt,
            'lastUsedAt' => $this->lastUsedAt,
        ];
    }
}