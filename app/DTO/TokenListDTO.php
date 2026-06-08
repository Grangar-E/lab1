<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

readonly class TokenListDTO implements JsonSerializable
{
    public function __construct(
        public array $tokens
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'tokens' => $this->tokens,
            'count' => count($this->tokens),
        ];
    }
}